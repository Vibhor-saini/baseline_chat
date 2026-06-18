<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(private ChatService $chatService) {}

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user',
        ]);

        $this->chatService->sendWelcomeMessage($user);

        return $user;
    }

    public function getAllUsers(): LengthAwarePaginator
    {
        return User::orderBy('created_at', 'desc')->paginate(10);
    }

    public function deleteUser(User $user): void
    {
        foreach ($user->conversations as $conversation) {
            $conversation->messages()->delete();
            $conversation->users()->detach();
            $conversation->delete();
        }

        $user->delete();
    }
}
