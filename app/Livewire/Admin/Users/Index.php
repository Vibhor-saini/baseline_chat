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

    // ── Custom Confirm Modal ─────────────────────────────
    public bool   $showConfirm    = false;
    public string $confirmAction  = '';   // 'delete' | 'toggle'
    public string $confirmType    = '';   // 'delete' | 'disable' | 'enable'
    public ?int   $confirmUserId  = null;
    public string $confirmTitle   = '';
    public string $confirmMessage = '';
    public string $confirmBtnLabel = '';

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

    // ── Toggle active/inactive ───────────────────────────
    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');
            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'enabled' : 'disabled';
        session()->flash('success', "{$user->name}'s account has been {$status}.");
    }

    // ── Custom confirm modal ─────────────────────────────
    public function openConfirm(string $action, int $userId, string $type): void
    {
        $user = User::findOrFail($userId);

        $this->confirmAction  = $action;
        $this->confirmType    = $type;
        $this->confirmUserId  = $userId;

        if ($action === 'delete') {
            $this->confirmTitle    = 'Delete User?';
            $this->confirmMessage  = "This will permanently delete {$user->name}'s account and all their data. This cannot be undone.";
            $this->confirmBtnLabel = 'Delete';
        } elseif ($type === 'disable') {
            $this->confirmTitle    = 'Disable Account?';
            $this->confirmMessage  = "{$user->name} will be logged out immediately and won't be able to sign in until re-enabled.";
            $this->confirmBtnLabel = 'Disable';
        } else {
            $this->confirmTitle    = 'Enable Account?';
            $this->confirmMessage  = "{$user->name} will be able to sign in again.";
            $this->confirmBtnLabel = 'Enable';
        }

        $this->showConfirm = true;
    }

    public function closeConfirm(): void
    {
        $this->showConfirm    = false;
        $this->confirmAction  = '';
        $this->confirmType    = '';
        $this->confirmUserId  = null;
        $this->confirmTitle   = '';
        $this->confirmMessage = '';
        $this->confirmBtnLabel = '';
    }

    public function executeConfirm(): void
    {
        if (! $this->confirmUserId) return;

        // Capture before closeConfirm() nulls them
        $action = $this->confirmAction;
        $userId = $this->confirmUserId;

        $this->closeConfirm();

        if ($action === 'delete') {
            $this->delete($userId);
        } elseif ($action === 'toggle') {
            $this->toggleActive($userId);
        }
    }
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
