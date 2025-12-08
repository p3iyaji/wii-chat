<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'body',
        'reply_to',
        'deleted_for_user_id',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type'
    ];

    protected $appends = ['file_url', 'is_image', 'is_document'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function repliedTo(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to');
    }

    // Get the full URL for the file
    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }

    // Check if the message contains an image
    public function getIsImageAttribute()
    {
        return $this->type === 'image' && in_array($this->mime_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ]);
    }

    // Check if the message contains a document
    public function getIsDocumentAttribute()
    {
        return $this->type === 'document' && !$this->getIsImageAttribute();
    }
}