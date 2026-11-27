<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', 'min:12'],
        ]);
        $attributes = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
        ];
        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        DB::transaction(function () use ($user, $attributes, $activityLogger): void {
            $user->update($attributes);
            $activityLogger->record($user, 'user.profile_updated', $user, [
                'changed_fields' => array_keys($attributes),
            ]);
        });

        return back()->with('status', 'Votre profil a été mis à jour.');
    }
}
