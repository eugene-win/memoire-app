<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Models\ThesisDecision;
use App\Notifications\ThesisWorkflowNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ThesisReviewController extends Controller
{
    public function updateAnalysis(Request $request, Thesis $thesis): RedirectResponse
    {
        $this->authorizeReview($request, $thesis);

        $validated = $request->validate([
            'analysis_status' => ['required', Rule::in(['completed', 'error'])],
            'analysis_provider' => ['required', 'string', 'max:255'],
            'analysis_reference' => ['nullable', 'string', 'max:255'],
            'similarity_percent' => ['required_if:analysis_status,completed', 'nullable', 'numeric', 'between:0,100'],
            'analysis_exclusions' => ['nullable', 'string', 'max:2000'],
            'analysis_report' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'analysis_note' => ['required_if:analysis_status,error', 'nullable', 'string', 'max:2000'],
        ]);

        $reportFile = $validated['analysis_report'] ?? null;
        $reportPath = null;
        $reportFilename = null;

        if ($reportFile) {
            if ($validated['analysis_status'] !== 'completed') {
                throw ValidationException::withMessages([
                    'analysis_report' => 'Un rapport ne peut être joint qu’à une analyse terminée.',
                ]);
            }

            $handle = fopen($reportFile->getRealPath(), 'rb');
            if ($handle === false) {
                throw new RuntimeException('Impossible de lire le rapport PDF fourni.');
            }

            $signature = fread($handle, 5);
            fclose($handle);
            if ($signature !== '%PDF-') {
                throw ValidationException::withMessages([
                    'analysis_report' => 'Le rapport sélectionné ne contient pas une signature PDF valide.',
                ]);
            }

            $reportPath = $reportFile->store('analysis-reports/'.$thesis->id, 'local');
            if (! is_string($reportPath)) {
                throw new RuntimeException('Le rapport PDF n’a pas pu être enregistré dans le stockage privé.');
            }
            $reportFilename = substr(
                preg_replace('/[\x00-\x1F\x7F]/', '_', basename($reportFile->getClientOriginalName())) ?: 'rapport.pdf',
                0,
                255
            );
        }

        try {
            $oldReportPath = DB::transaction(function () use ($thesis, $request, $validated, $reportPath, $reportFilename): ?string {
                $lockedThesis = Thesis::query()->lockForUpdate()->findOrFail($thesis->id);
                abort_unless(in_array($lockedThesis->status, ['analysis_pending', 'analysis_error'], true), 409);

                $version = $lockedThesis->currentVersion()->lockForUpdate()->firstOrFail();
                $oldReportPath = $version->analysis_report_path;
                $version->update([
                    'analysis_status' => $validated['analysis_status'],
                    'analysis_provider' => $validated['analysis_provider'],
                    'analysis_reference' => $validated['analysis_reference'] ?? null,
                    'similarity_percent' => $validated['analysis_status'] === 'completed' ? $validated['similarity_percent'] : null,
                    'analysis_exclusions' => $validated['analysis_status'] === 'completed' ? ($validated['analysis_exclusions'] ?? null) : null,
                    'analysis_report_path' => $reportPath ?? $version->analysis_report_path,
                    'analysis_report_filename' => $reportFilename ?? $version->analysis_report_filename,
                    'analysis_note' => $validated['analysis_status'] === 'error' ? $validated['analysis_note'] : null,
                    'analyzed_at' => now(),
                ]);

                if ($validated['analysis_status'] === 'error') {
                    $lockedThesis->update(['status' => 'analysis_error']);
                    $decisionType = 'analysis_error';
                    $reason = $validated['analysis_note'];
                } elseif ((float) $validated['similarity_percent'] > 20) {
                    $lockedThesis->update(['status' => 'rejected']);
                    $decisionType = 'automatic_rejection';
                    $reason = 'Rejet automatique : taux de similitude supérieur à 20 % ('.$validated['similarity_percent'].' %).';
                } else {
                    $lockedThesis->update(['status' => 'awaiting_physical_review']);
                    $decisionType = 'analysis_acceptable';
                    $reason = 'Analyse terminée avec un taux de similitude de '.$validated['similarity_percent'].' %. Contrôle physique requis.';
                }

                ThesisDecision::create([
                    'thesis_id' => $lockedThesis->id,
                    'thesis_version_id' => $version->id,
                    'actor_id' => $request->user()->id,
                    'type' => $decisionType,
                    'reason' => $reason,
                ]);

                [$event, $title, $message] = match ($decisionType) {
                    'analysis_error' => [
                        'analysis_error',
                        'Analyse en attente',
                        'L’analyse de votre mémoire « '.$lockedThesis->title.' » a rencontré une erreur technique. Le dossier reste en attente et n’est pas rejeté.',
                    ],
                    'automatic_rejection' => [
                        'thesis_auto_rejected',
                        'Rejet automatique',
                        'Votre mémoire « '.$lockedThesis->title.' » a été rejeté automatiquement. '.$reason,
                    ],
                    default => [
                        'analysis_completed',
                        'Analyse terminée',
                        'L’analyse de « '.$lockedThesis->title.' » est acceptable ('.$validated['similarity_percent'].' %). Le contrôle physique est maintenant attendu.',
                    ],
                };
                $lockedThesis->student->notify(new ThesisWorkflowNotification(
                    $event,
                    $title,
                    $message,
                    $lockedThesis->id,
                    $version->id,
                ));

                return $oldReportPath;
            });
        } catch (Throwable $exception) {
            if ($reportPath !== null) {
                Storage::disk('local')->delete($reportPath);
            }

            throw $exception;
        }

        if ($reportPath !== null && $oldReportPath !== null && $oldReportPath !== $reportPath) {
            Storage::disk('local')->delete($oldReportPath);
        }

        return back()->with('status', 'Le résultat d’analyse a été enregistré.');
    }

    public function confirmPhysicalReceipt(Request $request, Thesis $thesis): RedirectResponse
    {
        $this->authorizeReview($request, $thesis);
        $request->validate([
            'physical_checked' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($thesis, $request): void {
            $lockedThesis = Thesis::query()->lockForUpdate()->findOrFail($thesis->id);
            abort_unless($lockedThesis->status === 'awaiting_physical_review', 409);

            $version = $lockedThesis->currentVersion()->lockForUpdate()->firstOrFail();
            $version->update([
                'physical_received_at' => now(),
                'physical_received_by' => $request->user()->id,
            ]);
            $lockedThesis->update(['status' => 'physical_received']);

            ThesisDecision::create([
                'thesis_id' => $lockedThesis->id,
                'thesis_version_id' => $version->id,
                'actor_id' => $request->user()->id,
                'type' => 'physical_received',
                'reason' => 'Réception du document physique confirmée.',
            ]);
            $lockedThesis->student->notify(new ThesisWorkflowNotification(
                'physical_review_completed',
                'Contrôle physique effectué',
                'La réception du document papier pour « '.$lockedThesis->title.' » a été confirmée; la décision finale est en attente.',
                $lockedThesis->id,
                $version->id,
            ));
        });

        return back()->with('status', 'La réception physique a été enregistrée.');
    }

    public function decide(Request $request, Thesis $thesis): RedirectResponse
    {
        $this->authorizeReview($request, $thesis);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['publish', 'reject'])],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:2000'],
            'physical_compatible' => ['required_if:decision,publish', 'accepted_if:decision,publish'],
        ]);

        DB::transaction(function () use ($thesis, $request, $validated): void {
            $lockedThesis = Thesis::query()->lockForUpdate()->findOrFail($thesis->id);
            abort_unless($lockedThesis->status === 'physical_received', 409);

            $version = $lockedThesis->currentVersion()->lockForUpdate()->firstOrFail();
            abort_unless(
                $version->analysis_status === 'completed'
                && (float) $version->similarity_percent <= 20
                && $version->physical_received_at !== null,
                409
            );

            $isPublishing = $validated['decision'] === 'publish';
            $lockedThesis->update([
                'status' => $isPublishing ? 'published' : 'rejected',
                'published_at' => $isPublishing ? now() : null,
            ]);

            ThesisDecision::create([
                'thesis_id' => $lockedThesis->id,
                'thesis_version_id' => $version->id,
                'actor_id' => $request->user()->id,
                'type' => $isPublishing ? 'published' : 'rejected',
                'reason' => $isPublishing ? 'Document physique comparé et déclaré compatible avec le PDF.' : $validated['reason'],
            ]);
            $lockedThesis->student->notify(new ThesisWorkflowNotification(
                $isPublishing ? 'thesis_published' : 'thesis_rejected',
                $isPublishing ? 'Mémoire publié' : 'Dépôt rejeté',
                $isPublishing
                    ? 'Votre mémoire « '.$lockedThesis->title.' » a été publié dans le catalogue.'
                    : 'Votre dépôt « '.$lockedThesis->title.' » a été rejeté. Motif : '.$validated['reason'],
                $lockedThesis->id,
                $version->id,
            ));
        });

        return back()->with('status', $validated['decision'] === 'publish'
            ? 'Le mémoire a été publié.'
            : 'Le dépôt a été rejeté avec motif.');
    }

    private function authorizeReview(Request $request, Thesis $thesis): void
    {
        abort_unless(in_array($request->user()->role, ['validator', 'library_manager'], true), 403);
        abort_if($thesis->student_id === $request->user()->id, 403);
    }
}
