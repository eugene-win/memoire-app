<?php

use App\Models\Role;
use App\Models\RoleAssignmentEvent;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('platform:assign-role {email} {role} {--reason=}', function (ActivityLogger $activityLogger): int {
    $user = User::query()->where('email', $this->argument('email'))->first();
    $role = Role::query()->where('slug', $this->argument('role'))->first();

    if (! $user || ! $role) {
        $this->error('Compte ou rôle introuvable. Exécutez d’abord php artisan db:seed.');

        return self::FAILURE;
    }

    if ($user->account_status !== 'active') {
        $this->error('Impossible d’attribuer un rôle à un compte inactif.');

        return self::FAILURE;
    }

    $reason = $this->option('reason') ?: 'Affectation de rôle effectuée depuis la console.';
    DB::transaction(function () use ($user, $role, $reason, $activityLogger): void {
        if ($user->role === $role->slug) {
            return;
        }

        $previousRole = Role::query()->where('slug', $user->role)->first();
        if ($previousRole) {
            RoleAssignmentEvent::create([
                'user_id' => $user->id,
                'role_id' => $previousRole->id,
                'action' => 'revoked',
                'reason' => $reason,
                'occurred_at' => now(),
            ]);
        }

        $user->roles()->sync([$role->id]);
        $user->update(['role' => $role->slug]);
        RoleAssignmentEvent::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'action' => 'assigned',
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
        $activityLogger->record(null, 'user.role_changed', $user, [
            'from' => $previousRole?->slug,
            'to' => $role->slug,
            'reason' => $reason,
            'source' => 'console',
        ]);
    });

    $this->info('Le rôle '.$role->slug.' a été attribué au compte '.$user->email.'.');

    return self::SUCCESS;
})->purpose('Attribuer un rôle à un compte existant depuis la console');
