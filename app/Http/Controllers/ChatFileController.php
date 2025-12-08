<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatFileController extends Controller
{
    public function upload(Request $request, User $friend)
    {
        \Log::info('File upload started', [
            'from' => auth()->id(),
            'to' => $friend->id,
            'has_file' => $request->hasFile('file'),
            'file' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'no file'
        ]);

        $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            \Log::info('File details', [
                'name' => $originalName,
                'mime' => $mimeType,
                'size' => $fileSize,
                'extension' => $file->getClientOriginalExtension()
            ]);

            // Determine file type
            $fileType = 'document';
            if (str_starts_with($mimeType, 'image/')) {
                $fileType = 'image';
            }

            // Generate filename
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("chat/{$fileType}s", $filename, 'public');

            \Log::info('File stored', [
                'path' => $path,
                'type' => $fileType
            ]);

            // Create message
            $message = ChatMessage::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $friend->id,
                'type' => $fileType,
                'body' => $fileType === 'image' ? 'Sent an image' : 'Sent a document',
                'file_path' => $path,
                'file_name' => $originalName,
                'file_size' => $this->formatFileSize($fileSize),
                'mime_type' => $mimeType,
                'reply_to' => $request->input('reply_to')
            ]);

            $message->load(['sender', 'repliedTo']);

            \Log::info('Message created', [
                'message_id' => $message->id,
                'type' => $message->type
            ]);

            // Broadcast
            broadcast(new \App\Events\MessageSent($message));

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('Upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}