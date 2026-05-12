<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [

            'totalUsers' => User::count(),

            'adminUsers' => User::where('is_admin', true)->count(),

            'normalUsers' => User::where('is_admin', false)->count(),

        ]);
    }
}