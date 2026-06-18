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

    public bool   $isOpen         = false;
    public string $name           = '';
    public string $statusQuote    = '';
    public string $status         = 'available';

    /**
     * Base64-encoded image data URI sent from JS on save.
     * Format: "data:image/jpeg;base64,/9j/4AAQ..."
     * Empty string = no new avatar selected.
     */
    public string $avatarBase64   = '';

    public string $successMessage = '';
    public string $saveError      = '';

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
            'name'         => ['required', 'string', 'min:2', 'max:60'],
            'statusQuote'  => ['nullable', 'string', 'max:160'],
            'status'       => ['required', 'in:available,busy,away,dnd'],
            'avatarBase64' => ['nullable', 'string'],
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
            $this->successMessage = '';
            $this->saveError      = '';
            $this->avatarBase64   = '';
        }
    }

    #[On('close-profile-panel')]
    public function closePanel(): void
    {
        $this->isOpen = false;
    }

    public function save(): void
    {
        $this->saveError      = '';
        $this->successMessage = '';

        $this->validate();

        $user    = auth()->user();
        $oldPath = $user->profile_image;
        $newPath = $oldPath;

        try {
            DB::transaction(function () use ($user, $oldPath, &$newPath) {

                // ── Process base64 avatar if provided ─────────────────
                if ($this->avatarBase64 !== '') {
                    // Parse "data:image/jpeg;base64,<data>"
                    if (preg_match('/^data:(image\/\w+);base64,(.+)$/', $this->avatarBase64, $m)) {
                        $mime      = $m[1];                          // e.g. image/jpeg
                        $ext       = explode('/', $mime)[1] ?? 'jpg'; // jpeg → jpeg
                        $ext       = $ext === 'jpeg' ? 'jpg' : $ext;
                        $imageData = base64_decode($m[2]);

                        $filename = 'profile-images/' . uniqid('avatar_', true) . '.' . $ext;
                        Storage::disk('public')->put($filename, $imageData);
                        $newPath = $filename;
                    }
                }

                $user->update([
                    'name'          => $this->name,
                    'status_quote'  => $this->statusQuote,
                    'status'        => $this->status,
                    'profile_image' => $newPath,
                ]);

                // Delete old avatar file after a successful update
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
            return;
        }

        // Refresh the auth user so subsequent renders see fresh DB data
        auth()->setUser($user->fresh());

        // Always broadcast so name / status / avatar propagate to all tabs
        $avatarUrl = $newPath ? Storage::url($newPath) : '';
        broadcast(new UserProfileUpdated(
            auth()->id(),
            $avatarUrl,
            $this->status,
            $this->name,
        ));

        // Clear local state
        $this->avatarBase64   = '';
        $this->successMessage = 'Profile saved!';

        // JS listener in chat.js will update topbar dot/name immediately
        $this->dispatch('profile-saved');
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
