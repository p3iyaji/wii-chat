<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?ChatMessage $message = null,
        public bool $isTypingUpdate = false,
        public ?int $typingUserId = null,
        public bool $isTyping = false,
        public bool $isClearMessages = false,
        public ?int $clearedForUserId = null,
        public bool $isPermanentDelete = false


    ) {
        Log::info('MessageSent event constructed', [
            'message_id' => $message?->id,
            'isTypingUpdate' => $isTypingUpdate,
            'typingUserId' => $typingUserId,
            'isTyping' => $isTyping,
            'isClearMessages' => $isClearMessages
        ]);
    }

    public function broadcastOn(): array
    {
        // If it's a typing update, broadcast to the OTHER user's channel
        if ($this->isTypingUpdate) {
            Log::info('Broadcasting typing update to channel', [
                'channel' => 'chat.' . $this->typingUserId,
                'typingUserId' => $this->typingUserId,
                'isTyping' => $this->isTyping
            ]);
            return [
                new PrivateChannel('chat.' . $this->typingUserId),
            ];
        }

        // If it's a clear messages event
        if ($this->isClearMessages) {
            // Only broadcast to the user who cleared the messages
            return [
                new PrivateChannel('chat.' . $this->clearedForUserId),
            ];
        }

        // Broadcast regular message to both users
        Log::info('Broadcasting message to channels', [
            'sender_channel' => 'chat.' . $this->message->sender_id,
            'receiver_channel' => 'chat.' . $this->message->receiver_id
        ]);
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
        Log::info('Preparing broadcast data', [
            'isTypingUpdate' => $this->isTypingUpdate,
            'isClearMessages' => $this->isClearMessages
        ]);

        if ($this->isTypingUpdate) {
            return [
                'isTypingUpdate' => true,
                'userId' => auth()->id(), // This should be the person who is typing
                'isTyping' => $this->isTyping
            ];
        }

        if ($this->isClearMessages) {
            return [
                'isClearMessages' => true,
                'clearedForUserId' => $this->clearedForUserId
            ];
        }
        return [
            'message' => $this->message->load('sender'),
            'isTypingUpdate' => false,
            'isClearMessages' => false
        ];
    }
}