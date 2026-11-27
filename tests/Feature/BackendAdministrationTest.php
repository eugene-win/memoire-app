<?php

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\RoleAssignmentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('allows administrators to update platform settings and records the change', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);
    $setting = PlatformSetting::where('setting_key', 'preview_page_count')->firstOrFail();
    expect($administrator->account_status)->toBe('active')
        ->and($administrator->hasPermission('platform_settings.manage'))->toBeTrue();

    $this->actingAs($administrator)
        ->get(route('admin.settings.index'))
        ->assertOk();
    $this->actingAs($administrator)
        ->patch(route('admin.settings.update', $setting), ['value' => 3])
        ->assertOk()
        ->assertJsonPath('value', 3);

    expect($setting->fresh()->value)->toBe(3)
        ->and(ActivityLog::where('action', 'platform_setting.updated')->count())->toBe(1);

    $similaritySetting = PlatformSetting::where('setting_key', 'maximum_similarity_percent')->firstOrFail();
    $this->actingAs($administrator)
        ->patch(route('admin.settings.update', $similaritySetting), ['value' => 15])
        ->assertUnprocessable();
});

it('caps the configured PDF size at five mebibytes', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);
    $setting = PlatformSetting::where('setting_key', 'maximum_pdf_size_bytes')->firstOrFail();

    expect((int) $setting->value)->toBe(5242880);

    $this->actingAs($administrator)
        ->patchJson(route('admin.settings.update', $setting), ['value' => 5242881])
        ->assertUnprocessable();

    $this->patchJson(route('admin.settings.update', $setting), ['value' => 5242880])
        ->assertOk()
        ->assertJsonPath('value', 5242880);
});

it('renders administration pages for browsers and preserves JSON endpoints for API clients', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);

    $this->actingAs($administrator)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertSee('Espace d’administration')
        ->assertSee('État des mémoires');
    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Comptes et profils étudiants')
        ->assertSee('data-admin-form', false);
    $this->get(route('admin.roles.index'))->assertOk()->assertSee('Rôles et permissions');
    $this->get(route('admin.references.index'))->assertOk()->assertSee('Référentiels académiques');
    $this->get(route('admin.settings.index'))->assertOk()->assertSee('Paramètres de la plateforme');
    $this->get(route('admin.activity-logs.index'))->assertOk()->assertSee('Journal d’activité');

    $this->getJson(route('admin.index'))
        ->assertOk()
        ->assertJsonStructure(['users', 'students', 'theses', 'theses_by_status', 'confirmed_payments_xof']);
    $this->getJson(route('admin.roles.index'))
        ->assertOk()
        ->assertJsonStructure(['roles', 'permissions']);
});

it('allows administrators to grant and revoke eligible student deposit authorizations', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-ADMIN-501',
        'deposit_authorized' => false,
    ]);
    prepareStudentForDeposit($student, false);

    expect($administrator->hasPermission('deposit_authorizations.manage'))->toBeTrue();

    $this->actingAs($administrator)
        ->post(route('admin.deposit-authorizations.store'), [
            'matricule' => $student->matricule,
            'reason' => 'Éligibilité vérifiée par le service.',
        ])
        ->assertRedirect();
    $this->assertDatabaseHas('deposit_authorizations', [
        'student_id' => $student->id,
        'status' => 'active',
    ]);
    expect($student->fresh()->deposit_authorized)->toBeTrue();
    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Révoquer l’autorisation');

    $this->post(route('admin.students.deposit-authorization.destroy', $student), [
        'reason' => 'Révocation administrative documentée.',
    ])->assertRedirect();
    $this->assertDatabaseHas('deposit_authorizations', [
        'student_id' => $student->id,
        'status' => 'revoked',
    ]);
    expect($student->fresh()->deposit_authorized)->toBeFalse();
    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Accorder l’autorisation');
});

it('protects administration endpoints and blocks self-demotion or last-admin removal', function () {
    $reader = User::factory()->create(['role' => 'external_reader']);
    $administrator = User::factory()->create(['role' => 'administrator']);
    $operator = User::factory()->create(['role' => 'validator']);
    $operatorRole = Role::where('slug', 'validator')->firstOrFail();
    $operatorRole->permissions()->sync(
        Permission::whereIn('slug', ['users.manage', 'roles.manage'])->pluck('id')
    );

    $this->actingAs($reader)->get(route('admin.index'))->assertForbidden();
    $this->actingAs($administrator)
        ->patch(route('admin.users.status', $administrator), [
            'account_status' => 'suspended',
            'reason' => 'Suspension demandée pour vérification.',
        ])
        ->assertForbidden();
    $this->actingAs($administrator)
        ->put(route('admin.users.role', $administrator), [
            'role' => 'student',
            'reason' => 'Changement de fonction demandé.',
        ])
        ->assertForbidden();
    $this->actingAs($operator)
        ->put(route('admin.users.role', $administrator), [
            'role' => 'student',
            'reason' => 'Réaffectation du rôle administrateur.',
        ])
        ->assertStatus(409);
});

it('bootstraps an initial administrator through the console with an audit event', function () {
    $user = User::factory()->create(['role' => 'external_reader']);
    $role = Role::where('slug', 'administrator')->firstOrFail();

    $this->artisan('platform:assign-role', [
        'email' => $user->email,
        'role' => $role->slug,
        '--reason' => 'Initial account provisioning.',
    ])->assertExitCode(0);

    expect($user->fresh()->role)->toBe('administrator')
        ->and($user->fresh()->hasPermission('users.manage'))->toBeTrue();
    $this->assertDatabaseHas('role_assignment_events', [
        'user_id' => $user->id,
        'role_id' => $role->id,
        'action' => 'assigned',
        'reason' => 'Initial account provisioning.',
    ]);
    expect(RoleAssignmentEvent::where('user_id', $user->id)->count())->toBe(2);
});

it('creates and deactivates academic reference records without deleting their history', function () {
    $administrator = User::factory()->create(['role' => 'administrator']);

    $response = $this->actingAs($administrator)
        ->postJson(route('admin.references.store', 'filieres'), [
            'code' => 'GEST-PROJ',
            'name' => 'Gestion de projets',
        ])
        ->assertCreated();
    $filiereId = $response->json('id');

    $this->patchJson(route('admin.references.status', ['type' => 'filieres', 'id' => $filiereId]), [
        'is_active' => false,
    ])->assertOk()->assertJsonPath('is_active', false);
});
