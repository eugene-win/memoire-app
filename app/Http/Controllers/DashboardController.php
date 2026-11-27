<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Models\ThesisArchiveImport;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $notificationData = [
            'notifications' => $user->notifications()->latest()->limit(8)->get(),
            'unreadNotificationsCount' => $user->unreadNotifications()->count(),
        ];

        return match ($user->role) {
            'external_reader' => view('dashboard.reader', array_merge($notificationData, [
                'accessGrants' => $user->accessGrants()
                    ->where('status', 'active')
                    ->with(['thesis.currentVersion'])
                    ->latest('granted_at')
                    ->get(),
                'payments' => $user->thesisPayments()
                    ->with('thesis')
                    ->latest()
                    ->limit(10)
                    ->get(),
                'consultations' => $user->consultations()
                    ->with(['thesis', 'version'])
                    ->latest('consulted_at')
                    ->limit(10)
                    ->get(),
            ])),
            'student' => view('dashboard.student', array_merge($notificationData, [
                'consultations' => $user->consultations()
                    ->with(['thesis', 'version'])
                    ->latest('consulted_at')
                    ->limit(10)
                    ->get(),
                'theses' => Thesis::query()
                    ->where('student_id', $user->id)
                    ->with([
                        'versions' => fn ($query) => $query->orderByDesc('version_number'),
                        'decisions' => fn ($query) => $query->with(['version', 'actor'])->latest(),
                    ])
                    ->latest()
                    ->get(),
            ])),
            'validator', 'library_manager' => view('dashboard.validator', array_merge($notificationData, [
                'archiveImports' => ThesisArchiveImport::query()
                    ->with(['thesis', 'responsible'])
                    ->latest('imported_at')
                    ->limit(10)
                    ->get(),
                'authorizedStudents' => User::query()
                    ->where('role', 'student')
                    ->where('deposit_authorized', true)
                    ->orderBy('name')
                    ->limit(30)
                    ->get(),
                'theses' => Thesis::query()
                    ->where('student_id', '!=', $user->id)
                    ->whereIn('status', ['analysis_pending', 'analysis_error', 'awaiting_physical_review', 'physical_received'])
                    ->with(['student', 'currentVersion'])
                    ->oldest()
                    ->get(),
            ])),
            default => abort(403),
        };
    }
}
