<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

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

        User::create([

            'name' => $this->name,

            'email' => $this->email,

            'password' => Hash::make($this->password),

            'is_admin' => $this->is_admin,

        ]);

        session()->flash('success', 'User created successfully.');

        return redirect()->route('users.index');
    }

    public function render()
    {
        return view('livewire.admin.users.create');
    }
}