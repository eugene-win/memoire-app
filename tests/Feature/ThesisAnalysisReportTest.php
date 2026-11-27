<?php

use App\Models\Thesis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function validAnalysisReportPdf(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('rapport anti-plagiat.pdf', "%PDF-1.4\nRapport d’analyse\n%%EOF");
}

function createThesisAwaitingAnalysisReport(User $student): Thesis
{
    $thesis = Thesis::create([
        'reference' => 'EN-REPORT-'.str_pad((string) $student->id, 10, '0', STR_PAD_LEFT),
        'student_id' => $student->id,
        'title' => 'Mémoire avec rapport fournisseur',
        'author_name' => $student->name,
        'matricule' => $student->matricule,
        'department' => 'Gestion des entreprises',
        'defended_year' => now()->year,
        'status' => 'analysis_pending',
    ]);

    $thesis->versions()->create([
        'version_number' => 1,
        'file_path' => 'theses/'.$student->id.'/memoire.pdf',
        'original_filename' => 'memoire.pdf',
        'file_size' => 100,
        'checksum' => hash('sha256', 'analysis-report-'.$student->id),
        'analysis_status' => 'pending',
    ]);

    return $thesis;
}

it('stores and restricts access to the private provider report', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ETU-REPORT-001',
    ]);
    $responsible = User::factory()->create(['role' => 'validator']);
    $thesis = createThesisAwaitingAnalysisReport($student);
    $version = $thesis->currentVersion;

    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Outil institutionnel',
            'analysis_reference' => 'RAPPORT-ENEAM-001',
            'similarity_percent' => 14.5,
            'analysis_exclusions' => 'Bibliographie et citations directes exclues.',
            'analysis_report' => validAnalysisReportPdf(),
        ])
        ->assertRedirect(route('dashboard'));

    $version->refresh();
    expect($thesis->fresh()->status)->toBe('awaiting_physical_review')
        ->and($version->analysis_exclusions)->toBe('Bibliographie et citations directes exclues.')
        ->and($version->analysis_report_path)->toStartWith('analysis-reports/'.$thesis->id.'/');
    Storage::disk('local')->assertExists($version->analysis_report_path);

    $this->actingAs($responsible)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ouvrir le rapport PDF')
        ->assertSee('Bibliographie et citations directes exclues.');

    $this->actingAs($responsible)
        ->get(route('responsable.thesis-analysis-reports.show', $version))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs($student)
        ->get(route('responsable.thesis-analysis-reports.show', $version))
        ->assertForbidden();
});

it('rejects invalid provider report files without recording an analysis', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ETU-REPORT-002',
    ]);
    $responsible = User::factory()->create(['role' => 'library_manager']);
    $thesis = createThesisAwaitingAnalysisReport($student);

    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'completed',
            'analysis_provider' => 'Outil institutionnel',
            'similarity_percent' => 12,
            'analysis_report' => UploadedFile::fake()->createWithContent('rapport.pdf', 'not a pdf'),
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('analysis_report');

    expect($thesis->fresh()->status)->toBe('analysis_pending')
        ->and($thesis->currentVersion->analysis_status)->toBe('pending')
        ->and($thesis->currentVersion->analysis_report_path)->toBeNull();
    Storage::disk('local')->assertDirectoryEmpty('analysis-reports');
});

it('does not accept an analysis report when the provider analysis failed', function () {
    Storage::fake('local');
    $student = User::factory()->create([
        'role' => 'student',
        'matricule' => 'ETU-REPORT-003',
    ]);
    $responsible = User::factory()->create(['role' => 'validator']);
    $thesis = createThesisAwaitingAnalysisReport($student);

    $this->actingAs($responsible)
        ->from(route('dashboard'))
        ->post(route('responsable.theses.analysis', $thesis), [
            'analysis_status' => 'error',
            'analysis_provider' => 'Outil institutionnel',
            'analysis_note' => 'Le fournisseur était indisponible.',
            'analysis_report' => validAnalysisReportPdf(),
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('analysis_report');

    expect($thesis->fresh()->status)->toBe('analysis_pending')
        ->and($thesis->currentVersion->analysis_status)->toBe('pending');
    Storage::disk('local')->assertDirectoryEmpty('analysis-reports');
});
