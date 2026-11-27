<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LibraryHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $publishedTheses = Thesis::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->with('currentVersion')
            ->latest('published_at')
            ->get();
        $user = $request->user();
        $accessibleThesisIds = $user?->role === 'external_reader'
            ? $user->accessGrants()->where('status', 'active')->pluck('thesis_id')->all()
            : [];

        return view('welcome', compact('publishedTheses', 'accessibleThesisIds'));
    }
}
