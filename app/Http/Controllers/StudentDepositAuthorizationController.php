<?php

namespace App\Http\Controllers;

use App\Models\DepositAuthorizationEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentDepositAuthorizationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $responsible = $request->user();
        $this->authorizeResponsible($responsible);

        $validated = $request->validate([
            'matricule' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $student = User::query()
            ->where('role', 'student')
            ->where('matricule', $validated['matricule'])
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'matricule' => 'Aucun compte étudiant ne correspond à ce matricule.',
            ]);
        }

        $wasGranted = DB::transaction(function () use ($student, $responsible, $validated): bool {
            $lockedStudent = User::query()->lockForUpdate()->findOrFail($student->id);
            if ($lockedStudent->deposit_authorized) {
                return false;
            }

            $lockedStudent->update(['deposit_authorized' => true]);
            DepositAuthorizationEvent::create([
                'student_id' => $lockedStudent->id,
                'responsible_id' => $responsible->id,
                'action' => 'granted',
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

    public function destroy(Request $request, User $student): RedirectResponse
    {
        $responsible = $request->user();
        $this->authorizeResponsible($responsible);
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $wasRevoked = DB::transaction(function () use ($student, $responsible, $validated): bool {
            $lockedStudent = User::query()->lockForUpdate()->findOrFail($student->id);
            if (! $lockedStudent->deposit_authorized) {
                return false;
            }

            $lockedStudent->update(['deposit_authorized' => false]);
            DepositAuthorizationEvent::create([
                'student_id' => $lockedStudent->id,
                'responsible_id' => $responsible->id,
                'action' => 'revoked',
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
        abort_unless(in_array($user->role, ['validator', 'library_manager'], true), 403);
    }
}
