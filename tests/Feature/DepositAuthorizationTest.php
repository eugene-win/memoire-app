<?php

use App\Models\DepositAuthorizationEvent;
use App\Models\Thesis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validAuthorizedStudentPdf(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('memoire.pdf', "%PDF-1.4\nstudent deposit\n%%EOF");
}

it('allows a responsible to grant a persistent deposit authorization and the student to submit', function () {
    Storage::fake('local');
    $responsible = User::factory()->create(['role' => 'validator']);
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ETU-TEST-001',
        'deposit_authorized' => false,
    ]);

    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.deposit-authorizations.store'), [
            'matricule' => $student->matricule,
            'reason' => 'Éligibilité institutionnelle vérifiée pour le dépôt.',
        ])
        ->assertRedirect(route('dashboard'));

    expect($student->fresh()->deposit_authorized)->toBeTrue();
    $this->assertDatabaseHas('deposit_authorization_events', [
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'action' => 'granted',
        'reason' => 'Éligibilité institutionnelle vérifiée pour le dépôt.',
    ]);
    $student->refresh();
    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Votre autorisation de dépôt est active')
        ->assertSee('Formulaire de dépôt');

    $this->actingAs($student)
        ->post(route('deposits.store'), [
            'title' => 'Mémoire après autorisation',
            'department' => 'Gestion des entreprises',
            'defended_year' => now()->year,
            'supervisor' => 'M. Encadreur',
            'document' => validAuthorizedStudentPdf(),
            'rights' => '1',
        ])
        ->assertRedirect(route('dashboard'));

    expect(Thesis::where('student_id', $student->id)->firstOrFail()->status)->toBe('analysis_pending');
});

it('records a reason when a responsible revokes a deposit authorization', function () {
    $responsible = User::factory()->create(['role' => 'library_manager']);
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ETU-TEST-002',
        'deposit_authorized' => true,
    ]);

    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.students.deposit-authorization.destroy', $student), [
            'reason' => 'Autorisation suspendue après réexamen du dossier.',
        ])
        ->assertRedirect(route('dashboard'));

    expect($student->fresh()->deposit_authorized)->toBeFalse()
        ->and(DepositAuthorizationEvent::where('student_id', $student->id)->count())->toBe(1);
    $this->assertDatabaseHas('deposit_authorization_events', [
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'action' => 'revoked',
        'reason' => 'Autorisation suspendue après réexamen du dossier.',
    ]);

    Storage::fake('local');
    $student->refresh();
    $this->actingAs($student)
        ->post(route('deposits.store'), [
            'title' => 'Dépôt après révocation',
            'department' => 'Gestion des entreprises',
            'defended_year' => now()->year,
            'document' => validAuthorizedStudentPdf(),
            'rights' => '1',
        ])
        ->assertForbidden();
    expect(Thesis::count())->toBe(0);
});

it('does not let readers grant deposit authorizations', function () {
    $reader = User::factory()->create(['role' => 'external_reader']);
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ETU-TEST-003',
        'deposit_authorized' => false,
    ]);

    $this->actingAs($reader)
        ->post(route('responsable.deposit-authorizations.store'), [
            'matricule' => $student->matricule,
            'reason' => 'Autorisation demandée pour le dépôt.',
        ])
        ->assertForbidden();

    expect($student->fresh()->deposit_authorized)->toBeFalse()
        ->and(DepositAuthorizationEvent::count())->toBe(0);
});
