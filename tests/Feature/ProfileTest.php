<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('allows a reader to update profile details and records the change', function () {
    $reader = User::factory()->create([
        'first_name' => 'Awa',
        'last_name' => 'Dossou',
        'email' => 'awa.old@example.test',
    ]);

    $this->actingAs($reader)
        ->from(route('dashboard'))
        ->patch(route('profile.update'), [
            'first_name' => 'Awa',
            'last_name' => 'Mensah',
            'email' => 'awa.new@example.test',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Votre profil a été mis à jour.');

    expect($reader->fresh()->name)->toBe('Awa Mensah')
        ->and($reader->fresh()->email)->toBe('awa.new@example.test');
    expect(ActivityLog::query()->where('action', 'user.profile_updated')->where('user_id', $reader->id)->exists())->toBeTrue();
});

it('requires the current password and confirmation to change a password', function () {
    $reader = User::factory()->create([
        'first_name' => 'Awa',
        'last_name' => 'Dossou',
        'password' => 'CurrentPassword123!',
    ]);

    $this->actingAs($reader)
        ->from(route('dashboard'))
        ->patch(route('profile.update'), [
            'first_name' => 'Awa',
            'last_name' => 'Dossou',
            'email' => $reader->email,
            'current_password' => 'WrongPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('current_password');

    $this->patch(route('profile.update'), [
        'first_name' => 'Awa',
        'last_name' => 'Dossou',
        'email' => $reader->email,
        'current_password' => 'CurrentPassword123!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect(route('dashboard'));

    expect(Hash::check('NewPassword123!', $reader->fresh()->password))->toBeTrue();
});
