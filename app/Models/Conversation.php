<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
        'status',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->withTrashed()->latestOfMany();
    }

    /** Count of unread messages from the other user */
    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Return the other participant in the conversation (not the auth user).
     */
    public function otherUser(): User
    {
        return $this->user_one_id === auth()->id()
            ? $this->userTwo
            : $this->userOne;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: conversations that involve a given user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_one_id', $userId)
                     ->orWhere('user_two_id', $userId);
    }

    /**
     * Scope: only accepted conversations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: only pending conversations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}