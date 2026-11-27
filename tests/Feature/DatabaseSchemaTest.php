<?php

use App\Models\DefenseYear;
use App\Models\Filiere;
use App\Models\PaymentWebhookReceipt;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentRegistry;
use App\Models\StudyLevel;
use App\Models\Thesis;
use App\Models\ThesisConsultation;
use App\Models\User;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\StudentRegistrySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function databaseSchemaTestThesis(?int $studentId = null, ?int $filiereId = null, ?int $defenseYearId = null): Thesis
{
    return Thesis::query()->create([
        'reference' => (string) Str::uuid(),
        'student_id' => $studentId,
        'title' => 'Étude de gestion',
        'author_name' => 'Auteur de test',
        'matricule' => 'TEST-'.Str::random(8),
        'department' => 'Filière de test',
        'filiere_id' => $filiereId,
        'defended_year' => now()->year,
        'defense_year_id' => $defenseYearId,
    ]);
}

it('stores academic references and multiple roles without removing legacy user fields', function () {
    $this->seed([
        ReferenceDataSeeder::class,
        PlatformSettingsSeeder::class,
    ]);

    $filiere = Filiere::factory()->create();
    $year = DefenseYear::factory()->create();
    $level = StudyLevel::query()->where('code', 'L3')->firstOrFail();
    $user = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-TEST-01',
        'deposit_authorized' => true,
    ]);

    $student = Student::factory()->eligibleToDeposit()->create([
        'user_id' => $user->id,
        'matricule' => $user->matricule,
        'filiere_id' => $filiere->id,
        'study_level_id' => $level->id,
        'defense_year_id' => $year->id,
    ]);
    $thesis = databaseSchemaTestThesis($user->id, $filiere->id, $year->id);
    $readerRole = Role::query()->where('slug', 'external_reader')->firstOrFail();
    $studentRole = Role::query()->where('slug', 'student')->firstOrFail();
    $user->roles()->syncWithoutDetaching([$readerRole->id, $studentRole->id]);

    expect($user->fresh()->role)->toBe('student')
        ->and($user->fresh()->deposit_authorized)->toBeTrue()
        ->and($user->fresh()->roles)->toHaveCount(2)
        ->and($user->student->is($student))->toBeTrue()
        ->and($student->studyLevel->code)->toBe('L3')
        ->and($thesis->filiere->is($filiere))->toBeTrue()
        ->and($thesis->defenseYear->is($year))->toBeTrue()
        ->and(PlatformSetting::query()->where('setting_key', 'external_access_price_xof')->firstOrFail()->value)->toBe(1000);

    expect($readerRole->permissions()->where('slug', 'payment.create')->exists())->toBeTrue();
});

it('records anonymous previews and makes payment webhook event identifiers idempotent', function () {
    $thesis = databaseSchemaTestThesis();
    $version = $thesis->versions()->create([
        'version_number' => 1,
        'file_path' => 'private/theses/test.pdf',
        'original_filename' => 'test.pdf',
        'file_size' => 1200,
        'checksum' => hash('sha256', 'test-pdf'),
        'mime_type' => 'application/pdf',
        'submitted_at' => now(),
    ]);

    $consultation = ThesisConsultation::query()->create([
        'user_id' => null,
        'thesis_id' => $thesis->id,
        'thesis_version_id' => $version->id,
        'access_type' => 'preview',
        'consulted_at' => now(),
    ]);

    PaymentWebhookReceipt::query()->create([
        'provider' => 'test-provider',
        'provider_event_id' => 'event-unique-001',
        'received_at' => now(),
    ]);

    expect($consultation->user_id)->toBeNull();

    expect(fn () => PaymentWebhookReceipt::query()->create([
        'provider' => 'test-provider',
        'provider_event_id' => 'event-unique-001',
        'received_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('seeds a matricule registry with enough synthetic students across all licence levels', function () {
    $this->seed([
        ReferenceDataSeeder::class,
        StudentRegistrySeeder::class,
    ]);

    expect(StudentRegistry::query()->count())->toBe(31)
        ->and(StudentRegistry::query()->whereHas('studyLevel', fn ($query) => $query->where('code', 'L1'))->count())->toBe(10)
        ->and(StudentRegistry::query()->whereHas('studyLevel', fn ($query) => $query->where('code', 'L2'))->count())->toBe(10)
        ->and(StudentRegistry::query()->whereHas('studyLevel', fn ($query) => $query->where('code', 'L3'))->count())->toBe(11)
        ->and(StudentRegistry::query()->whereNotNull('date_of_birth')->count())->toBe(31)
        ->and(StudentRegistry::query()->whereNotNull('place_of_birth')->count())->toBe(31)
        ->and(StudentRegistry::query()->whereNotNull('email')->count())->toBe(5)
        ->and(StudentRegistry::query()->whereNotNull('class_code')->count())->toBe(31);
});

it('prevents assigning the same email address to multiple student records', function () {
    $this->seed([
        ReferenceDataSeeder::class,
        StudentRegistrySeeder::class,
    ]);
    $email = StudentRegistry::query()->whereNotNull('email')->value('email');
    $otherStudent = StudentRegistry::query()->whereNull('email')->firstOrFail();

    expect(fn () => $otherStudent->update(['email' => $email]))
        ->toThrow(QueryException::class);
});
