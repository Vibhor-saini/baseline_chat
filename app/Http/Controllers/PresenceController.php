<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PresenceController extends Controller
{
    /**
     * Update the authenticated user's last_seen timestamp.
     * Called by chat.js on page load, visibility change, and before unload.
     */
    public function ping(Request $request): JsonResponse
    {
        $request->user()->update(['last_seen' => now()]);

        return response()->json(['ok' => true]);
    }
}
