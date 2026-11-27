<?php

use App\Models\Thesis;
use App\Models\User;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

uses(RefreshDatabase::class);

function makePreviewPdf(): string
{
    $pdf = new Fpdi;
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    $pdf->Write(10, 'Public preview page');
    $pdf->AddPage();
    $pdf->Write(10, 'Private full document page');

    return $pdf->Output('S');
}

function createPublishedPreviewThesis(User $owner): array
{
    $thesis = Thesis::create([
        'reference' => 'EN-PREVIEW-001',
        'student_id' => $owner->id,
        'title' => 'Mémoire publié',
        'author_name' => $owner->name,
        'matricule' => $owner->matricule,
        'department' => 'Informatique de gestion',
        'defended_year' => now()->year,
        'status' => 'published',
        'published_at' => now(),
    ]);
    $version = $thesis->versions()->create([
        'version_number' => 1,
        'file_path' => 'theses/public-preview.pdf',
        'original_filename' => 'memoire.pdf',
        'file_size' => 100,
        'checksum' => hash('sha256', 'preview'),
        'submitted_at' => now(),
        'version_status' => 'published',
    ]);

    return [$thesis, $version];
}

it('serves the configured number of public preview pages to guests', function () {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => 'student', 'matricule' => 'ENEAM-PREVIEW-001']);
    [$thesis, $version] = createPublishedPreviewThesis($owner);
    Storage::disk('local')->put($version->file_path, makePreviewPdf());
    PlatformSetting::query()->where('setting_key', 'preview_page_count')->update(['value' => 2]);

    $response = $this->get(route('thesis-versions.preview', $version))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $previewPath = tempnam(sys_get_temp_dir(), 'thesis-preview-');
    file_put_contents($previewPath, $response->getContent());
    try {
        $preview = new Fpdi;
        expect($preview->setSourceFile($previewPath))->toBe(2);
    } finally {
        unlink($previewPath);
    }

    $this->assertDatabaseHas('thesis_consultations', [
        'thesis_id' => $thesis->id,
        'thesis_version_id' => $version->id,
        'user_id' => null,
        'access_type' => 'preview',
    ]);
});

it('never exposes more pages than the source PDF contains', function () {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => 'student', 'matricule' => 'ENEAM-PREVIEW-003']);
    [, $version] = createPublishedPreviewThesis($owner);
    Storage::disk('local')->put($version->file_path, makePreviewPdf());
    PlatformSetting::query()->where('setting_key', 'preview_page_count')->update(['value' => 50]);

    $response = $this->get(route('thesis-versions.preview', $version))->assertOk();
    $previewPath = tempnam(sys_get_temp_dir(), 'thesis-preview-');
    file_put_contents($previewPath, $response->getContent());
    try {
        expect((new Fpdi)->setSourceFile($previewPath))->toBe(2);
    } finally {
        unlink($previewPath);
    }
});

it('does not expose previews for unpublished or non-public theses', function () {
    Storage::fake('local');
    $owner = User::factory()->create(['role' => 'student', 'matricule' => 'ENEAM-PREVIEW-002']);
    [$thesis, $version] = createPublishedPreviewThesis($owner);
    Storage::disk('local')->put($version->file_path, makePreviewPdf());

    $thesis->update(['visibility' => 'private']);
    $this->get(route('thesis-versions.preview', $version))->assertNotFound();

    $thesis->update(['visibility' => 'public', 'status' => 'analysis_pending']);
    $this->get(route('thesis-versions.preview', $version))->assertNotFound();
});
