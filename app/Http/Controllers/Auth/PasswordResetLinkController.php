<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordReset', [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::query()->where('email', Str::lower($validated['email']))->first();
        if ($user && $user->role !== 'student') {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with(
            'password-reset-status',
            'Si cette adresse correspond à un compte, un lien de réinitialisation lui sera envoyé.'
        );
    }
}
