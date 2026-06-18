<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teams Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    @livewireStyles
</head>
<body class="bg-teams-bg text-white h-screen overflow-hidden flex flex-col font-sans">
    <header class="h-12 bg-teams-secondary border-b border-teams-border flex items-center justify-between px-4 shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                <i data-lucide="layout-grid" class="w-5 h-5 text-teams-text-secondary"></i>
                <span class="font-semibold text-[14px]">Microsoft Teams</span>
            </a>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('profile') }}" wire:navigate class="p-2 rounded-md {{ request()->is('profile') ? 'bg-teams-hover text-teams-accent' : 'text-teams-text-secondary hover:text-white' }}" title="Profile">
                <i data-lucide="user" class="w-5 h-5"></i>
            </a>
            <div class="w-8 h-8 bg-teams-accent rounded-full flex items-center justify-center text-[12px] font-bold border border-teams-border overflow-hidden">
                @if(Auth::user()->avatar)
                    <img src="{{ asset('storage/'.Auth::user()->avatar) }}" alt="" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                @endif
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <nav class="w-16 bg-teams-secondary border-r border-teams-border flex flex-col items-center py-2 shrink-0">
            <a href="{{ route('dashboard') }}" wire:navigate class="p-2 rounded-md {{ request()->is('dashboard') ? 'bg-teams-hover text-teams-accent' : 'text-teams-text-secondary' }}">
                <i data-lucide="message-square" class="w-6 h-6"></i>
            </a>
            @if(Auth::user()->role === 'admin')
            <a href="{{ route('admin.users') }}" wire:navigate class="mt-auto mb-4 p-2 text-teams-text-secondary hover:text-teams-accent">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </a>
            @endif
        </nav>
        <main class="flex-1 flex flex-col overflow-hidden bg-teams-bg">
            @yield('content')
        </main>
    </div>

    @livewireScripts
    <script>
        function initIcons() { lucide.createIcons(); }
        document.addEventListener('livewire:navigated', initIcons);
        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-icons', () => setTimeout(initIcons, 50));
            Livewire.on('scroll-bottom', () => {
                setTimeout(() => {
                    const el = document.querySelector('.messages-container');
                    if(el) el.scrollTop = el.scrollHeight;
                }, 100);
            });
        });
    </script>
</body>
</html>