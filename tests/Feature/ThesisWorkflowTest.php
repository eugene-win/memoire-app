<?php

use App\Models\Thesis;
use App\Models\ThesisDecision;
use App\Models\ThesisVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validThesisPdf(string $content = 'document'): UploadedFile
{
    return UploadedFile::fake()->createWithContent('memoire.pdf', "%PDF-1.4\n".$content."\n%%EOF");
}

function thesisSubmission(UploadedFile $file, array $overrides = []): array
{
    return array_merge([
        'title' => 'Gestion des stocks en PME',
        'department' => 'Gestion des entreprises',
        'defended_year' => now()->year,
        'supervisor' => 'Mme Exemple',
        'document' => $file,
        'rights' => '1',
    ], $overrides);
}

function submitThesis(LaravelTestCase $test, User $student, UploadedFile $file, array $overrides = [])
{
    return $test->actingAs($student)
        ->from(route('dashboard'))
        ->post(route('deposits.store'), thesisSubmission($file, $overrides));
}

it('stores an eligible student thesis and its PDF privately', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-101',
        'deposit_authorized' => true,
    ]);

    submitThesis($this, $student, validThesisPdf())->assertRedirect(route('dashboard'));

    $thesis = Thesis::with('currentVersion')->firstOrFail();
    expect($thesis->status)->toBe('analysis_pending')
        ->and($thesis->student_id)->toBe($student->id)
        ->and($thesis->currentVersion->version_number)->toBe(1)
        ->and($thesis->currentVersion->analysis_status)->toBe('pending')
        ->and($thesis->currentVersion->metadata_snapshot['title'])->toBe('Gestion des stocks en PME')
        ->and($thesis->currentVersion->rights_confirmed_at)->not->toBeNull()
        ->and($thesis->currentVersion->checksum)->toHaveLength(64);

    Storage::disk('local')->assertExists($thesis->currentVersion->file_path);
    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($thesis->reference)
        ->assertSee('En attente d’analyse')
        ->assertSee('Version 1');
});

it('rejects unauthorized depositors and malformed or duplicate files', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-102',
        'deposit_authorized' => false,
    ]);

    submitThesis($this, $student, validThesisPdf())->assertForbidden();
    expect(Thesis::count())->toBe(0);

    $student->update(['deposit_authorized' => true]);
    submitThesis($this, $student, UploadedFile::fake()->createWithContent('not-a-pdf.pdf', 'not a pdf'))
        ->assertSessionHasErrors('document');

    $sameFile = validThesisPdf('same bytes');
    submitThesis($this, $student, $sameFile)->assertRedirect(route('dashboard'));
    submitThesis($this, $student, validThesisPdf('same bytes'))->assertSessionHasErrors('document');
    expect(Thesis::count())->toBe(1)
        ->and(ThesisVersion::count())->toBe(1);
});

it('keeps an eligible analysis pending physical review until a human publishes', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-103',
        'deposit_authorized' => true,
    ]);
    $validator = User::factory()->create([
        'role' => 'validator',
        'matricule' => 'ENEAM-STAFF-103',
    ]);

    submitThesis($this, $student, validThesisPdf())->assertRedirect(route('dashboard'));
    $thesis = Thesis::firstOrFail();

    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Fournisseur institutionnel',
            'analysis_reference' => 'RAPPORT-103',
            'similarity_percent' => 20,
        ])
        ->assertRedirect(route('dashboard'));

    expect($thesis->fresh()->status)->toBe('awaiting_physical_review');
    $this->actingAs($validator)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Confirmer la réception physique')
        ->assertSee('RAPPORT-103');

    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.physical-receipt', $thesis), ['physical_checked' => '1'])
        ->assertRedirect(route('dashboard'));

    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.decision', $thesis), [
            'decision' => 'publish',
            'physical_compatible' => '1',
        ])
        ->assertRedirect(route('dashboard'));

    expect($thesis->fresh()->status)->toBe('published')
        ->and($thesis->fresh()->published_at)->not->toBeNull();
    $this->assertDatabaseHas('thesis_decisions', [
        'thesis_id' => $thesis->id,
        'actor_id' => $validator->id,
        'type' => 'published',
    ]);
    expect($student->notifications()->get()->pluck('data.event')->all())->toContain('thesis_published');
});

it('notifies the depositor and responsible accounts and scopes read actions to the owner', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-109',
        'deposit_authorized' => true,
    ]);
    $validator = User::factory()->create([
        'role' => 'validator',
        'matricule' => 'ENEAM-STAFF-109',
    ]);
    $libraryManager = User::factory()->create([
        'role' => 'library_manager',
        'matricule' => 'ENEAM-LIB-109',
    ]);

    submitThesis($this, $student, validThesisPdf())->assertRedirect(route('dashboard'));

    $studentNotification = $student->notifications()->firstOrFail();
    $validatorNotification = $validator->notifications()->firstOrFail();
    $libraryNotification = $libraryManager->notifications()->firstOrFail();
    expect($studentNotification->data['event'])->toBe('thesis_submitted')
        ->and($studentNotification->data['title'])->toBe('Dépôt reçu')
        ->and($validatorNotification->data['event'])->toBe('thesis_submitted')
        ->and($validatorNotification->data['title'])->toBe('Nouveau dépôt à traiter')
        ->and($libraryNotification->data['event'])->toBe('thesis_submitted');

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dépôt reçu')
        ->assertSee('1 non lue');

    $this->actingAs($student)
        ->from(route('dashboard'))
        ->post(route('notifications.read', $validatorNotification->id))
        ->assertNotFound();

    $this->actingAs($student)
        ->from(route('dashboard'))
        ->post(route('notifications.read', $studentNotification->id))
        ->assertRedirect(route('dashboard'));

    expect($studentNotification->fresh()->read_at)->not->toBeNull();
    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('0 non lues')
        ->assertDontSee('Marquer comme lue');
});

it('automatically rejects a similarity score over twenty percent and logs analysis errors without rejection', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-104',
        'deposit_authorized' => true,
    ]);
    $validator = User::factory()->create([
        'role' => 'validator',
        'matricule' => 'ENEAM-STAFF-104',
    ]);
    submitThesis($this, $student, validThesisPdf('over threshold'))->assertRedirect(route('dashboard'));
    $thesis = Thesis::firstOrFail();

    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Fournisseur institutionnel',
            'similarity_percent' => 20.01,
        ])
        ->assertRedirect(route('dashboard'));

    expect($thesis->fresh()->status)->toBe('rejected');
    expect($student->notifications()->get()->pluck('data.event')->all())->toContain('thesis_auto_rejected');
    $this->assertDatabaseHas('thesis_decisions', [
        'thesis_id' => $thesis->id,
        'type' => 'automatic_rejection',
    ]);
    $this->actingAs($validator)->post(route('responsable.theses.physical-receipt', $thesis), [
        'physical_checked' => '1',
    ])->assertStatus(409);

    submitThesis($this, $student, validThesisPdf('provider error'))->assertRedirect(route('dashboard'));
    $errorCase = Thesis::latest('id')->firstOrFail();
    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $errorCase), [
            'analysis_status' => 'error',
            'analysis_provider' => 'Fournisseur institutionnel',
            'analysis_note' => 'Service indisponible',
        ])
        ->assertRedirect(route('dashboard'));
    expect($errorCase->fresh()->status)->toBe('analysis_error');
    expect($student->notifications()->get()->pluck('data.event')->all())->toContain('analysis_error');

    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $errorCase), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Fournisseur institutionnel',
            'similarity_percent' => 12,
        ])
        ->assertRedirect(route('dashboard'));
    expect($errorCase->fresh()->status)->toBe('awaiting_physical_review');
    expect($student->notifications()->get()->pluck('data.event')->all())->toContain('analysis_completed');
});

it('creates a new immutable version after rejection and restricts access to the private PDF', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-105',
        'deposit_authorized' => true,
    ]);
    $otherStudent = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-106',
        'deposit_authorized' => true,
    ]);
    $validator = User::factory()->create([
        'role' => 'library_manager',
        'matricule' => 'ENEAM-STAFF-105',
    ]);

    submitThesis($this, $student, validThesisPdf('original version'))->assertRedirect(route('dashboard'));
    $thesis = Thesis::with('currentVersion')->firstOrFail();
    $firstVersion = $thesis->currentVersion;

    $this->actingAs($student)->get(route('thesis-versions.document', $firstVersion))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs($otherStudent)->get(route('thesis-versions.document', $firstVersion))->assertForbidden();

    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Fournisseur institutionnel',
            'similarity_percent' => 10,
        ])
        ->assertRedirect(route('dashboard'));
    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.physical-receipt', $thesis), ['physical_checked' => '1'])
        ->assertRedirect(route('dashboard'));
    $this->actingAs($validator)->from(route('dashboard'))
        ->post(route('responsable.theses.decision', $thesis), [
            'decision' => 'reject',
            'reason' => 'Le document papier ne correspond pas au PDF.',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();
    expect($thesis->fresh()->status)->toBe('rejected');

    submitThesis($this, $student, validThesisPdf('corrected version'), [
        'thesis_id' => $thesis->id,
        'title' => 'Gestion des stocks corrigée',
    ])
        ->assertRedirect(route('dashboard'));

    expect($thesis->fresh()->status)->toBe('analysis_pending')
        ->and($thesis->versions()->count())->toBe(2)
        ->and($thesis->versions()->orderBy('version_number')->first()->id)->toBe($firstVersion->id)
        ->and($firstVersion->fresh()->metadata_snapshot['title'])->toBe('Gestion des stocks en PME')
        ->and($thesis->fresh()->currentVersion->metadata_snapshot['title'])->toBe('Gestion des stocks corrigée');

    $this->actingAs($validator)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($thesis->reference)
        ->assertSee('Version 2');
});

it('provides a protected viewer for published theses to students but not unpaid external readers', function () {
    Storage::fake('local');
    $owner = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-107',
        'deposit_authorized' => true,
    ]);
    $studentReader = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-108',
    ]);
    $externalReader = User::factory()->create(['role' => 'external_reader']);

    submitThesis($this, $owner, validThesisPdf())->assertRedirect(route('dashboard'));
    $thesis = Thesis::with('currentVersion')->firstOrFail();
    $thesis->update(['status' => 'published', 'published_at' => now()]);
    $version = $thesis->currentVersion;

    $this->actingAs($studentReader)
        ->get(route('thesis-versions.viewer', $version))
        ->assertOk()
        ->assertSee('Consultation protégée')
        ->assertSee(route('thesis-versions.document', $version));
    $this->actingAs($studentReader)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-viewer-url="'.route('thesis-versions.viewer', $version).'"', false)
        ->assertSee('data-can-view="true"', false);
    $this->actingAs($studentReader)
        ->get(route('thesis-versions.document', $version))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $this->assertDatabaseHas('thesis_consultations', [
        'user_id' => $studentReader->id,
        'thesis_id' => $thesis->id,
        'thesis_version_id' => $version->id,
        'access_type' => 'student',
    ]);

    $this->actingAs($externalReader)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-can-view="false"', false);
    $this->actingAs($externalReader)
        ->get(route('thesis-versions.viewer', $version))
        ->assertForbidden();
    $this->assertDatabaseMissing('thesis_consultations', [
        'user_id' => $externalReader->id,
        'thesis_id' => $thesis->id,
    ]);
});

it('prevents a validator from reviewing their own thesis', function () {
    Storage::fake('local');
    $studentValidator = User::factory()->create([
        'role' => 'validator',
        'matricule' => 'ENEAM-STAFF-107',
        'deposit_authorized' => true,
    ]);
    $thesis = Thesis::create([
        'reference' => 'EN-OWNTHESIS001',
        'student_id' => $studentValidator->id,
        'title' => 'Test mémoire',
        'author_name' => $studentValidator->name,
        'matricule' => $studentValidator->matricule,
        'department' => 'Finance et comptabilité',
        'defended_year' => now()->year,
        'status' => 'analysis_pending',
    ]);
    $version = $thesis->versions()->create([
        'version_number' => 1,
        'file_path' => 'theses/private.pdf',
        'original_filename' => 'private.pdf',
        'file_size' => 10,
        'checksum' => hash('sha256', 'fake'),
    ]);

    $this->actingAs($studentValidator)
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Fournisseur institutionnel',
            'similarity_percent' => 10,
        ])
        ->assertForbidden();
    expect(ThesisDecision::where('thesis_id', $thesis->id)->count())->toBe(0);
});

it('shows published theses in the public catalogue and hides every other status', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ENEAM-2026-108',
    ]);
    $published = Thesis::create([
        'reference' => 'EN-PUBLISHED108',
        'student_id' => $student->id,
        'title' => 'Mémoire réellement publié',
        'author_name' => $student->name,
        'matricule' => $student->matricule,
        'department' => 'Finance et comptabilité',
        'defended_year' => now()->year,
        'status' => 'published',
        'published_at' => now(),
    ]);
    $published->versions()->create([
        'version_number' => 1,
        'file_path' => 'theses/private.pdf',
        'original_filename' => 'private.pdf',
        'file_size' => 10,
        'checksum' => hash('sha256', 'published'),
    ]);
    Thesis::create([
        'reference' => 'EN-PENDING108',
        'student_id' => $student->id,
        'title' => 'Mémoire encore privé',
        'author_name' => $student->name,
        'matricule' => $student->matricule,
        'department' => 'Finance et comptabilité',
        'defended_year' => now()->year,
        'status' => 'analysis_pending',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mémoire réellement publié')
        ->assertSee('data-real="true"', false)
        ->assertDontSee('Mémoire encore privé')
        ->assertDontSee('Notices de démonstration');
});
