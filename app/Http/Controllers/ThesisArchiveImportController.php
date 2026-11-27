<?php

namespace App\Http\Controllers;

use App\Models\DefenseYear;
use App\Models\Filiere;
use App\Models\Thesis;
use App\Models\ThesisArchiveImport;
use App\Models\ThesisDecision;
use App\Models\ThesisPublication;
use App\Models\ThesisVersion;
use App\Services\ActivityLogger;
use App\Services\Pdf\PdfDocumentValidator;
use App\Services\PlatformSettings;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ThesisArchiveImportController extends Controller
{
    public function store(
        Request $request,
        ActivityLogger $activityLogger,
        PlatformSettings $settings,
        PdfDocumentValidator $pdfValidator
    ): RedirectResponse {
        $responsible = $request->user();
        abort_unless(
            $responsible->hasRole('library_manager') && $responsible->hasPermission('archives.import'),
            403
        );
        $maximumFileSizeKb = max(1, (int) ceil($settings->integer('maximum_pdf_size_bytes', 5242880) / 1024));

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author_name' => ['required', 'string', 'max:255'],
            'matricule' => ['required', 'string', 'max:255'],
            'department' => ['required', Rule::exists('filieres', 'name')->where('is_active', true)],
            'defended_year' => ['required', 'integer', 'between:1900,'.now()->year],
            'supervisor' => ['nullable', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:255'],
            'control_result' => ['required', 'string', 'max:2000'],
            'document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.$maximumFileSizeKb],
            'physical_checked' => ['required', 'accepted'],
            'diffusion_rights_confirmed' => ['required', 'accepted'],
        ]);

        $file = $validated['document'];
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier PDF d’archive.');
        }

        $signature = fread($handle, 5);
        fclose($handle);
        if ($signature !== '%PDF-') {
            throw ValidationException::withMessages([
                'document' => 'Le fichier sélectionné ne contient pas une signature PDF valide.',
            ]);
        }
        $pdfValidator->validate($file->getRealPath(), 'document');

        $checksum = hash_file('sha256', $file->getRealPath());
        if (! is_string($checksum)) {
            throw new RuntimeException('Impossible de calculer l’empreinte du PDF d’archive.');
        }

        if (ThesisVersion::query()->where('checksum', $checksum)->exists()) {
            throw ValidationException::withMessages([
                'document' => 'Ce fichier existe déjà dans la bibliothèque.',
            ]);
        }

        $path = $file->store('theses/archives', 'local');
        if (! is_string($path)) {
            throw new RuntimeException('Le PDF d’archive n’a pas pu être enregistré dans le stockage privé.');
        }

        $originalFilename = substr(
            preg_replace('/[\x00-\x1F\x7F]/', '_', basename($file->getClientOriginalName())) ?: 'archive.pdf',
            0,
            255
        );
        $fileSize = $file->getSize();

        try {
            DB::transaction(function () use ($validated, $responsible, $path, $checksum, $originalFilename, $fileSize, $activityLogger): void {
                if (ThesisVersion::query()->where('checksum', $checksum)->exists()) {
                    throw ValidationException::withMessages([
                        'document' => 'Ce fichier existe déjà dans la bibliothèque.',
                    ]);
                }

                $filiere = Filiere::query()
                    ->where('name', $validated['department'])
                    ->where('is_active', true)
                    ->firstOrFail();
                $defenseYear = DefenseYear::query()->firstOrCreate(
                    ['label' => (string) $validated['defended_year']],
                    ['is_active' => true],
                );
                abort_unless($defenseYear->is_active, 422, 'Cette année de soutenance n’est pas active.');

                $thesis = Thesis::create([
                    'reference' => 'AR-'.Str::upper(Str::random(10)),
                    'student_id' => null,
                    'title' => $validated['title'],
                    'author_name' => $validated['author_name'],
                    'matricule' => $validated['matricule'],
                    'department' => $validated['department'],
                    'filiere_id' => $filiere->id,
                    'defended_year' => $validated['defended_year'],
                    'defense_year_id' => $defenseYear->id,
                    'supervisor' => $validated['supervisor'] ?? null,
                    'status' => 'published',
                    'visibility' => 'public',
                    'published_at' => now(),
                ]);

                $version = $thesis->versions()->create([
                    'version_number' => 1,
                    'file_path' => $path,
                    'original_filename' => $originalFilename,
                    'file_size' => $fileSize,
                    'checksum' => $checksum,
                    'submitted_by_id' => $responsible->id,
                    'submitted_at' => now(),
                    'mime_type' => 'application/pdf',
                    'version_status' => 'published',
                    'rights_confirmed_at' => now(),
                    'metadata_snapshot' => [
                        'title' => $validated['title'],
                        'author_name' => $validated['author_name'],
                        'matricule' => $validated['matricule'],
                        'department' => $validated['department'],
                        'defended_year' => $validated['defended_year'],
                        'supervisor' => $validated['supervisor'] ?? null,
                    ],
                    'analysis_status' => 'not_required',
                    'physical_received_at' => now(),
                    'physical_received_by' => $responsible->id,
                ]);

                ThesisArchiveImport::create([
                    'thesis_id' => $thesis->id,
                    'responsible_id' => $responsible->id,
                    'source' => $validated['source'],
                    'physical_checked' => true,
                    'diffusion_rights_confirmed' => true,
                    'control_result' => $validated['control_result'],
                    'imported_at' => now(),
                ]);

                ThesisDecision::create([
                    'thesis_id' => $thesis->id,
                    'thesis_version_id' => $version->id,
                    'actor_id' => $responsible->id,
                    'type' => 'archive_import_published',
                    'reason' => $validated['control_result'],
                ]);
                ThesisPublication::create([
                    'thesis_id' => $thesis->id,
                    'thesis_version_id' => $version->id,
                    'actor_id' => $responsible->id,
                    'action' => 'published',
                    'visibility' => $thesis->visibility,
                    'reason' => $validated['control_result'],
                    'physical_compatible' => true,
                    'rights_confirmed' => true,
                    'occurred_at' => now(),
                ]);
                $activityLogger->record($responsible, 'thesis.archive_imported', $thesis, [
                    'version_id' => $version->id,
                    'source' => $validated['source'],
                ]);
            });
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($path);

            if (ThesisVersion::query()->where('checksum', $checksum)->exists()) {
                throw ValidationException::withMessages([
                    'document' => 'Ce fichier existe déjà dans la bibliothèque.',
                ]);
            }

            throw $exception;
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('dashboard')->with('status', 'L’archive a été importée et publiée après confirmation des contrôles.');
    }
}
