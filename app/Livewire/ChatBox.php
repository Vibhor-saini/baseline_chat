<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatBox extends Component
{
    public $activeConversationId;

    public $messageBody = '';

    public function getListeners()
    {
        if (! $this->activeConversationId) {
            return ['conversationSelected' => 'loadChat'];
        }

        return [
            "echo-private:chat.{$this->activeConversationId},MessageSent" => '$refresh',
            'conversationSelected' => 'loadChat',
        ];
    }

    #[On('conversationSelected')]
    public function loadChat($id)
    {
        if (! auth()->user()->isMemberOfConversation($id)) {
            return;
        }

        $this->activeConversationId = $id;

        Message::where('conversation_id', $id)
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('scroll-bottom');
    }

    public function sendMessage()
    {
        $this->validate([
            'messageBody' => 'required|string|max:5000',
        ]);

        if (! $this->activeConversationId || ! auth()->user()->isMemberOfConversation($this->activeConversationId)) {
            return;
        }

        $msg = Message::create([
            'conversation_id' => $this->activeConversationId,
            'sender_id' => auth()->id(),
            'body' => trim($this->messageBody),
        ]);

        $this->messageBody = '';
        broadcast(new MessageSent($msg))->toOthers();
        $this->dispatch('scroll-bottom');
    }

    public function render()
    {
        $conversation = null;

        if ($this->activeConversationId && auth()->user()->isMemberOfConversation($this->activeConversationId)) {
            $conversation = Conversation::with(['messages.sender', 'users'])->find($this->activeConversationId);
        }

        return view('livewire.chat-box', ['conversation' => $conversation]);
    }
}
