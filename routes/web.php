<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Chat\Index as ChatIndex;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/dashboard', Dashboard::class)
        ->name('dashboard');

    Route::get('/users', UsersIndex::class)
        ->name('users.index');

    Route::get('/users/create', UsersCreate::class)
        ->name('users.create');

    Route::get('/users/{user}/edit', UsersEdit::class)
        ->name('users.edit');

    Route::middleware(['auth'])->group(function () {});
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/chat', ChatIndex::class)
        ->name('chat.index');
});

require __DIR__ . '/auth.php';
