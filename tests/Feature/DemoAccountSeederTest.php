<?php

use App\Mail\StudentLoginCodeMail;
use App\Models\DepositAuthorization;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('creates working demo logins only in the local environment', function () {
    Mail::fake();
    $this->seed(DatabaseSeeder::class);
    expect(User::query()->count())->toBe(0);
    expect(fn () => $this->seed(DemoAccountSeeder::class))
        ->toThrow(RuntimeException::class, 'uniquement en environnement local');

    $originalEnvironment = app()->environment();
    app()->instance('env', 'local');
    try {
        $this->seed(DatabaseSeeder::class);
    } finally {
        app()->instance('env', $originalEnvironment);
    }

    $accounts = [
        ['external_reader', 'lecteur.demo@example.test', 'LecteurDemo!2026'],
        ['validator', 'DEMO-VAL-001', 'FiliereDemo!2026'],
        ['validator', 'DEMO-BIB-001', 'BibliothequeDemo!2026'],
        ['administrator', 'administrateur.demo@example.test', 'AdminDemo!2026'],
        ['student', 'DEMO-ETU-001', null],
    ];

    expect(User::query()->count())->toBe(5);
    foreach ($accounts as [$accountType, $identifier, $password]) {
        $field = in_array($accountType, ['external_reader', 'administrator'], true) ? 'email' : 'matricule';
        $user = User::query()->where($field, $identifier)->firstOrFail();
        if ($accountType === 'student') {
            $code = null;
            $this->post(route('login.student-code.send'), ['matricule' => $identifier])
                ->assertRedirect();
            Mail::assertSent(StudentLoginCodeMail::class, function (StudentLoginCodeMail $mail) use (&$code): bool {
                $code = $mail->code;

                return $mail->hasTo('marcos134michel@gmail.com');
            });
            expect($code)->toMatch('/^\d{6}$/');
            $this->post(route('login.student-code.verify'), [
                'verification_code' => $code,
            ])->assertRedirect(route('dashboard'));
        } else {
            expect(Hash::check($password, $user->password))->toBeTrue();
            $this->post(route('login.store'), [
                'identifier' => $identifier,
                'password' => $password,
            ])->assertRedirect($accountType === 'administrator' ? route('admin.index') : route('dashboard'));
        }
        $this->assertAuthenticatedAs($user);

        $page = $this->get($accountType === 'administrator' ? route('admin.index') : route('dashboard'))
            ->assertOk();
        $page->assertSee(match ($accountType) {
            'external_reader' => 'Mes mémoires achetés',
            'student' => 'Mes dépôts',
            'administrator' => 'Espace d’administration',
            default => 'File des dépôts à traiter',
        });
        if ($identifier === 'DEMO-BIB-001') {
            $page->assertSee('Importer une archive');
        }

        $this->post(route('logout'))->assertRedirect(route('home'));
    }

    $student = User::query()->where('matricule', 'DEMO-ETU-001')->firstOrFail();
    expect($student->student->enrollment_status)->toBe('enrolled')
        ->and($student->student->institutionally_verified_at)->not->toBeNull()
        ->and($student->student->studyLevel->code)->toBe('L3')
        ->and($student->studentRegistry->email)->toBe('marcos134michel@gmail.com');
    expect(DepositAuthorization::query()->where('student_id', $student->id)->where('status', 'active')->exists())->toBeTrue();
    expect(Student::query()->where('user_id', $student->id)->exists())->toBeTrue();

    $originalEnvironment = app()->environment();
    app()->instance('env', 'local');
    try {
        $admin = User::query()->where('email', 'administrateur.demo@example.test')->firstOrFail();
        $admin->update(['password' => 'ChangedDemo!2026']);
        $this->seed(DatabaseSeeder::class);
    } finally {
        app()->instance('env', $originalEnvironment);
    }

    $admin = User::query()->where('email', 'administrateur.demo@example.test')->firstOrFail();
    expect(Hash::check('ChangedDemo!2026', $admin->password))->toBeTrue();
});
