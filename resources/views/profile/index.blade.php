@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col p-8 bg-teams-bg overflow-y-auto">
    <div class="max-w-2xl mx-auto w-full">
        <a href="{{ route('dashboard') }}"
            wire:navigate
            class="flex items-center gap-2 text-teams-text-secondary hover:text-white transition-colors mb-6 group text-sm font-medium">

            <i data-lucide="chevron-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>

            <span>Back to Dashboard</span>
        </a>

        <h1 class="text-2xl font-bold mb-8 text-white">Edit Profile</h1>

        @if(session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" class="mb-6 p-4 bg-green-500/10 border border-green-500/20 text-green-400 rounded-md text-sm">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <div class="flex items-center gap-6">
                <div class="relative group">
                    <div class="w-24 h-24 rounded-full bg-teams-accent flex items-center justify-center text-3xl font-bold overflow-hidden border-2 border-teams-border">
                        @if(Auth::user()->avatar)
                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" class="w-full h-full object-cover">
                        @else
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <label class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 cursor-pointer rounded-full transition-opacity">
                        <i data-lucide="camera" class="w-6 h-6 text-white"></i>
                        <input type="file" name="avatar" class="hidden">
                    </label>
                </div>
                <div>
                    <h3 class="font-semibold text-white text-lg">Profile Picture</h3>
                    <p class="text-teams-text-secondary text-sm">PNG or JPG, max 2MB.</p>
                </div>
            </div>

            <div class="grid gap-6 bg-teams-secondary p-6 rounded-lg border border-teams-border">
                <div>
                    <label class="block text-xs font-bold text-teams-text-secondary uppercase mb-2">Display Name</label>
                    <input type="text" name="name" value="{{ Auth::user()->name }}" class="w-full bg-teams-bg border border-teams-border rounded px-4 py-2 text-sm focus:border-teams-accent outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-teams-text-secondary uppercase mb-2">Status Message</label>
                    <input type="text" name="status_quote" value="{{ Auth::user()->status_quote }}" placeholder="What's on your mind?" class="w-full bg-teams-bg border border-teams-border rounded px-4 py-2 text-sm focus:border-teams-accent outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-teams-text-secondary uppercase mb-2">Email Address (Read Only)</label>
                    <input type="text" value="{{ Auth::user()->email }}" disabled class="w-full bg-teams-hover border border-teams-border rounded px-4 py-2 text-sm text-teams-text-secondary cursor-not-allowed">
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="bg-teams-accent text-white px-8 py-2 rounded-sm font-semibold hover:opacity-90 transition-all shadow-md">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection