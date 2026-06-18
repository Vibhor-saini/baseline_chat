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

        // ── Set status to Available on login ─────────────────────────────
        // Reset any previously persisted manual status so presence logic
        // starts fresh. Only Busy/Away/DND manual selections persist across
        // sessions — a fresh login always starts as Available.
        $user = Auth::user();
        $user->update([
            'status'              => 'available',
            'status_manually_set' => false,
        ]);

        // Broadcast the status reset so other connected users see the change.
        $avatarUrl = $user->profile_image
            ? Storage::url($user->profile_image)
            : '';

        broadcast(new UserProfileUpdated(
            $user->id,
            $avatarUrl,
            'available',
            $user->name,
        ));

        return redirect()->intended(route('chat.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
