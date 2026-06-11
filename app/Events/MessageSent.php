<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;
    public int   $recipientUserId;

    public function __construct(Message $message)
    {
        $message->loadMissing('sender', 'forwardedFrom.sender');

        $this->message = [
            'id'                => $message->id,
            'conversation_id'   => $message->conversation_id,
            'sender_id'         => $message->sender_id,
            'body'              => $message->body,
            'type'              => $message->type,
            'file_path'         => $message->file_path,
            'delivered_at'      => $message->delivered_at?->toISOString(),
            'read_at'           => $message->read_at?->toISOString(),
            'deleted_at'        => null,
            'forwarded_from_id' => $message->forwarded_from_id,
            'forwarded_from'    => $message->forwardedFrom ? [
                'id'        => $message->forwardedFrom->id,
                'body'      => $message->forwardedFrom->body,
                'type'      => $message->forwardedFrom->type,
                'file_path' => $message->forwardedFrom->file_path,
                'sender'    => [
                    'id'   => $message->forwardedFrom->sender->id,
                    'name' => $message->forwardedFrom->sender->name,
                ],
            ] : null,
            'created_at'        => $message->created_at->toISOString(),
            'sender'            => [
                'id'   => $message->sender->id,
                'name' => $message->sender->name,
            ],
        ];

        // Resolve the recipient (the other participant in the conversation).
        // We load only the two ID columns to avoid a heavy join.
        $conversation = Conversation::select('user_one_id', 'user_two_id')
            ->find($message->conversation_id);

        $this->recipientUserId = $conversation
            ? ($conversation->user_one_id === $message->sender_id
                ? $conversation->user_two_id
                : $conversation->user_one_id)
            : 0;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat.' . $this->message['conversation_id']),
        ];

        // Also broadcast on the recipient's private user channel so they
        // receive the event even when they haven't opened this conversation.
        if ($this->recipientUserId > 0) {
            $channels[] = new PrivateChannel('user.' . $this->recipientUserId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
