<?php

namespace App\Http\Controllers;

use App\Models\ThesisVersion;
use App\Services\Pdf\ThesisPreviewGenerator;
use App\Services\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ThesisPreviewController extends Controller
{
    public function __invoke(
        Request $request,
        ThesisVersion $version,
        ThesisPreviewGenerator $generator,
        PlatformSettings $settings
    ) {
        $thesis = $version->thesis;
        abort_unless(
            $thesis->status === 'published'
                && $thesis->published_at !== null
                && $thesis->visibility === 'public'
                && $thesis->currentVersion?->is($version),
            404
        );
        abort_unless(Storage::disk('local')->exists($version->file_path), 404);

        $thesis->consultations()->create([
            'user_id' => $request->user()?->id,
            'thesis_version_id' => $version->id,
            'access_type' => 'preview',
            'consulted_at' => now(),
        ]);

        $filename = preg_replace('/[\r\n"\\\\]/', '_', $version->original_filename) ?: 'memoire.pdf';

        return response($generator->generate(
            Storage::disk('local')->path($version->file_path),
            $settings->integer('preview_page_count', 1),
        ), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
