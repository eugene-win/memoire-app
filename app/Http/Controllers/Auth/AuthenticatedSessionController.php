<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('login', [
            'account_type' => ['required', Rule::in(['student', 'validator', 'library_manager', 'external_reader'])],
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $field = $validated['account_type'] === 'external_reader' ? 'email' : 'matricule';
        $roles = in_array($validated['account_type'], ['validator', 'library_manager'], true)
            ? ['validator', 'library_manager']
            : [$validated['account_type']];
        $credentials = [
            $field => trim($validated['identifier']),
            'role' => $roles,
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['identifier' => 'Identifiant ou mot de passe incorrect.'], 'login')
                ->withInput($request->only('identifier', 'account_type'));
        }

        $request->session()->regenerate();

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
