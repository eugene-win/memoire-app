<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefenseYear;
use App\Models\Filiere;
use App\Models\StudyLevel;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferenceDataController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $references = [
            'filieres' => Filiere::query()->orderBy('name')->get(),
            'study_levels' => StudyLevel::query()->orderBy('code')->get(),
            'defense_years' => DefenseYear::query()->orderByDesc('label')->get(),
        ];

        return $request->expectsJson()
            ? response()->json($references)
            : view('admin.references', $references);
    }

    public function store(Request $request, string $type, ActivityLogger $activityLogger): JsonResponse
    {
        $model = $this->modelFor($type);
        $validated = match ($type) {
            'filieres' => $request->validate([
                'code' => ['required', 'string', 'max:32', 'unique:filieres,code'],
                'name' => ['required', 'string', 'max:255'],
            ]),
            'study-levels' => $request->validate([
                'code' => ['required', 'string', 'max:16', 'unique:study_levels,code'],
                'name' => ['required', 'string', 'max:255'],
            ]),
            'defense-years' => $request->validate([
                'label' => ['required', 'string', 'max:20', 'unique:defense_years,label'],
                'starts_on' => ['nullable', 'date'],
                'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            ]),
        };

        $record = DB::transaction(function () use ($model, $validated, $request, $type, $activityLogger) {
            $record = $model::create($validated + ['is_active' => true]);
            $activityLogger->record($request->user(), 'reference.created', $record, ['type' => $type]);

            return $record;
        });

        return response()->json($record, 201);
    }

    public function updateStatus(Request $request, string $type, int $id, ActivityLogger $activityLogger): JsonResponse
    {
        $model = $this->modelFor($type);
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $record = DB::transaction(function () use ($model, $id, $validated, $request, $type, $activityLogger) {
            $record = $model::query()->findOrFail($id);
            $record->update(['is_active' => $validated['is_active']]);
            $activityLogger->record($request->user(), 'reference.status_changed', $record, [
                'type' => $type,
                'is_active' => $validated['is_active'],
            ]);

            return $record;
        });

        return response()->json($record->fresh());
    }

    public function update(Request $request, string $type, int $id, ActivityLogger $activityLogger): JsonResponse
    {
        $model = $this->modelFor($type);
        $validated = match ($type) {
            'filieres' => $request->validate([
                'code' => ['sometimes', 'required', 'string', 'max:32', 'unique:filieres,code,'.$id],
                'name' => ['sometimes', 'required', 'string', 'max:255'],
            ]),
            'study-levels' => $request->validate([
                'code' => ['sometimes', 'required', 'string', 'max:16', 'unique:study_levels,code,'.$id],
                'name' => ['sometimes', 'required', 'string', 'max:255'],
            ]),
            'defense-years' => $request->validate([
                'label' => ['sometimes', 'required', 'string', 'max:20', 'unique:defense_years,label,'.$id],
                'starts_on' => ['sometimes', 'nullable', 'date'],
                'ends_on' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_on'],
            ]),
        };
        abort_if($validated === [], 422, 'Aucune donnée à mettre à jour.');
        $record = DB::transaction(function () use ($model, $id, $validated, $request, $type, $activityLogger) {
            $record = $model::query()->findOrFail($id);
            $record->update($validated);
            $activityLogger->record($request->user(), 'reference.updated', $record, ['type' => $type]);

            return $record;
        });

        return response()->json($record->fresh());
    }

    private function modelFor(string $type): string
    {
        return match ($type) {
            'filieres' => Filiere::class,
            'study-levels' => StudyLevel::class,
            'defense-years' => DefenseYear::class,
            default => abort(404),
        };
    }
}
