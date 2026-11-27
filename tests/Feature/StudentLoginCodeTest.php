<?php

use App\Mail\StudentLoginCodeMail;
use App\Models\StudentLoginChallenge;
use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\StudentRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('authenticates a student with a one-time code sent to the registry email', function () {
    Mail::fake();
    $this->seed([
        ReferenceDataSeeder::class,
        StudentRegistrySeeder::class,
    ]);

    $this->from(route('login'))
        ->post(route('login.student-code.send'), ['matricule' => 'demo-etu-001'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('student-login-code-status');

    $code = null;
    Mail::assertSent(StudentLoginCodeMail::class, function (StudentLoginCodeMail $mail) use (&$code): bool {
        $code = $mail->code;

        return $mail->hasTo('marcos134michel@gmail.com');
    });

    expect($code)->toMatch('/^\d{6}$/');
    $challenge = StudentLoginChallenge::query()->firstOrFail();
    expect(Hash::check($code, $challenge->code_hash))->toBeTrue()
        ->and($challenge->code_hash)->not->toBe($code)
        ->and($challenge->email_snapshot_hash)->toBe(hash('sha256', 'marcos134michel@gmail.com'))
        ->and($challenge->expires_at->isFuture())->toBeTrue();

    $this->post(route('login.student-code.verify'), [
        'verification_code' => $code,
    ])->assertRedirect(route('dashboard'));

    $student = User::query()->where('matricule', 'DEMO-ETU-001')->firstOrFail();
    $this->assertAuthenticatedAs($student);
    expect($student->email)->toBeNull()
        ->and($student->student->institutionally_verified_at)->not->toBeNull()
        ->and($student->studentRegistry->user_id)->toBe($student->id);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mes informations ENEAM')
        ->assertSee('DEMO-ETU-001')
        ->assertSee('Démo')
        ->assertSee('IG3');
});

it('explains that the local log mailer does not deliver messages', function () {
    Mail::fake();
    $this->seed([
        ReferenceDataSeeder::class,
        StudentRegistrySeeder::class,
    ]);
    config(['mail.default' => 'log']);
    $this->from(route('login'))
        ->post(route('login.student-code.send'), ['matricule' => 'DEMO-ETU-001'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('student-login-code-status', fn (string $status): bool => str_contains($status, 'aucun message ne sera livré'));

    Mail::assertSent(StudentLoginCodeMail::class);
});

it('uses the same response and code-entry screen for an unknown matricule', function () {
    Mail::fake();

    $this->from(route('login'))
        ->post(route('login.student-code.send'), ['matricule' => 'ENEAM-INCONNU-999'])
        ->assertRedirect(route('login'))
        ->assertSessionHas('student-login-code-status')
        ->assertSessionHas('student_login_challenge');

    Mail::assertNothingSent();
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('student-verification-code')
        ->assertSee('Si ce matricule est enregistré et associé à une adresse e-mail, un code a été envoyé.');

    $this->post(route('login.student-code.verify'), [
        'verification_code' => '123456',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('verification_code', null, 'login')
        ->assertSessionHas('student_login_challenge');

    for ($attempt = 1; $attempt < 5; $attempt++) {
        $this->post(route('login.student-code.verify'), [
            'verification_code' => '123456',
        ])->assertSessionHasErrors('verification_code', null, 'login');
    }

    expect($this->app['session.store']->get('student_login_challenge'))->toBeNull();
    $this->assertGuest();
    $this->assertDatabaseCount('student_login_challenges', 0);
});

it('consumes a student code after five failed attempts', function () {
    Mail::fake();
    $this->seed([
        ReferenceDataSeeder::class,
        StudentRegistrySeeder::class,
    ]);
    $this->post(route('login.student-code.send'), ['matricule' => 'DEMO-ETU-001']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.student-code.verify'), [
            'verification_code' => '000000',
        ])->assertSessionHasErrors('verification_code', null, 'login');
    }

    $challenge = StudentLoginChallenge::query()->firstOrFail();
    expect($challenge->attempts)->toBe(5)
        ->and($challenge->consumed_at)->not->toBeNull();
    expect($this->app['session.store']->get('student_login_challenge'))->toBeNull();
    $this->assertGuest();
});

it('rejects a code after its expiry', function () {
    Mail::fake();
    $this->seed([
        ReferenceDataSeeder::class,
        StudentRegistrySeeder::class,
    ]);
    $this->post(route('login.student-code.send'), ['matricule' => 'DEMO-ETU-001']);

    $code = null;
    Mail::assertSent(StudentLoginCodeMail::class, function (StudentLoginCodeMail $mail) use (&$code): bool {
        $code = $mail->code;

        return true;
    });
    StudentLoginChallenge::query()->firstOrFail()->update([
        'expires_at' => now()->subMinute(),
    ]);

    $this->post(route('login.student-code.verify'), [
        'verification_code' => $code,
    ])->assertSessionHasErrors('verification_code', null, 'login');

    $this->assertGuest();
    expect(User::query()->where('matricule', 'DEMO-ETU-001')->exists())->toBeFalse()
        ->and($this->app['session.store']->get('student_login_challenge'))->toBeNull();
});
