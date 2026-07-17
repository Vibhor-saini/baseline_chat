<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // ── Reset Password Modal ─────────────────────────────
    public bool   $showResetModal   = false;
    public ?int   $resetUserId      = null;
    public string $resetUserName    = '';
    public string $newPassword      = '';
    public string $newPasswordConfirm = '';

    public function render()
    {
        $users = User::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.users.index', [
            'users' => $users
        ]);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user->delete();
        session()->flash('success', 'User deleted successfully.');
    }

    // ── Open reset modal ─────────────────────────────────
    public function openResetModal(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Use the profile panel to change your own password.');
            return;
        }

        $this->resetUserId        = $userId;
        $this->resetUserName      = $user->name;
        $this->newPassword        = '';
        $this->newPasswordConfirm = '';
        $this->showResetModal     = true;
    }

    public function closeResetModal(): void
    {
        $this->showResetModal     = false;
        $this->resetUserId        = null;
        $this->resetUserName      = '';
        $this->newPassword        = '';
        $this->newPasswordConfirm = '';
        $this->resetValidation();
    }

    // ── Confirm reset ────────────────────────────────────
    public function confirmResetPassword(): void
    {
        $this->validate([
            'newPassword' => [
                'required',
                'min:8',
                'same:newPasswordConfirm',
            ],
            'newPasswordConfirm' => ['required'],
        ], [
            'newPassword.required'        => 'New password is required.',
            'newPassword.min'             => 'Password must be at least 8 characters.',
            'newPassword.same'            => 'Passwords do not match.',
            'newPasswordConfirm.required' => 'Please confirm the password.',
        ]);

        $user = User::findOrFail($this->resetUserId);
        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->closeResetModal();
        session()->flash('success', "Password for {$user->name} has been reset successfully.");
    }
}
