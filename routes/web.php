<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Chat\Index as ChatIndex;

use App\Http\Controllers\PresenceController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('chat.index');
    }
    return view('auth.login');
});

// ── Account disabled page (no auth required — user is logged out already) ──
Route::get('/account-disabled', function () {
    return view('auth.account-disabled');
})->name('account.disabled');

// ── Authenticated routes — check user is active on every request ──────────
Route::middleware(['auth', 'active'])->group(function () {

    // Presence ping — updates last_seen, called by chat.js
    Route::post('/presence/ping', [PresenceController::class, 'ping'])->name('presence.ping');

    // ── Admin routes ──────────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {

        Route::get('/dashboard', Dashboard::class)
            ->name('dashboard');

        Route::get('/users', UsersIndex::class)
            ->name('users.index');

        Route::get('/users/create', UsersCreate::class)
            ->name('users.create');

        Route::get('/users/{user}/edit', UsersEdit::class)
            ->name('users.edit');
    });

    // ── Regular user routes ───────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');

    Route::get('/chat', ChatIndex::class)->name('chat.index');

    Route::get('/user/{user}/profile-card', [ProfileController::class, 'cardData'])
        ->name('user.profile-card');
});

require __DIR__ . '/auth.php';

// Password strength check — secured with token validation
// Only allows checking password during active password reset flow
Route::post('/check-password-match', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8',
    ]);

    // Verify the password reset token is valid
    $tokenRecord = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->first();

    // If no valid token exists, deny the check (prevents oracle attack)
    if (!$tokenRecord) {
        return response()->json(['same' => false]);
    }

    // Verify the token matches and hasn't expired
    if (!\Illuminate\Support\Facades\Hash::check($request->token, $tokenRecord->token)) {
        return response()->json(['same' => false]);
    }

    // Check if token is expired (tokens expire after 60 minutes by default)
    $expiresAt = \Carbon\Carbon::parse($tokenRecord->created_at)->addMinutes(60);
    if (now()->isAfter($expiresAt)) {
        return response()->json(['same' => false]);
    }

    // Token is valid - now check if new password matches old password
    $user = \App\Models\User::where('email', $request->email)->first();
    if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['same' => true]);
    }

    return response()->json(['same' => false]);
})->middleware('throttle:30,1')->name('password.check.same');
