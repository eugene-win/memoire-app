<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DefenseYear;
use App\Models\Filiere;
use App\Models\Permission;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\RoleAssignmentEvent;
use App\Models\Student;
use App\Models\StudyLevel;
use App\Models\Thesis;
use App\Models\ThesisPayment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdministrationController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $summary = [
            'users' => User::count(),
            'students' => User::where('role', 'student')->count(),
            'theses' => Thesis::count(),
            'theses_by_status' => Thesis::query()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'confirmed_payments_xof' => (int) ThesisPayment::where('status', 'paid')->sum('amount'),
            'pending_analysis' => Thesis::whereIn('status', ['analysis_pending', 'analysis_error'])->count(),
            'pending_physical_review' => Thesis::whereIn('status', ['awaiting_physical_review', 'physical_received'])->count(),
        ];

        return $request->expectsJson()
            ? response()->json($summary)
            : view('admin.index', compact('summary'));
    }

    public function users(Request $request): JsonResponse|View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'disabled'])],
            'role' => ['nullable', 'exists:roles,slug'],
        ]);

        $users = User::query()
            ->select(['id', 'name', 'first_name', 'last_name', 'email', 'matricule', 'role', 'requested_role', 'account_status', 'deposit_authorized', 'created_at'])
            ->with(['roles:id,slug,name', 'student.filiere:id,name', 'student.studyLevel:id,code,name'])
            ->with('depositAuthorization')
            ->when($validated['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('matricule', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('account_status', $status))
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($users);
        }

        return view('admin.users', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'filieres' => Filiere::query()->orderBy('name')->get(),
            'studyLevels' => StudyLevel::query()->orderBy('code')->get(),
            'defenseYears' => DefenseYear::query()->orderByDesc('label')->get(),
        ]);
    }

    public function roles(Request $request): JsonResponse|View
    {
        $roles = Role::query()->with('permissions:id,slug,name')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();

        return $request->expectsJson()
            ? response()->json(['roles' => $roles, 'permissions' => $permissions])
            : view('admin.roles', compact('roles', 'permissions'));
    }

    public function updateStatus(Request $request, User $user, ActivityLogger $activityLogger): JsonResponse
    {
        abort_if($request->user()->is($user), 403, 'Un administrateur ne peut pas suspendre son propre compte.');

        $validated = $request->validate([
            'account_status' => ['required', Rule::in(['active', 'suspended', 'disabled'])],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $user, $validated, $activityLogger): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if(
                $lockedUser->requested_role !== null && $validated['account_status'] === 'active',
                409,
                'Attribuez d’abord un rôle institutionnel à cette demande avant d’activer le compte.'
            );
            $this->protectLastActiveAdministrator($lockedUser, $validated['account_status'], $lockedUser->role);
            $oldStatus = $lockedUser->account_status;
            $lockedUser->update(['account_status' => $validated['account_status']]);
            $activityLogger->record($request->user(), 'user.status_changed', $lockedUser, [
                'from' => $oldStatus,
                'to' => $validated['account_status'],
                'reason' => $validated['reason'],
            ]);
        });

        return response()->json(['id' => $user->id, 'account_status' => $user->fresh()->account_status]);
    }

    public function updateAccount(Request $request, User $user, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'matricule' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'matricule')->ignore($user->id)],
        ]);
        abort_if($validated === [], 422, 'Aucune donnée à mettre à jour.');

        if (array_key_exists('first_name', $validated) || array_key_exists('last_name', $validated)) {
            $validated['name'] = trim(
                ($validated['first_name'] ?? $user->first_name ?? '')
                .' '
                .($validated['last_name'] ?? $user->last_name ?? '')
            );
            abort_if($validated['name'] === '', 422, 'Le nom du compte ne peut pas être vide.');
        }

        DB::transaction(function () use ($request, $user, $validated, $activityLogger): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->update($validated);
            if ($lockedUser->role === 'student' && array_key_exists('matricule', $validated)) {
                Student::query()->updateOrCreate(
                    ['user_id' => $lockedUser->id],
                    ['matricule' => $validated['matricule']],
                );
            }
            $activityLogger->record($request->user(), 'user.account_updated', $lockedUser, [
                'changed_fields' => array_keys($validated),
            ]);
        });

        return response()->json($user->fresh()->only(['id', 'name', 'first_name', 'last_name', 'email', 'matricule']));
    }

    public function assignRole(Request $request, User $user, ActivityLogger $activityLogger): JsonResponse
    {
        abort_if($request->user()->is($user), 403, 'Un administrateur ne peut pas modifier son propre rôle.');

        $validated = $request->validate([
            'role' => ['required', 'exists:roles,slug'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $user, $validated, $activityLogger): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $role = Role::query()->where('slug', $validated['role'])->firstOrFail();
            $this->protectLastActiveAdministrator($lockedUser, $lockedUser->account_status, $role->slug);
            $previousRole = $lockedUser->role;
            if ($previousRole === $role->slug) {
                return;
            }

            $oldRole = Role::query()->where('slug', $previousRole)->first();
            if ($oldRole) {
                RoleAssignmentEvent::create([
                    'user_id' => $lockedUser->id,
                    'role_id' => $oldRole->id,
                    'actor_id' => $request->user()->id,
                    'action' => 'revoked',
                    'reason' => $validated['reason'],
                    'occurred_at' => now(),
                ]);
            }

            $lockedUser->roles()->syncWithPivotValues([$role->id], [
                'assigned_by_id' => $request->user()->id,
            ]);
            $lockedUser->update(['role' => $role->slug, 'requested_role' => null]);
            RoleAssignmentEvent::create([
                'user_id' => $lockedUser->id,
                'role_id' => $role->id,
                'actor_id' => $request->user()->id,
                'action' => 'assigned',
                'reason' => $validated['reason'],
                'occurred_at' => now(),
            ]);
            $activityLogger->record($request->user(), 'user.role_changed', $lockedUser, [
                'from' => $previousRole,
                'to' => $role->slug,
                'reason' => $validated['reason'],
            ]);
        });

        return response()->json(['id' => $user->id, 'role' => $user->fresh()->role]);
    }

    public function updateStudentProfile(Request $request, User $user, ActivityLogger $activityLogger): JsonResponse
    {
        abort_unless($user->role === 'student', 404);
        $validated = $request->validate([
            'matricule' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'matricule')->ignore($user->id),
                Rule::unique('students', 'matricule')->ignore($user->student?->id),
            ],
            'filiere_id' => ['required', 'integer', 'exists:filieres,id'],
            'study_level_id' => ['required', 'integer', 'exists:study_levels,id'],
            'defense_year_id' => ['nullable', 'integer', 'exists:defense_years,id'],
            'enrollment_status' => ['required', Rule::in(['unknown', 'enrolled', 'graduated', 'withdrawn'])],
            'institutionally_verified_at' => ['nullable', 'date'],
            'defended_at' => ['nullable', 'date'],
        ]);

        $profile = DB::transaction(function () use ($request, $user, $validated, $activityLogger): Student {
            $profile = Student::query()->updateOrCreate(
                ['user_id' => $user->id],
                $validated,
            );
            if (array_key_exists('matricule', $validated)) {
                $user->update(['matricule' => $validated['matricule']]);
            }
            $activityLogger->record($request->user(), 'student.profile_updated', $profile, [
                'student_user_id' => $user->id,
            ]);

            return $profile;
        });

        return response()->json($profile->load(['filiere', 'studyLevel', 'defenseYear']));
    }

    public function updateRolePermissions(Request $request, Role $role, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['present', 'array', 'max:100'],
            'permissions.*' => ['required', 'string', 'distinct', 'exists:permissions,slug'],
        ]);
        abort_unless(
            $role->slug !== 'administrator'
                || (
                    in_array('users.manage', $validated['permissions'], true)
                    && in_array('roles.manage', $validated['permissions'], true)
                ),
            422,
            'Le rôle administrateur doit conserver les permissions de gestion des comptes et des rôles.'
        );

        $permissions = DB::transaction(function () use ($request, $role, $validated, $activityLogger) {
            $permissions = Permission::query()->whereIn('slug', $validated['permissions'])->get();
            $role->permissions()->sync($permissions->modelKeys());
            $activityLogger->record($request->user(), 'role.permissions_changed', $role, [
                'permissions' => $permissions->pluck('slug')->all(),
            ]);

            return $permissions;
        });

        return response()->json([
            'role' => $role->slug,
            'permissions' => $permissions->pluck('slug')->values(),
        ]);
    }

    public function settings(Request $request): JsonResponse|View
    {
        $settings = PlatformSetting::query()->orderBy('setting_group')->orderBy('setting_key')->get();

        return $request->expectsJson()
            ? response()->json($settings)
            : view('admin.settings', compact('settings'));
    }

    public function updateSetting(
        Request $request,
        PlatformSetting $setting,
        ActivityLogger $activityLogger
    ): JsonResponse {
        $bounds = match ($setting->setting_key) {
            'external_access_price_xof' => ['min:1', 'max:10000000'],
            'maximum_pdf_size_bytes' => ['min:1024', 'max:5242880'],
            'preview_page_count' => ['min:1', 'max:50'],
            default => abort(422, 'Ce paramètre ne peut pas être modifié via cette interface.'),
        };
        $validated = $request->validate([
            'value' => array_merge(['required', 'integer'], $bounds),
        ]);

        DB::transaction(function () use ($request, $setting, $validated, $activityLogger): void {
            $oldValue = $setting->value;
            $setting->update(['value' => $validated['value']]);
            $activityLogger->record($request->user(), 'platform_setting.updated', $setting, [
                'from' => $oldValue,
                'to' => $validated['value'],
            ]);
        });

        return response()->json($setting->fresh());
    }

    public function activityLogs(Request $request): JsonResponse|View
    {
        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->latest('occurred_at')
            ->paginate(50)
            ->withQueryString();

        return $request->expectsJson()
            ? response()->json($logs)
            : view('admin.activity-logs', compact('logs'));
    }

    private function protectLastActiveAdministrator(User $user, string $newStatus, string $newRole): void
    {
        if ($user->role !== 'administrator' || ($newStatus === 'active' && $newRole === 'administrator')) {
            return;
        }

        $otherActiveAdministrators = User::query()
            ->where('role', 'administrator')
            ->where('account_status', 'active')
            ->where('id', '!=', $user->id)
            ->count();
        abort_if($otherActiveAdministrators === 0, 409, 'Le dernier administrateur actif ne peut pas être désactivé ou rétrogradé.');
    }
}
