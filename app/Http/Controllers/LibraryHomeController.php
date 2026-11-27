<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LibraryHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:255'],
            'filiere_id' => ['nullable', 'integer', 'exists:filieres,id'],
            'defended_year' => ['nullable', 'integer', 'between:1900,'.now()->year],
        ]);

        $publishedTheses = Thesis::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('visibility', 'public')
            ->with('currentVersion')
            ->when($validated['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    foreach (['title', 'author_name', 'matricule', 'department', 'supervisor'] as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            })
            ->when($validated['department'] ?? null, fn ($query, string $department) => $query->where('department', $department))
            ->when($validated['filiere_id'] ?? null, fn ($query, int $filiereId) => $query->where('filiere_id', $filiereId))
            ->when($validated['defended_year'] ?? null, fn ($query, int $year) => $query->where('defended_year', $year))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();
        $user = $request->user();
        $accessibleThesisIds = $user?->hasRole('external_reader')
            ? $user->accessGrants()->where('status', 'active')->pluck('thesis_id')->all()
            : [];

        return view('welcome', compact('publishedTheses', 'accessibleThesisIds'));
    }
}
