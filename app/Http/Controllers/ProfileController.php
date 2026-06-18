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
     */
    public function cardData(\App\Models\User $user): JsonResponse
    {
        $status = $user->status instanceof \App\Enums\UserStatus
            ? $user->status->value
            : ($user->status ?? 'available');

        return response()->json([
            'id'           => $user->id,
            'name'         => $user->name,
            'status'       => $status,
            'status_label' => \App\Enums\UserStatus::from($status)->label(),
            'status_quote' => $user->status_quote ?? '',
            'avatar_url'   => $user->profile_image
                ? Storage::url($user->profile_image)
                : null,
            'initials'     => strtoupper(substr($user->name, 0, 1)),
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
