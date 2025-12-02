<?php

use App\Events\MessageSent;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    $users = User::where('id', '!=', auth()->id())->get();
    return Inertia::render('Dashboard', [
        'users' => $users
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('chat/{friend}', function (User $friend) {
    // Load initial messages
    $messages = ChatMessage::query()
        ->where(function ($query) use ($friend) {
            $query->where('sender_id', auth()->id())
                ->where('receiver_id', $friend->id);
        })
        ->orWhere(function ($query) use ($friend) {
            $query->where('receiver_id', auth()->id())
                ->where('sender_id', $friend->id);
        })
        ->with('sender', 'receiver')
        ->orderBy('created_at', 'asc') // Use created_at for ordering
        ->get();

    return Inertia::render('Chat', [
        'user' => $friend,
        'initialMessages' => $messages // Changed from 'messages' to 'initialMessages'
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

require __DIR__ . '/settings.php';
