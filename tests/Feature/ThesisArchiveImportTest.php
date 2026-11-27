<?php

use App\Models\Thesis;
use App\Models\ThesisArchiveImport;
use App\Models\ThesisDecision;
use App\Models\ThesisVersion;
use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReferenceDataSeeder::class);
});

function validArchivePdf(string $content = 'archive'): UploadedFile
{
    $pdf = new Fpdi;
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    $pdf->Write(10, $content);

    return UploadedFile::fake()->createWithContent('archive.pdf', $pdf->Output('S'));
}

function archiveImportPayload(UploadedFile $file, array $overrides = []): array
{
    return array_merge([
        'title' => 'Étude historique de la gestion publique',
        'author_name' => 'Koffi Exemple',
        'matricule' => 'ENEAM-ARCHIVE-1995',
        'department' => 'Gestion des entreprises',
        'defended_year' => 1995,
        'supervisor' => 'Mme Examinatrice',
        'source' => 'Fonds papier de la bibliothèque',
        'control_result' => 'PDF et exemplaire papier comparés; métadonnées et autorisation de diffusion vérifiées.',
        'physical_checked' => '1',
        'diffusion_rights_confirmed' => '1',
        'document' => $file,
    ], $overrides);
}

it('imports and publishes a checked archive without inventing a student account', function () {
    Storage::fake('local');
    $responsible = User::factory()->create(['role' => 'library_manager']);
    $studentReader = User::factory()->create(['role' => 'student']);
    prepareStudentForDeposit($studentReader);

    $this->actingAs($responsible)
        ->post(route('responsable.archives.store'), archiveImportPayload(validArchivePdf()))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'L’archive a été importée et publiée après confirmation des contrôles.');

    $thesis = Thesis::with('currentVersion')->firstOrFail();
    expect($thesis->student_id)->toBeNull()
        ->and($thesis->status)->toBe('published')
        ->and($thesis->published_at)->not->toBeNull()
        ->and($thesis->author_name)->toBe('Koffi Exemple')
        ->and($thesis->currentVersion->analysis_status)->toBe('not_required')
        ->and($thesis->currentVersion->physical_received_by)->toBe($responsible->id);

    $archive = ThesisArchiveImport::firstOrFail();
    expect($archive->responsible_id)->toBe($responsible->id)
        ->and($archive->source)->toBe('Fonds papier de la bibliothèque')
        ->and($archive->physical_checked)->toBeTrue()
        ->and($archive->diffusion_rights_confirmed)->toBeTrue();

    $this->assertDatabaseHas('thesis_decisions', [
        'thesis_id' => $thesis->id,
        'actor_id' => $responsible->id,
        'type' => 'archive_import_published',
    ]);
    $this->assertDatabaseHas('thesis_publications', [
        'thesis_id' => $thesis->id,
        'thesis_version_id' => $thesis->currentVersion->id,
        'actor_id' => $responsible->id,
        'action' => 'published',
        'visibility' => 'public',
    ]);
    Storage::disk('local')->assertExists($thesis->currentVersion->file_path);
    $this->get(route('home'))->assertOk()->assertSee($thesis->title);
    $this->actingAs($studentReader)
        ->get(route('thesis-versions.viewer', $thesis->currentVersion))
        ->assertOk();
    $externalReader = User::factory()->create(['role' => 'external_reader']);
    $this->actingAs($externalReader)
        ->get(route('thesis-versions.viewer', $thesis->currentVersion))
        ->assertForbidden();
});

it('prevents readers from importing archives and requires physical and rights checks', function () {
    Storage::fake('local');
    $reader = User::factory()->create(['role' => 'external_reader']);

    $this->actingAs($reader)
        ->post(route('responsable.archives.store'), archiveImportPayload(validArchivePdf()))
        ->assertForbidden();
    $validator = User::factory()->create(['role' => 'validator']);
    $this->actingAs($validator)
        ->post(route('responsable.archives.store'), archiveImportPayload(validArchivePdf()))
        ->assertForbidden();
    expect(Thesis::count())->toBe(0);

    $responsible = User::factory()->create(['role' => 'library_manager']);
    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.archives.store'), archiveImportPayload(validArchivePdf(), [
            'physical_checked' => null,
            'diffusion_rights_confirmed' => null,
        ]))
        ->assertSessionHasErrors(['physical_checked', 'diffusion_rights_confirmed']);
    expect(Thesis::count())->toBe(0)
        ->and(ThesisArchiveImport::count())->toBe(0);
});

it('rejects duplicate archive PDFs by checksum', function () {
    Storage::fake('local');
    $responsible = User::factory()->create(['role' => 'library_manager']);
    $file = validArchivePdf('same archived file');
    $fileBytes = file_get_contents($file->getRealPath());

    $this->actingAs($responsible)
        ->post(route('responsable.archives.store'), archiveImportPayload($file))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.archives.store'), archiveImportPayload(
            UploadedFile::fake()->createWithContent('archive.pdf', $fileBytes)
        ))
        ->assertSessionHasErrors('document');

    expect(Thesis::count())->toBe(1)
        ->and(ThesisVersion::count())->toBe(1)
        ->and(ThesisArchiveImport::count())->toBe(1)
        ->and(ThesisDecision::where('type', 'archive_import_published')->count())->toBe(1);
});
