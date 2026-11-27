<?php

namespace App\Http\Controllers;

use App\Models\DepositAuthorization;
use App\Models\DepositAuthorizationEvent;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentDepositAuthorizationController extends Controller
{
    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $responsible = $request->user();
        $this->authorizeResponsible($responsible);

        $validated = $request->validate([
            'matricule' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $student = User::query()
            ->with(['student.filiere', 'student.studyLevel'])
            ->where('role', 'student')
            ->where('matricule', $validated['matricule'])
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'matricule' => 'Aucun compte étudiant ne correspond à ce matricule.',
            ]);
        }
        abort_unless(
            $student->account_status === 'active'
                && $student->student?->filiere?->is_active === true
                && $student->student?->studyLevel?->code === 'L3'
                && $student->student->studyLevel->is_active === true
                && $student->student->enrollment_status === 'enrolled'
                && $student->student->institutionally_verified_at !== null
                && $student->student->defended_at !== null,
            422,
            'Une autorisation ne peut être accordée qu’à un étudiant L3 vérifié ayant soutenu.'
        );

        $wasGranted = DB::transaction(function () use ($student, $responsible, $validated, $activityLogger): bool {
            $lockedStudent = User::query()->lockForUpdate()->findOrFail($student->id);
            $authorization = DepositAuthorization::query()->firstOrNew(['student_id' => $lockedStudent->id]);
            if (
                $lockedStudent->deposit_authorized
                && $authorization->status === 'active'
                && ($authorization->expires_at === null || $authorization->expires_at->isFuture())
            ) {
                return false;
            }

            $lockedStudent->update(['deposit_authorized' => true]);
            $authorization->fill([
                'authorized_by_id' => $responsible->id,
                'status' => 'active',
                'reason' => $validated['reason'],
                'authorized_at' => now(),
                'expires_at' => null,
            ])->save();
            DepositAuthorizationEvent::create([
                'student_id' => $lockedStudent->id,
                'responsible_id' => $responsible->id,
                'action' => 'granted',
                'reason' => $validated['reason'],
            ]);
            $activityLogger->record($responsible, 'deposit_authorization.granted', $lockedStudent, [
                'reason' => $validated['reason'],
            ]);

            return true;
        });

        return back()->with(
            'status',
            $wasGranted
                ? 'L’autorisation de dépôt a été accordée à '.$student->name.'.'
                : 'Cette autorisation de dépôt est déjà active.'
        );
    }

    public function destroy(Request $request, User $student, ActivityLogger $activityLogger): RedirectResponse
    {
        $responsible = $request->user();
        $this->authorizeResponsible($responsible);
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $wasRevoked = DB::transaction(function () use ($student, $responsible, $validated, $activityLogger): bool {
            $lockedStudent = User::query()->lockForUpdate()->findOrFail($student->id);
            $authorization = DepositAuthorization::query()->where('student_id', $lockedStudent->id)->first();
            if (! $lockedStudent->deposit_authorized && $authorization?->status !== 'active') {
                return false;
            }

            $lockedStudent->update(['deposit_authorized' => false]);
            DepositAuthorization::query()->updateOrCreate(
                ['student_id' => $lockedStudent->id],
                [
                    'authorized_by_id' => $responsible->id,
                    'status' => 'revoked',
                    'reason' => $validated['reason'],
                    'authorized_at' => $authorization?->authorized_at,
                    'expires_at' => null,
                ],
            );
            DepositAuthorizationEvent::create([
                'student_id' => $lockedStudent->id,
                'responsible_id' => $responsible->id,
                'action' => 'revoked',
                'reason' => $validated['reason'],
            ]);
            $activityLogger->record($responsible, 'deposit_authorization.revoked', $lockedStudent, [
                'reason' => $validated['reason'],
            ]);

            return true;
        });

        return back()->with(
            'status',
            $wasRevoked
                ? 'L’autorisation de dépôt de '.$student->name.' a été révoquée.'
                : 'Cette autorisation de dépôt était déjà inactive.'
        );
    }

    private function authorizeResponsible(User $user): void
    {
        abort_unless(
            $user->account_status === 'active'
                && (
                    $user->hasRole(['validator', 'library_manager'])
                    || $user->hasPermission('deposit_authorizations.manage')
                ),
            403
        );
    }
}
