<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('creates an external reader account and redirects to the dashboard', function () {
    $response = $this->post(route('register'), [
        'first_name' => 'Awa',
        'last_name' => 'Dossou',
        'email' => 'awa@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
        'terms' => 'on',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'name' => 'Awa Dossou',
        'email' => 'awa@example.com',
        'role' => 'external_reader',
    ]);
    expect(Hash::check('StrongPassword123!', User::where('email', 'awa@example.com')->value('password')))->toBeTrue();
});

it('authenticates an external reader with email and redirects to the dashboard', function () {
    User::factory()->create([
        'email' => 'reader@example.com',
        'password' => 'StrongPassword123!',
    ]);

    $response = $this->post(route('login.store'), [
        'account_type' => 'external_reader',
        'identifier' => 'reader@example.com',
        'password' => 'StrongPassword123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('authenticates a pre-registered student by matricule', function () {
    User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2025-001',
        'password' => 'StrongPassword123!',
    ]);

    $response = $this->post(route('login.store'), [
        'account_type' => 'student',
        'identifier' => 'ENEAM-2025-001',
        'password' => 'StrongPassword123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('keeps the dashboard behind authentication', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('opens login in external-reader mode by default', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('value="external_reader"', false)
        ->assertSee('type="email"', false)
        ->assertSee('Responsable de filière et de bibliothèque')
        ->assertDontSee('data-role="library_manager"', false);
});

it('authenticates a pre-registered validator with a matricule', function () {
    User::factory()->create([
        'role' => 'validator',
        'matricule' => 'ENEAM-STAFF-001',
        'password' => 'StrongPassword123!',
    ]);

    $response = $this->post(route('login.store'), [
        'account_type' => 'validator',
        'identifier' => 'ENEAM-STAFF-001',
        'password' => 'StrongPassword123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->get(route('dashboard'))->assertOk()->assertSee('File des dépôts à traiter');
});

it('authenticates a library manager with a matricule and gives them the validation queue', function () {
    User::factory()->create([
        'role' => 'library_manager',
        'matricule' => 'ENEAM-LIB-001',
        'password' => 'StrongPassword123!',
    ]);

    $this->post(route('login.store'), [
        'account_type' => 'validator',
        'identifier' => 'ENEAM-LIB-001',
        'password' => 'StrongPassword123!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('File des dépôts à traiter')
        ->assertSee('Responsable ENEAM')
        ->assertSee('Importer une archive');
});

it('does not offer an administration login or dashboard', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('data-role="administrator"', false)
        ->assertDontSee('Administration');

    $administrator = User::factory()->create([
        'role' => 'administrator',
        'email' => 'admin@example.com',
        'password' => 'StrongPassword123!',
    ]);

    $this->actingAs($administrator)->get(route('dashboard'))->assertForbidden();
});

it('shows a different dashboard for each account role', function () {
    $reader = User::factory()->create(['role' => 'external_reader']);
    $this->actingAs($reader)->get(route('dashboard'))->assertOk()->assertSee('Mes mémoires achetés');

    $student = User::factory()->create(['role' => 'student', 'matricule' => 'ENEAM-2025-002']);
    $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('Mes dépôts');

    $validator = User::factory()->create(['role' => 'validator', 'matricule' => 'ENEAM-STAFF-002']);
    $this->actingAs($validator)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('File des dépôts à traiter')
        ->assertSee('Importer une archive');
});

it('only enables student deposit access after institutional authorization', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2025-003',
        'deposit_authorized' => false,
    ]);

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Autorisation de dépôt non confirmée')
        ->assertDontSee('id="deposit-form"', false)
        ->assertDontSee('Ouvrir le formulaire de dépôt');

    $student->update(['deposit_authorized' => true]);

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Votre autorisation de dépôt est active')
        ->assertSee('Ouvrir le formulaire de dépôt')
        ->assertSee('id="deposit-form"', false)
        ->assertSee('Fichier PDF intégral (50 Mo maximum)');
});

it('shows an empty real validation queue when no submissions exist', function () {
    $validator = User::factory()->create([
        'role' => 'validator',
        'matricule' => 'ENEAM-STAFF-003',
    ]);

    $this->actingAs($validator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Aucun dépôt à traiter')
        ->assertSee('Le résultat d’analyse doit provenir du fournisseur institutionnel.');
});

it('sends a password reset link and changes the password with a valid token', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'lecteur@example.com']);

    $this->from(route('login'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('login'))
        ->assertSessionHas('password-reset-status', 'Si cette adresse correspond à un compte, un lien de réinitialisation lui sera envoyé.');

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    expect($token)->toBeString();
    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Choisir un nouveau mot de passe')
        ->assertSee('name="password_confirmation"', false);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'UpdatedPassword123!',
        'password_confirmation' => 'UpdatedPassword123!',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');

    expect(Hash::check('UpdatedPassword123!', $user->fresh()->password))->toBeTrue();
});

it('keeps password reset responses generic for unknown email addresses', function () {
    Notification::fake();

    $this->from(route('login'))
        ->post(route('password.email'), ['email' => 'inconnu@example.com'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('password-reset-status', 'Si cette adresse correspond à un compte, un lien de réinitialisation lui sera envoyé.');

    Notification::assertNothingSent();
});
