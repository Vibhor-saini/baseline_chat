<div class="flex flex-col h-full bg-teams-bg relative" 
     x-data="{ 
        scrollToBottom() { 
            $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight; 
        } 
     }" 
     x-init="scrollToBottom();"
     @refresh-icons.window="lucide.createIcons()"
     @scroll-bottom.window="setTimeout(() => scrollToBottom(), 50)">

    @if($conversation)
        @php
            $receiver = $conversation->users->where('id', '!=', auth()->id())->first();
        @endphp

        <header class="h-14 border-b border-teams-border flex items-center justify-between px-6 shrink-0 bg-teams-bg/80 backdrop-blur-md sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-teams-accent flex items-center justify-center text-xs font-bold overflow-hidden border border-teams-border">
                    @if($receiver->avatar)
                        <img src="{{ asset('storage/'.$receiver->avatar) }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr($receiver->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">{{ $receiver->name }}</h2>
                    <span class="text-[10px] text-green-500 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> Available
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-teams-text-secondary">
                <i data-lucide="video" class="w-4 h-4 cursor-pointer hover:text-white transition-colors"></i>
                <i data-lucide="phone" class="w-4 h-4 cursor-pointer hover:text-white transition-colors"></i>
                <i data-lucide="more-horizontal" class="w-4 h-4 cursor-pointer hover:text-white transition-colors"></i>
            </div>
        </header>

        <div x-ref="messageContainer" class="messages-container flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar flex flex-col scroll-smooth">
            @foreach($conversation->messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp

                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} mb-2">
                    <div class="max-w-[70%] flex gap-3 {{ $isMine ? 'flex-row-reverse' : '' }}">
                        @if(!$isMine)
                            <div class="w-7 h-7 rounded-full bg-teams-accent shrink-0 flex items-center justify-center text-[10px] font-bold overflow-hidden mt-1 shadow-sm">
                                @if($message->sender->avatar)
                                    <img src="{{ asset('storage/'.$message->sender->avatar) }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                                @endif
                            </div>
                        @endif

                        <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                            <div class="px-4 py-2 rounded-lg text-sm shadow-sm transition-all
                                {{ $isMine ? 'bg-teams-accent text-white rounded-tr-none' : 'bg-teams-secondary text-white rounded-tl-none border border-teams-border' }}">
                                {{ $message->body }}
                            </div>
                            <span class="text-[9px] text-teams-text-secondary mt-1 px-1">
                                {{ $message->created_at->format('h:i A') }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4 bg-teams-bg shrink-0 border-t border-teams-border/30">
            <div class="bg-teams-secondary border border-teams-border rounded-lg shadow-2xl transition-all focus-within:border-teams-accent/50">
                
                <div class="px-2 py-1.5 border-b border-teams-border/50 flex items-center gap-1">
                    <button type="button" class="p-1.5 hover:bg-teams-hover rounded text-teams-text-secondary hover:text-white transition-colors">
                        <i data-lucide="type" class="w-4 h-4"></i>
                    </button>
                    <button type="button" class="p-1.5 hover:bg-teams-hover rounded text-teams-text-secondary hover:text-white transition-colors">
                        <i data-lucide="paperclip" class="w-4 h-4"></i>
                    </button>
                    <button type="button" class="p-1.5 hover:bg-teams-hover rounded text-teams-text-secondary hover:text-white transition-colors">
                        <i data-lucide="smile" class="w-4 h-4"></i>
                    </button>
                    <button type="button" class="p-1.5 hover:bg-teams-hover rounded text-teams-text-secondary hover:text-white transition-colors">
                        <span class="text-[10px] font-bold border border-teams-text-secondary px-0.5 rounded">GIF</span>
                    </button>
                    <div class="w-[1px] h-4 bg-teams-border mx-1"></div>
                    <button type="button" class="p-1.5 hover:bg-teams-hover rounded text-teams-text-secondary hover:text-white transition-colors">
                        <i data-lucide="image" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="flex items-end px-3 py-2.5 gap-2">
                    <textarea
                        wire:model="messageBody"
                        wire:keydown.enter.prevent="sendMessage"
                        placeholder="Type a message"
                        rows="1"
                        x-on:keydown.enter="setTimeout(() => scrollToBottom(), 100)"
                        class="flex-1 bg-transparent border-none text-[14px] text-white focus:ring-0 outline-none resize-none py-1 custom-scrollbar placeholder:text-teams-text-secondary/50"></textarea>

                    <div class="flex items-center gap-1">
                        <button
                            wire:click="sendMessage"
                            wire:loading.attr="disabled"
                            class="p-2 {{ empty(trim($messageBody)) ? 'text-teams-text-secondary opacity-50 cursor-not-allowed' : 'text-teams-accent hover:bg-teams-accent/10' }} rounded-full transition-all"
                            {{ empty(trim($messageBody)) ? 'disabled' : '' }}>
                            
                            <div wire:loading wire:target="sendMessage" class="w-5 h-5 border-2 border-teams-accent border-t-transparent rounded-full animate-spin"></div>
                            <i wire:loading.remove wire:target="sendMessage" data-lucide="send-horizontal" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-2 flex justify-end">
                <p class="text-[10px] text-teams-text-secondary opacity-50 italic">Press Enter to send</p>
            </div>
        </div>

    @else
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center bg-teams-bg">
            <div class="mb-8 relative">
                <div class="w-20 h-20 bg-teams-accent/10 rounded-full flex items-center justify-center border border-teams-accent/20">
                    <i data-lucide="messages-square" class="w-10 h-10 text-teams-accent"></i>
                </div>
                <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-teams-secondary border border-teams-border rounded-full flex items-center justify-center shadow-lg">
                    <i data-lucide="plus" class="w-3.5 h-3.5 text-teams-text-secondary"></i>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-white mb-3">Welcome, {{ auth()->user()->name }}!</h2>
            <p class="text-teams-text-secondary text-sm leading-relaxed mb-8 max-w-md">
                Connect with your team members. Select a chat from the sidebar or search for someone to start a conversation.
            </p>

            <button class="px-6 py-2.5 bg-teams-accent text-white rounded-sm text-sm font-semibold hover:bg-opacity-90 transition-all shadow-md active:scale-95">
                Find people
            </button>
        </div>
    @endif
</div>