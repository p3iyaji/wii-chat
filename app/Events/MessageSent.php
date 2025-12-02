<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Changed to ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcastNow // Changed here
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
        Log::info('MessageSent event constructed (ShouldBroadcastNow)', [
            'message_id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'channels' => [
                'chat.' . $message->sender_id,
                'chat.' . $message->receiver_id
            ]
        ]);
    }

    public function broadcastOn(): array
    {
        // Broadcast to both users in the conversation
        return [
            new PrivateChannel('chat.' . $this->message->sender_id),
            new PrivateChannel('chat.' . $this->message->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        Log::info('Broadcasting message data', ['message_id' => $this->message->id]);

        return [
            'message' => $this->message->load('sender'),
        ];
    }

    // Optional: Add this method for immediate broadcasting
    public function broadcastWhen(): bool
    {
        return true;
    }
}