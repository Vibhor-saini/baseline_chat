<?php

namespace App\Livewire\Profile;

use App\Enums\UserStatus;
use App\Events\UserProfileUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class Panel extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC STATE
    |--------------------------------------------------------------------------
    */

    public bool $isOpen         = false;
    public string $name           = '';
    public string $statusQuote    = '';
    public string $status         = 'available';

    /**
     * Stores the server-side path of a pre-uploaded avatar file.
     * Prefixed with '__path:' so save() can distinguish it from a stale
     * base64 string (which caused Livewire's upload pipeline to fire).
     * Set via setAvatarPath() after JS uploads the file to /profile/avatar.
     * Empty string = no new avatar.
     */
    public string $avatarPath     = '';

    public string $successMessage = '';
    public string $saveError      = '';

    public bool $isSaving         = false;
    public bool $isChangingStatus = false;

    // ── Change Password state ────────────────────────────
    public bool   $showPasswordSection  = false;
    public string $currentPassword      = '';
    public string $newPasswordField     = '';
    public string $confirmPasswordField = '';
    public string $passwordSuccess      = '';
    public string $passwordError        = '';

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->syncFromUser();
    }

    private function syncFromUser(): void
    {
        $user              = auth()->user();
        $this->name        = $user->name;
        $this->statusQuote = $user->status_quote ?? '';

        $rawStatus    = $user->status;
        $this->status = $rawStatus instanceof UserStatus
            ? $rawStatus->value
            : ($rawStatus ?? 'available');
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:2', 'max:60'],
            'statusQuote' => ['nullable', 'string', 'max:160'],
            'status'      => ['required', 'in:available,busy,away,dnd'],
            'avatarPath'  => ['nullable', 'string'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    #[On('toggle-profile-panel')]
    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen) {
            $this->syncFromUser();
            $this->resetValidation();
            $this->successMessage       = '';
            $this->saveError            = '';
            $this->avatarPath           = '';
            $this->isSaving             = false;
            $this->isChangingStatus     = false;
            $this->showPasswordSection  = false;
            $this->currentPassword      = '';
            $this->newPasswordField     = '';
            $this->confirmPasswordField = '';
            $this->passwordSuccess      = '';
            $this->passwordError        = '';
        }
    }

    #[On('close-profile-panel')]
    public function closePanel(): void
    {
        $this->isOpen = false;
    }

    /**
     * Called from JS after the avatar file is successfully uploaded via
     * fetch('/profile/avatar'). Stores only the short server-side path
     * (e.g. "profile-images/avatar_xxx.jpg") — no binary data ever enters
     * Livewire's request pipeline.
     */
    public function setAvatarPath(string $path): void
    {
        // Validate path is inside the expected directory
        if (str_starts_with($path, 'profile-images/') && Storage::disk('public')->exists($path)) {
            $this->avatarPath = $path;
        }
    }

    /**
     * Quick status-only change.
     * Busy/Away/DND → status_manually_set = true (presence cannot override)
     * Available     → status_manually_set = false (presence may reset it)
     */
    public function changeStatus(string $newStatus): void
    {
        $allowed = ['available', 'busy', 'away', 'dnd'];
        if (! in_array($newStatus, $allowed, true)) return;

        $this->isChangingStatus = true;
        $this->status           = $newStatus;

        $user     = auth()->user();
        $isManual = $newStatus !== 'available';

        try {
            $user->update([
                'status'              => $newStatus,
                'status_manually_set' => $isManual,
            ]);

            auth()->setUser($user->fresh());

            $avatarUrl = $user->profile_image ? Storage::url($user->profile_image) : '';

            broadcast(new UserProfileUpdated($user->id, $avatarUrl, $newStatus, $user->name));

            $this->dispatch('status-changed', status: $newStatus);

        } catch (\Throwable $e) {
            Log::error('[ProfilePanel] Status change failed: ' . $e->getMessage());
        } finally {
            $this->isChangingStatus = false;
        }
    }

    /**
     * Save name, status quote, status, and optionally the pre-uploaded avatar.
     * No binary data is passed — the avatar path was set via setAvatarPath().
     */
    public function save(): void
    {
        $this->isSaving       = true;
        $this->saveError      = '';
        $this->successMessage = '';

        $this->validate();

        $user    = auth()->user();
        $oldPath = $user->profile_image;
        $newPath = $oldPath;

        try {
            DB::transaction(function () use ($user, $oldPath, &$newPath) {

                // Use pre-uploaded avatar path if one was set
                if ($this->avatarPath !== '' && Storage::disk('public')->exists($this->avatarPath)) {
                    $newPath = $this->avatarPath;
                }

                $isManual = $this->status !== 'available';

                $user->update([
                    'name'                => $this->name,
                    'status_quote'        => $this->statusQuote,
                    'status'              => $this->status,
                    'status_manually_set' => $isManual,
                    'profile_image'       => $newPath,
                ]);

                // Delete old file after successful DB update
                if ($newPath !== $oldPath && $oldPath) {
                    try {
                        Storage::disk('public')->delete($oldPath);
                    } catch (\Throwable $e) {
                        Log::warning('[ProfilePanel] Failed to delete old avatar: ' . $e->getMessage());
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('[ProfilePanel] Save failed: ' . $e->getMessage());
            $this->saveError = 'Failed to save profile. Please try again.';
            $this->isSaving  = false;
            return;
        }

        auth()->setUser($user->fresh());

        $avatarUrl = $newPath ? Storage::url($newPath) : '';
        broadcast(new UserProfileUpdated(auth()->id(), $avatarUrl, $this->status, $this->name));

        $this->avatarPath     = '';
        $this->successMessage = 'Profile saved!';
        $this->isSaving       = false;

        $this->dispatch('profile-saved', status: $this->status, name: $this->name, avatarUrl: $avatarUrl);
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function togglePasswordSection(): void
    {
        $this->showPasswordSection  = ! $this->showPasswordSection;
        $this->currentPassword      = '';
        $this->newPasswordField     = '';
        $this->confirmPasswordField = '';
        $this->passwordSuccess      = '';
        $this->passwordError        = '';
        $this->resetErrorBag(['currentPassword', 'newPasswordField', 'confirmPasswordField']);
    }

    public function changePassword(): void
    {
        $this->passwordError   = '';
        $this->passwordSuccess = '';

        $this->validate([
            'currentPassword'      => ['required'],
            'newPasswordField'     => ['required', 'min:8', 'same:confirmPasswordField'],
            'confirmPasswordField' => ['required'],
        ], [
            'currentPassword.required'      => 'Current password is required.',
            'newPasswordField.required'     => 'New password is required.',
            'newPasswordField.min'          => 'New password must be at least 8 characters.',
            'newPasswordField.same'         => 'Passwords do not match.',
            'confirmPasswordField.required' => 'Please confirm your new password.',
        ]);

        $user = auth()->user();

        if (! \Illuminate\Support\Facades\Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($this->newPasswordField),
        ]);

        $this->currentPassword      = '';
        $this->newPasswordField     = '';
        $this->confirmPasswordField = '';
        $this->showPasswordSection  = false;
        $this->passwordSuccess      = 'Password changed successfully!';
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.profile.panel');
    }
}
