<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        $users = User::query()
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.users.index', [
            'users' => $users
        ]);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself

        if ($user->id === auth()->id()) {

            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $user->delete();

        session()->flash('success', 'User deleted successfully.');
    }
}
