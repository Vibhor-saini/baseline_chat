<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Events\UserProfileUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // ── Restore / reset status on login ──────────────────────────────
        // If the user had manually set a status (Busy/Away/DND) before their
        // last session ended, honour it — do NOT reset to Available.
        // Only reset when status_manually_set is false (i.e. it was never
        // explicitly chosen, or they had previously selected Available).
        $user = Auth::user();

        $currentStatus = $user->status instanceof \App\Enums\UserStatus
            ? $user->status->value
            : ($user->status ?? 'available');

        if (! $user->status_manually_set) {
            $currentStatus = 'available';
            $user->update(['status' => 'available']);
        }

        // Broadcast the status so other connected users see the correct value.
        $avatarUrl = $user->profile_image
            ? Storage::url($user->profile_image)
            : '';

        broadcast(new UserProfileUpdated(
            $user->id,
            $avatarUrl,
            $currentStatus,
            $user->name,
        ));

        return redirect()->intended(route('chat.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     * Broadcasts an 'offline' status before logging out so other connected
     * users immediately see the user go offline without waiting for the
     * WebSocket presence-leave timeout (which can take 30–60 seconds).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Capture these before the session is invalidated
        if ($user) {
            $avatarUrl = $user->profile_image
                ? Storage::url($user->profile_image)
                : '';

            broadcast(new UserProfileUpdated(
                $user->id,
                $avatarUrl,
                'offline',   // sentinel — not a DB value, just a broadcast signal
                $user->name,
            ));
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
