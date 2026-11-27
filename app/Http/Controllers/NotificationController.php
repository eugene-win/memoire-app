<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->findOrFail($notification)
            ->markAsRead();

        return back()->with('status', 'La notification a été marquée comme lue.');
    }
}
