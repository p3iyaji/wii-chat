<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_seen_at',
        'is_online',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    public function getIsOnlineNowAttribute()
    {
        if (!$this->last_seen_at) {
            return false;
        }

        // Consider online if last activity was within 3 minutes
        return $this->last_seen_at->greaterThan(now()->subMinutes(3));
    }

    // Update last seen timestamp
    public function updateLastSeen()
    {
        $this->last_seen_at = now();
        $this->is_online = true;
        $this->save();
    }

    // Mark as offline
    public function markAsOffline()
    {
        $this->is_online = false;
        $this->save();
    }

}
