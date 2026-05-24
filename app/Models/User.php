<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Conversation;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'last_seen' => 'datetime',
        ];
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversations()
    {
        return Conversation::query()

            ->where('status', 'accepted')

            ->where(function ($query) {

                $query->where('user_one_id', $this->id)

                    ->orWhere('user_two_id', $this->id);
            });
    }

/**
 * Check whether the user already has any conversation
 * (accepted OR pending) with the given user.
 */
public function hasConversationWith(int $userId): bool
{
    return Conversation::query()
        ->where(function ($q) use ($userId) {
            $q->where('user_one_id', $this->id)
              ->where('user_two_id', $userId);
        })
        ->orWhere(function ($q) use ($userId) {
            $q->where('user_one_id', $userId)
              ->where('user_two_id', $this->id);
        })
        ->exists();
}
}
