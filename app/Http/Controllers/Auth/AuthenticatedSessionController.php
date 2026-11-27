<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentRegistry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('login', [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($validated['identifier']);
        $field = str_contains($identifier, '@') ? 'email' : 'matricule';
        $normalizedIdentifier = $field === 'email' ? Str::lower($identifier) : Str::upper($identifier);
        $existingUser = User::query()->where($field, $normalizedIdentifier)->first();

        if (
            $existingUser?->role === 'student'
            || ($field === 'matricule' && StudentRegistry::query()->where('matricule', $normalizedIdentifier)->exists())
        ) {
            return back()
                ->withErrors([
                    'identifier' => 'Les étudiants se connectent avec le code envoyé à l’adresse associée à leur matricule.',
                ], 'login')
                ->withInput($request->only('identifier'));
        }

        $credentials = [
            $field => $normalizedIdentifier,
            'account_status' => 'active',
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['identifier' => 'Identifiant ou mot de passe incorrect.'], 'login')
                ->withInput($request->only('identifier'));
        }

        $request->session()->regenerate();

        if ($request->user()->hasRole('administrator')) {
            return redirect()->intended(route('admin.index'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
