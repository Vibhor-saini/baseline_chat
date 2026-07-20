<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload a profile avatar and return the stored path.
     * Called by chat.js via fetch() before the Livewire save() method.
     * Returns JSON: { path: "profile-images/xxxx.jpg" }
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $request->file('avatar')->store('profile-images', 'public');

        return response()->json(['path' => $path]);
    }

    /**
     * Return read-only profile data for the profile card modal (JSON).
     * Called by chat.js via fetch() when a user clicks another user's avatar.
     *
     * NOTE: `is_online` is a DB-level fallback (last_seen < 2 min).
     * The JS layer overrides this using the live presence Set so the card
     * always reflects the real-time online state.
     */
    public function cardData(\App\Models\User $user): JsonResponse
    {
        $status = $user->status instanceof \App\Enums\UserStatus
            ? $user->status->value
            : ($user->status ?? 'available');

        $isOnline = $user->isOnline();

        return response()->json([
            'id'           => $user->id,
            'name'         => $user->name,
            // Only expose the real status when the user is actually online.
            // If offline, send 'offline' so the card shows the correct state
            // even before the JS presence check runs.
            'status'       => $isOnline ? $status : 'offline',
            'status_label' => $isOnline
                ? \App\Enums\UserStatus::from($status)->label()
                : 'Offline',
            'status_quote' => $user->status_quote ?? '',
            'avatar_url'   => $user->profile_image
                ? Storage::url($user->profile_image)
                : null,
            'initials'     => strtoupper(substr($user->name, 0, 1)),
            // Raw DB status — JS uses this to restore the real status
            // once it confirms the user IS in the presence channel.
            'db_status'       => $status,
            'db_status_label' => \App\Enums\UserStatus::from($status)->label(),
            'is_online_db'    => $isOnline,
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
