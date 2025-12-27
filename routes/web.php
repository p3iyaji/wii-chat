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
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'last_seen_at' => $user->last_seen_at ? $user->last_seen_at->format('Y-m-d H:i:s') : null,
                    'is_online' => $user->is_online,
                    'is_online_now' => $user->is_online_now,
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

        return response()->json(['success' => true]);
    });

    Route::post('/user/offline', function () {
        $user = auth()->user();
        $user->markAsOffline();

        return response()->json(['success' => true]);
    });

    // USER MANAGEMENT ROUTES (Admin only)
    // Delete user
    Route::delete('/users/{user}', function (User $user) {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.'
            ], 403);
        }

        try {
            DB::transaction(function () use ($user) {
                // Delete user's messages
                ChatMessage::where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id)
                    ->delete();

                // Delete the user
                $user->delete();
            });

            \Log::info("User {$user->id} ({$user->name}) deleted by admin " . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user. Please try again.'
            ], 500);
        }
    })->name('users.delete');

    // Create new user (admin only)
    Route::post('/users', function () {
        request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,user'
        ]);

        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make(request('password')),
            'role' => request('role')
        ]);

        return response()->json([
            'success' => true,
            'user' => $user,
            'message' => 'User created successfully.'
        ]);
    });

    // Update user
    Route::put('/users/{user}', function (User $user) {
        request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,user'
        ]);

        $user->update([
            'name' => request('name'),
            'email' => request('email'),
            'role' => request('role')
        ]);

        return response()->json([
            'success' => true,
            'user' => $user,
            'message' => 'User updated successfully.'
        ]);
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
            ->with(['sender', 'receiver', 'repliedTo'])
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
            ->with(['sender', 'receiver', 'repliedTo']) // Add 'repliedTo' here
            ->orderBy('created_at', 'asc')
            ->get();
    })->middleware(['auth']);

    Route::post('/messages/{friend}', function (User $friend) {
        request()->validate([
            'message' => 'required|string|max:1000',
            'reply_to' => 'nullable|exists:chat_messages,id'
        ]);

        $message = ChatMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $friend->id,
            'body' => request()->input('message'),
            'reply_to' => request()->input('reply_to')
        ]);

        // Load all relationships including repliedTo
        $message->load(['sender', 'receiver', 'repliedTo']);

        broadcast(new MessageSent($message));

        return response()->json($message);
    })->middleware(['auth']);


    // Update your typing route in web.php
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


    // DELETE ALL MESSAGES (for both users)
    Route::delete('/messages/{friend}/delete-all', function (User $friend) {
        try {
            $currentUserId = auth()->id();
            $friendId = $friend->id;

            // Count messages before deletion
            $messageCount = ChatMessage::query()
                ->where(function ($query) use ($currentUserId, $friendId) {
                    $query->where('sender_id', $currentUserId)
                        ->where('receiver_id', $friendId);
                })
                ->orWhere(function ($query) use ($currentUserId, $friendId) {
                    $query->where('sender_id', $friendId)
                        ->where('receiver_id', $currentUserId);
                })
                ->count();

            // Permanently delete all messages
            DB::transaction(function () use ($currentUserId, $friendId) {
                ChatMessage::query()
                    ->where(function ($query) use ($currentUserId, $friendId) {
                        $query->where('sender_id', $currentUserId)
                            ->where('receiver_id', $friendId);
                    })
                    ->orWhere(function ($query) use ($currentUserId, $friendId) {
                        $query->where('sender_id', $friendId)
                            ->where('receiver_id', $currentUserId);
                    })
                    ->delete();
            });

            // Create dummy message for broadcast event
            $dummyMessage = new ChatMessage([
                'sender_id' => $currentUserId,
                'receiver_id' => $friendId
            ]);

            // Broadcast to BOTH users that messages are deleted
            broadcast(new MessageSent(
                message: $dummyMessage,
                isClearMessages: true,
                clearedForUserId: null, // null means delete for everyone
                isPermanentDelete: true
            ));

            // Log the action
            \Log::info("User {$currentUserId} permanently deleted {$messageCount} messages with user {$friendId}");

            return response()->json([
                'success' => true,
                'deleted' => $messageCount,
                'message' => "Successfully deleted {$messageCount} messages permanently for both users."
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting all messages: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete messages. Please try again.'
            ], 500);
        }
    })->middleware(['auth']);

    // REFRESH CHAT WITH PAGINATION
    Route::get('/messages/{friend}/refresh', function (User $friend) {
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
            ->with(['sender', 'receiver', 'repliedTo'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Also get user's current online status
        $friend->refresh(); // Refresh the model to get latest data

        return response()->json([
            'messages' => $messages,
            'user' => [
                'id' => $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
                'is_online' => $friend->is_online,
                'last_seen_at' => $friend->last_seen_at
            ],
            'last_updated' => now()->toISOString()
        ]);
    })->middleware(['auth']);

});

require __DIR__ . '/settings.php';
