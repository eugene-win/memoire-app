<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validateWithBag('register', [
            'account_type' => ['required', Rule::in(['external_reader'])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'matricule' => ['prohibited'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = DB::transaction(function () use ($validated, $activityLogger): User {
            $firstName = trim($validated['first_name']);
            $lastName = trim($validated['last_name']);
            $user = User::create([
                'name' => trim($firstName.' '.$lastName),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => Str::lower($validated['email']),
                'role' => 'external_reader',
                'account_status' => 'active',
                'password' => $validated['password'],
            ]);

            if ($roleId = Role::query()->where('slug', 'external_reader')->value('id')) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }

            $activityLogger->record(null, 'user.registered', $user, [
                'account_type' => 'external_reader',
                'account_status' => 'active',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Votre compte lecteur est créé.');
    }
}
