<?php

namespace App\Http\Controllers;

use App\Models\ThesisVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThesisAnalysisReportController extends Controller
{
    public function __invoke(Request $request, ThesisVersion $version)
    {
        abort_unless($request->user()->hasRole(['validator', 'library_manager']), 403);
        abort_unless(is_string($version->analysis_report_path) && $version->analysis_report_path !== '', 404);
        abort_unless(Storage::disk('local')->exists($version->analysis_report_path), 404);

        $safeFilename = preg_replace('/[\r\n"\\\\]/', '_', $version->analysis_report_filename) ?: 'rapport-analyse.pdf';

        return Storage::disk('local')->response($version->analysis_report_path, $safeFilename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
