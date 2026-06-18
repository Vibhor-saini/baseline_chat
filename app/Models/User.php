<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'last_seen',
        'profile_image',
        'status_quote',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_admin'          => 'boolean',
        'last_seen'         => 'datetime',
        'status'            => \App\Enums\UserStatus::class,
    ];

    /**
     * A user is considered online if their last_seen is within the last 2 minutes.
     * This is a fallback for the DB-based check; realtime status comes from
     * the presence channel in chat.js.
     */
    public function isOnline(): bool
    {
        return $this->last_seen !== null
            && $this->last_seen->diffInMinutes(now()) < 2;
    }

    /**
     * Human-readable last seen string, e.g. "last seen 5 minutes ago".
     */
    public function lastSeenText(): string
    {
        if ($this->isOnline()) {
            return 'Online';
        }

        if ($this->last_seen === null) {
            return 'Never seen';
        }

        return 'Last seen ' . $this->last_seen->diffForHumans();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function conversationsAsUserOne()
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    public function conversationsAsUserTwo()
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Return the public URL for the user's profile image, or null if none set.
     */
    public function avatarUrl(): ?string
    {
        return $this->profile_image
            ? Storage::url($this->profile_image)
            : null;
    }

    /**
     * Return the Conversation record (any status) between this user and $otherUserId,
     * or null if none exists.
     *
     * Used in the search dropdown to determine what action button to show:
     *   - accepted  → "Open Chat"
     *   - pending + I sent it  → "Request Sent" (disabled)
     *   - pending + I received → "Accept"
     *   - null → "Send Request" / "Start Chat"
     *
     * @param  int  $otherUserId
     * @return Conversation|null
     */
    public function getConversationWith(int $otherUserId): ?Conversation
    {
        return Conversation::query()
            ->where(function ($q) use ($otherUserId) {
                $q->where('user_one_id', $this->id)
                  ->where('user_two_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($otherUserId) {
                $q->where('user_one_id', $otherUserId)
                  ->where('user_two_id', $this->id);
            })
            ->first();
    }

    /**
     * Quick boolean check: does any conversation (any status) exist
     * between this user and $otherUserId?
     *
     * @param  int  $otherUserId
     * @return bool
     */
    public function hasConversationWith(int $otherUserId): bool
    {
        return $this->getConversationWith($otherUserId) !== null;
    }
}