<div class="flex-1 overflow-y-auto px-2 space-y-0.5 custom-scrollbar">
    @forelse($conversations as $conversation)
        @php
            $receiver = $conversation->users->where('id', '!=', auth()->id())->first();
            $lastMessage = $conversation->messages->first();
            // Check if this conversation is currently selected
            $isActive = $activeConversationId == $conversation->id;
        @endphp
        
        <div 
            wire:key="conv-{{ $conversation->id }}"
            wire:click="selectConversation({{ $conversation->id }})"
            class="flex items-center gap-3 p-3 rounded-md cursor-pointer transition-all group relative 
            {{ $isActive ? 'bg-teams-hover border-l-4 border-teams-accent' : 'hover:bg-teams-hover border-l-4 border-transparent' }}"
        >
            <div class="relative shrink-0">
                <div class="w-10 h-10 rounded-full bg-teams-accent flex items-center justify-center text-sm font-bold overflow-hidden border border-teams-border shadow-sm">
                    @if($receiver && $receiver->avatar)
                        <img src="{{ asset('storage/'.$receiver->avatar) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-white">{{ strtoupper(substr($receiver->name ?? '?', 0, 1)) }}</span>
                    @endif
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-teams-secondary rounded-full"></div>
            </div>
            
            <div class="flex-1 min-w-0 text-left">
                <div class="flex justify-between items-center mb-0.5">
                    <h3 class="text-[13px] {{ $isActive ? 'font-bold text-white' : 'font-semibold text-teams-text-secondary group-hover:text-white' }} truncate transition-colors">
                        {{ $receiver->name ?? 'User' }}
                    </h3>
                    <span class="text-[10px] text-teams-text-secondary shrink-0">
                        {{ $lastMessage ? $lastMessage->created_at->format('h:i A') : '' }}
                    </span>
                </div>
                
                <div class="flex items-center justify-between">
                    <p class="text-[12px] {{ $isActive ? 'text-white' : 'text-teams-text-secondary' }} truncate pr-2">
                        {{ $lastMessage->body ?? 'No messages yet' }}
                    </p>
                    
                    @if($conversation->unread_count > 0)
                        <span class="w-4 h-4 bg-red-500 text-[10px] flex items-center justify-center rounded-full text-white font-bold">
                            {{ $conversation->unread_count }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="py-12 text-center">
            <div class="flex justify-center mb-3 opacity-20">
                <i data-lucide="message-square-dashed" class="w-12 h-12 text-white"></i>
            </div>
            <p class="text-[11px] text-teams-text-secondary uppercase font-bold tracking-widest opacity-50">No recent chats</p>
        </div>
    @endforelse
</div>