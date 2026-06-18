@extends('layouts.app')

@section('content')
<div class="flex h-full">
    <div class="w-80 bg-teams-secondary border-r border-teams-border flex flex-col shrink-0">
        <div class="p-4 flex justify-between items-center shrink-0">
            <h2 class="text-xl font-bold text-white">Chat</h2>
            <button type="button" class="p-1.5 hover:bg-teams-hover rounded text-teams-text-secondary">
                <i data-lucide="filter" class="w-4 h-4"></i>
            </button>
        </div>

        @livewire('chat-sidebar')
    </div>

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @livewire('chat-box')
    </div>
</div>
@endsection
