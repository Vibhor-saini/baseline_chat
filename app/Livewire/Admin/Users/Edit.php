<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Events\AccountSuspended;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_admin = false;

    public string $account_status = 'active'; // S-4: active or suspended

    public function mount(User $user)
    {
        $this->user = $user;

        $this->name = $user->name;

        $this->email = $user->email;

        $this->is_admin = $user->is_admin;

        $this->account_status = $user->account_status ?? 'active';
    }

    protected function rules()
    {
        return [

            'name' => ['required', 'min:3'],

            'email' => ['required', 'email', 'unique:users,email,' . $this->user->id],

            'account_status' => ['required', 'in:active,suspended'],

        ];
    }

    public function update()
    {
        $this->validate();

        $wasActive = $this->user->account_status === 'active';
        $nowSuspended = $this->account_status === 'suspended';

        $data = [

            'name' => $this->name,

            'email' => $this->email,

            'is_admin' => $this->is_admin,

            'account_status' => $this->account_status,

        ];

        if ($this->password) {

            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);

        // S-4: Broadcast suspension event for instant disconnect
        if ($wasActive && $nowSuspended) {
            broadcast(new AccountSuspended(
                $this->user->id,
                'Your account has been suspended by an administrator'
            ));
        }

        session()->flash('success', 'User updated successfully.');

        return $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.edit');
    }
}