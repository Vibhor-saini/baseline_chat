@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col p-6 overflow-y-auto"
    x-data="{ 
        openModal: false, 
        deleteModal: false, 
        userIdToDelete: null,
        name: '', 
        username: '' 
     }">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-semibold text-white tracking-tight">Manage Users</h1>
        <button @click="openModal = true" class="bg-teams-accent text-white px-4 py-1.5 rounded-sm text-sm font-medium hover:opacity-90 transition-all shadow-lg">
            + Add User
        </button>
    </div>

    @if(session('error'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition.opacity.duration.500ms
        class="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4"></i>
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 2000)"
        x-show="show"
        x-transition.opacity.duration.500ms
        class="mb-4 p-3 bg-green-500/10 border border-green-500/20 text-green-400 text-sm rounded flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-teams-secondary border border-teams-border rounded-md overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-teams-hover text-teams-text-secondary text-[11px] uppercase tracking-wider border-b border-teams-border font-bold">
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Email Address</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Joined At</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-teams-border">
                @foreach($users as $user)
                <tr class="hover:bg-teams-hover/40 transition-colors">
                    <td class="px-4 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs text-white shadow-sm overflow-hidden border border-teams-border bg-teams-accent">
                            @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" class="w-full h-full object-cover">
                            @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <div class="font-medium text-white">{{ $user->name }}</div>
                            <div class="text-[11px] text-teams-text-secondary">@ {{ $user->username }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-teams-text-secondary font-light">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-sm text-[10px] font-bold tracking-tighter {{ $user->role === 'admin' ? 'bg-red-500/20 text-red-400' : 'bg-teams-accent/20 text-teams-accent' }}">
                            {{ strtoupper($user->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-teams-text-secondary text-[12px]">{{ $user->created_at->format('d M, Y') }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($user->id !== auth()->id())
                        <button
                            @click="deleteModal = true; userIdToDelete = {{ $user->id }}"
                            class="text-teams-text-secondary hover:text-red-500 transition-colors p-1">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="mt-4">
        {{ $users->links() }}
    </div>
    @endif

    <div x-show="openModal" class="fixed inset-0 bg-black/70 backdrop-blur-[2px] z-50 flex items-center justify-center p-4" x-cloak x-transition>
        <div @click.away="openModal = false" class="bg-teams-secondary border border-teams-border w-full max-w-md rounded-lg shadow-2xl overflow-hidden">
            <div class="p-4 border-b border-teams-border flex justify-between items-center bg-teams-hover/30">
                <h3 class="font-semibold text-white">Create New Enterprise User</h3>
                <button @click="openModal = false" class="text-teams-text-secondary hover:text-white transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-teams-text-secondary mb-1 uppercase font-bold">Full Name</label>
                    <input type="text" name="name" x-model="name"
                        @input="username = name.toLowerCase().replace(/\s+/g, '') + Math.floor(Math.random() * 100)"
                        required
                        class="w-full bg-teams-bg border border-teams-border rounded px-3 py-2 text-sm text-white focus:border-teams-accent outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs text-teams-text-secondary mb-1 uppercase font-bold">Username</label>
                    <input type="text" name="username" x-model="username" readonly
                        class="w-full bg-teams-hover border border-teams-border rounded px-3 py-2 text-sm text-teams-text-secondary outline-none cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs text-teams-text-secondary mb-1 uppercase font-bold">Email Address</label>
                    <input type="email" name="email" required
                        class="w-full bg-teams-bg border border-teams-border rounded px-3 py-2 text-sm text-white focus:border-teams-accent outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-teams-text-secondary mb-1 uppercase font-bold">Password</label>
                        <input type="password" name="password" required
                            class="w-full bg-teams-bg border border-teams-border rounded px-3 py-2 text-sm text-white focus:border-teams-accent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs text-teams-text-secondary mb-1 uppercase font-bold">Role</label>
                        <select name="role" class="w-full bg-teams-bg border border-teams-border rounded px-3 py-2 text-sm text-white focus:border-teams-accent outline-none appearance-none">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="pt-6 flex gap-3">
                    <button type="button" @click="openModal = false" class="flex-1 px-4 py-2 border border-teams-border rounded text-sm text-white hover:bg-teams-hover transition-all">Cancel</button>
                    <button type="submit" class="flex-1 bg-teams-accent px-4 py-2 rounded text-sm font-semibold text-white hover:opacity-90 transition-all shadow-md">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="deleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-[2px] z-[60] flex items-center justify-center p-4" x-cloak x-transition>
        <div @click.away="deleteModal = false" class="bg-teams-secondary border border-teams-border w-full max-w-sm rounded-lg shadow-2xl p-6 text-center">
            <div class="w-14 h-14 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-500/20">
                <i data-lucide="alert-circle" class="w-8 h-8"></i>
            </div>
            <h3 class="text-lg font-bold text-white tracking-tight">Remove User?</h3>
            <p class="text-teams-text-secondary text-sm mt-2 leading-relaxed">Are you sure you want to delete this user? This action will permanently remove their access and chat history.</p>

            <div class="flex gap-3 mt-8">
                <button @click="deleteModal = false" class="flex-1 px-4 py-2 border border-teams-border rounded text-sm text-white hover:bg-teams-hover transition-all">Cancel</button>

                <form :action="'{{ url('admin/users') }}/' + userIdToDelete" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 px-4 py-2 rounded text-sm font-bold text-white hover:bg-red-700 transition-all shadow-md">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection