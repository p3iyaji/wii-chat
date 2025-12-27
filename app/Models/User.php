<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'last_seen_at',
        'role',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_online' => 'boolean',
        ];
    }

    // Update last seen timestamp and set as online
    public function updateLastSeen()
    {
        $this->last_seen_at = now();
        $this->save();

        // Set cache for online status (3 minutes)
        Cache::put('user-online-' . $this->id, true, now()->addMinutes(3));

        // Broadcast online status update
        broadcast(new \App\Events\UserOnlineStatusUpdated($this, true));

        \Log::info("User {$this->id} updated last seen at: " . now());
    }

    // Mark as offline
    public function markAsOffline()
    {
        // Remove from cache
        Cache::forget('user-online-' . $this->id);

        // Broadcast offline status
        broadcast(new \App\Events\UserOnlineStatusUpdated($this, false));

        \Log::info("User {$this->id} marked as offline");
    }

    // Check if user is online (from cache)
    public function getIsOnlineAttribute()
    {
        return Cache::has('user-online-' . $this->id);
    }

    // Get current online status (computed)
    public function getIsOnlineNowAttribute()
    {
        // Check cache first (real-time)
        if (Cache::has('user-online-' . $this->id)) {
            return true;
        }

        // Fallback: check if last seen within 3 minutes
        if ($this->last_seen_at) {
            return $this->last_seen_at->greaterThan(now()->subMinutes(3));
        }

        return false;
    }

    // Get all messages sent by this user
    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    // Get all messages received by this user
    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id');
    }

    public static function boot()
    {
        parent::boot();

        // Clean up cache when user is deleted
        static::deleting(function ($user) {
            Cache::forget('user-online-' . $user->id);
        });
    }

    // Add a method to check all online users
    public static function getOnlineUsers()
    {
        $onlineUsers = [];
        $allUsers = self::all();

        foreach ($allUsers as $user) {
            if ($user->is_online_now) {
                $onlineUsers[] = $user;
            }
        }

        return $onlineUsers;
    }


}