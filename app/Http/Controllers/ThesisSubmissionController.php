<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Models\ThesisDecision;
use App\Models\ThesisVersion;
use App\Models\User;
use App\Notifications\ThesisWorkflowNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ThesisSubmissionController extends Controller
{
    private const DEPARTMENTS = [
        'Finance et comptabilité',
        'Gestion des entreprises',
        'Marketing et commerce',
        'Informatique de gestion',
        'Économie appliquée',
    ];

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user();
        abort_unless($student->role === 'student' && $student->deposit_authorized, 403);

        $validated = $request->validate([
            'thesis_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', Rule::in(self::DEPARTMENTS)],
            'defended_year' => ['required', 'integer', 'between:2000,'.now()->year],
            'supervisor' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:51200'],
            'rights' => ['accepted'],
        ]);

        $file = $validated['document'];
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier PDF soumis.');
        }

        $signature = fread($handle, 5);
        fclose($handle);
        if ($signature !== '%PDF-') {
            throw ValidationException::withMessages([
                'document' => 'Le fichier sélectionné ne contient pas une signature PDF valide.',
            ]);
        }

        $path = $file->store('theses/'.$student->id, 'local');
        if (! is_string($path)) {
            throw new RuntimeException('Le fichier PDF n’a pas pu être enregistré dans le stockage privé.');
        }

        $checksum = hash_file('sha256', $file->getRealPath());
        if (! is_string($checksum)) {
            Storage::disk('local')->delete($path);
            throw new RuntimeException('Impossible de calculer l’empreinte du fichier PDF.');
        }

        $originalFilename = substr(
            preg_replace('/[\x00-\x1F\x7F]/', '_', basename($file->getClientOriginalName())) ?: 'memoire.pdf',
            0,
            255
        );

        try {
            DB::transaction(function () use ($validated, $student, $file, $path, $checksum, $originalFilename): void {
                if (ThesisVersion::query()->where('checksum', $checksum)->exists()) {
                    throw ValidationException::withMessages([
                        'document' => 'Ce fichier a déjà été soumis à la bibliothèque.',
                    ]);
                }

                if (! empty($validated['thesis_id'])) {
                    $thesis = Thesis::query()
                        ->whereKey($validated['thesis_id'])
                        ->where('student_id', $student->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $thesis || $thesis->status !== 'rejected') {
                        throw ValidationException::withMessages([
                            'thesis_id' => 'Seul un dépôt rejeté vous appartenant peut recevoir une nouvelle version.',
                        ]);
                    }

                    $versionNumber = $thesis->versions()->max('version_number') + 1;
                    $thesis->update([
                        'title' => $validated['title'],
                        'department' => $validated['department'],
                        'defended_year' => $validated['defended_year'],
                        'supervisor' => $validated['supervisor'] ?? null,
                        'status' => 'analysis_pending',
                        'published_at' => null,
                    ]);
                    $decisionType = 'resubmitted';
                } else {
                    $thesis = Thesis::create([
                        'reference' => 'EN-'.Str::upper(Str::random(12)),
                        'student_id' => $student->id,
                        'title' => $validated['title'],
                        'author_name' => $student->name,
                        'matricule' => $student->matricule,
                        'department' => $validated['department'],
                        'defended_year' => $validated['defended_year'],
                        'supervisor' => $validated['supervisor'] ?? null,
                        'status' => 'analysis_pending',
                    ]);
                    $versionNumber = 1;
                    $decisionType = 'submitted';
                }

                $version = $thesis->versions()->create([
                    'version_number' => $versionNumber,
                    'file_path' => $path,
                    'original_filename' => $originalFilename,
                    'file_size' => $file->getSize(),
                    'checksum' => $checksum,
                    'metadata_snapshot' => [
                        'title' => $validated['title'],
                        'author_name' => $student->name,
                        'matricule' => $student->matricule,
                        'department' => $validated['department'],
                        'defended_year' => $validated['defended_year'],
                        'supervisor' => $validated['supervisor'] ?? null,
                    ],
                    'rights_confirmed_at' => now(),
                    'analysis_status' => 'pending',
                ]);

                ThesisDecision::create([
                    'thesis_id' => $thesis->id,
                    'thesis_version_id' => $version->id,
                    'actor_id' => $student->id,
                    'type' => $decisionType,
                    'reason' => $decisionType === 'resubmitted' ? 'Nouvelle version soumise après rejet.' : null,
                ]);

                $isResubmission = $decisionType === 'resubmitted';
                $student->notify(new ThesisWorkflowNotification(
                    $isResubmission ? 'thesis_resubmitted' : 'thesis_submitted',
                    $isResubmission ? 'Nouvelle version déposée' : 'Dépôt reçu',
                    $isResubmission
                        ? 'Votre nouvelle version du mémoire « '.$thesis->title.' » a été enregistrée et attend une nouvelle analyse.'
                        : 'Votre mémoire « '.$thesis->title.' » a été enregistré et attend son analyse.',
                    $thesis->id,
                    $version->id,
                ));

                User::query()
                    ->whereIn('role', ['validator', 'library_manager'])
                    ->get()
                    ->each(fn (User $responsible) => $responsible->notify(new ThesisWorkflowNotification(
                        $isResubmission ? 'thesis_resubmitted' : 'thesis_submitted',
                        $isResubmission ? 'Nouvelle version à traiter' : 'Nouveau dépôt à traiter',
                        $student->name.' a soumis « '.$thesis->title.' »; le dossier est en attente d’analyse.',
                        $thesis->id,
                        $version->id,
                    )));
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('dashboard')->with('status', 'Votre mémoire a été soumis. Le dossier est en attente d’analyse.');
    }

    public function viewer(Request $request, ThesisVersion $version): View
    {
        $this->authorizeVersionAccess($request, $version);

        return view('thesis.viewer', [
            'thesis' => $version->thesis,
            'version' => $version,
        ]);
    }

    public function document(Request $request, ThesisVersion $version)
    {
        $this->authorizeVersionAccess($request, $version);

        $thesis = $version->thesis;
        $request->user()->consultations()->create([
            'thesis_id' => $thesis->id,
            'thesis_version_id' => $version->id,
            'access_type' => $this->consultationAccessType($request->user(), $thesis),
            'consulted_at' => now(),
        ]);

        $safeFilename = preg_replace('/[\r\n"\\\\]/', '_', $version->original_filename) ?: 'memoire.pdf';

        return Storage::disk('local')->response($version->file_path, $safeFilename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function authorizeVersionAccess(Request $request, ThesisVersion $version): void
    {
        $thesis = $version->thesis;
        $user = $request->user();
        $isOwner = $thesis->student_id === $user->id;
        $isResponsible = in_array($user->role, ['validator', 'library_manager'], true)
            && $thesis->student_id !== $user->id;
        $isStudentReadingPublishedThesis = $user->role === 'student'
            && $thesis->status === 'published'
            && $thesis->published_at !== null;
        $isPaidExternalReader = $user->role === 'external_reader'
            && $thesis->status === 'published'
            && $thesis->published_at !== null
            && $user->accessGrants()
                ->where('thesis_id', $thesis->id)
                ->where('status', 'active')
                ->exists();

        abort_unless($isOwner || $isResponsible || $isStudentReadingPublishedThesis || $isPaidExternalReader, 403);
        abort_unless(Storage::disk('local')->exists($version->file_path), 404);
    }

    private function consultationAccessType(User $user, Thesis $thesis): string
    {
        if ($thesis->student_id === $user->id) {
            return 'owner';
        }

        return match ($user->role) {
            'external_reader' => 'paid_external',
            'student' => 'student',
            'validator', 'library_manager' => 'responsible',
            default => 'other',
        };
    }
}
