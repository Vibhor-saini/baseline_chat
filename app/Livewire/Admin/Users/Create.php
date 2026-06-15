<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;

class Create extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $is_admin = false;

    protected function rules()
    {
        return [

            'name' => ['required', 'min:3'],

            'email' => ['required', 'email', 'unique:users,email'],

            'password' => ['required', 'min:6'],

        ];
    }

public function save()
{
    $this->validate();

    $user = User::create([
        'name' => $this->name,
        'email' => $this->email,
        'password' => Hash::make($this->password),
        'is_admin' => $this->is_admin,
    ]);

    $conversation = Conversation::create([
        'user_one_id' => auth()->id(),
        'user_two_id' => $user->id,
        'last_message_at' => now(),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => auth()->id(),
        'body' => 'Welcome to Baseline Chat 👋',
    ]);

    session()->flash('success', 'User created successfully.');

    return $this->redirect(route('users.index'), navigate: true);
}

    public function render()
    {
        return view('livewire.admin.users.create');
    }
}
