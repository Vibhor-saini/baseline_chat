<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_admin = false;

    public function mount(User $user)
    {
        $this->user = $user;

        $this->name = $user->name;

        $this->email = $user->email;

        $this->is_admin = $user->is_admin;
    }

    protected function rules()
    {
        return [

            'name' => ['required', 'min:3'],

            'email' => ['required', 'email', 'unique:users,email,' . $this->user->id],

        ];
    }

    public function update()
    {
        $this->validate();

        $data = [

            'name' => $this->name,

            'email' => $this->email,

            'is_admin' => $this->is_admin,

        ];

        if ($this->password) {

            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);

        session()->flash('success', 'User updated successfully.');

        return $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.edit');
    }
}