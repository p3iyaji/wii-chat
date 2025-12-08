<?php

use App\Events\UserOnlineStatusUpdated;
use App\Events\MessageSent;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\ChatFileController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $users = User::where('id', '!=', auth()->id())
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'last_seen_at' => $user->last_seen_at,
                    'is_online' => $user->is_online,
                    'is_online_now' => $user->is_online_now, // Include computed attribute
                ];
            });

        return Inertia::render('Dashboard', [
            'users' => $users
        ]);
    })->name('dashboard');

    // Update user activity endpoint
    Route::post('/user/ping', function () {
        $user = auth()->user();
        $user->updateLastSeen();

        // Broadcast online status to other users
        broadcast(new \App\Events\UserOnlineStatusUpdated($user, true));

        return response()->json(['success' => true]);
    });

    Route::post('/user/offline', function () {
        $user = auth()->user();
        $user->markAsOffline();

        broadcast(new \App\Events\UserOnlineStatusUpdated($user, false));

        return response()->json(['success' => true]);
    });

    Route::get('chat/{friend}', function (User $friend) {
        $messages = ChatMessage::query()
            ->where(function ($query) use ($friend) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $friend->id);
            })
            ->orWhere(function ($query) use ($friend) {
                $query->where('receiver_id', auth()->id())
                    ->where('sender_id', $friend->id);
            })
            // Exclude messages that the current user has deleted
            ->where(function ($query) {
                $query->whereNull('deleted_for_user_id')
                    ->orWhere('deleted_for_user_id', '!=', auth()->id());
            })
            ->with('sender', 'receiver')
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chat', [
            'user' => $friend,
            'initialMessages' => $messages
        ]);
    })->middleware(['auth'])->name('chat');

    Route::get('/messages/{friend}', function (User $friend) {
        return ChatMessage::query()
            ->where(function ($query) use ($friend) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $friend->id);
            })
            ->orWhere(function ($query) use ($friend) {
                $query->where('receiver_id', auth()->id())
                    ->where('sender_id', $friend->id);
            })
            // Exclude messages that the current user has deleted
            ->where(function ($query) {
                $query->whereNull('deleted_for_user_id')
                    ->orWhere('deleted_for_user_id', '!=', auth()->id());
            })
            ->with('sender', 'receiver')
            ->orderBy('created_at', 'asc')
            ->get();
    })->middleware(['auth']);

    Route::post('/messages/{friend}', function (User $friend) {
        request()->validate([
            'message' => 'required|string|max:1000'
        ]);

        $message = ChatMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $friend->id,
            'body' => request()->input('message')
        ]);

        // Load relationships before broadcasting
        $message->load('sender');

        \Log::info('Broadcasting message', [
            'channel' => "chat.{$message->receiver_id}",
            'event' => 'MessageSent',
            'message_id' => $message->id
        ]);

        broadcast(new MessageSent($message))->toOthers();
        \Log::info('Broadcast successful', ['message_id' => $message->id]);

        return response()->json($message);

    })->middleware(['auth']);

    // Typing indicator endpoint
    Route::post('/typing/{friend}', function (User $friend) {
        request()->validate([
            'is_typing' => 'required|boolean'
        ]);

        $isTyping = request()->input('is_typing');

        \Log::info('Typing indicator received', [
            'from' => auth()->id(),
            'to' => $friend->id,
            'isTyping' => $isTyping
        ]);

        // Send typing indicator to the FRIEND's channel
        broadcast(new MessageSent(
            message: new ChatMessage([ // Dummy message
                'sender_id' => auth()->id(),
                'receiver_id' => $friend->id
            ]),
            isTypingUpdate: true,
            typingUserId: $friend->id, // Broadcast to friend's channel
            isTyping: $isTyping
        ));

        return response()->json(['success' => true]);
    })->middleware(['auth']);

    Route::post('/messages/{friend}/upload', [ChatFileController::class, 'upload'])->middleware(['auth']);

    // Clear messages endpoint - only clears for the current user
    Route::delete('/messages/{friend}/clear', function (User $friend) {
        // Mark messages as deleted for current user
        $updated = ChatMessage::query()
            ->where(function ($query) use ($friend) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $friend->id);
            })
            ->orWhere(function ($query) use ($friend) {
                $query->where('receiver_id', auth()->id())
                    ->where('sender_id', $friend->id);
            })
            ->where(function ($query) {
                $query->whereNull('deleted_for_user_id')
                    ->orWhere('deleted_for_user_id', '!=', auth()->id());
            })
            ->update([
                'deleted_for_user_id' => auth()->id()
            ]);

        // Create dummy message for event
        $dummyMessage = new ChatMessage([
            'sender_id' => auth()->id(),
            'receiver_id' => $friend->id
        ]);

        // Broadcast only to the user who cleared
        broadcast(new MessageSent(
            message: $dummyMessage,
            isClearMessages: true,
            clearedForUserId: auth()->id() // This tells the event to only notify current user
        ));

        return response()->json([
            'success' => true,
            'cleared' => $updated,
            'message' => 'Messages cleared for you only. The other user can still see them.'
        ]);
    })->middleware(['auth']);



});

require __DIR__ . '/settings.php';
