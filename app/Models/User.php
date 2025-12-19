<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ad_username',
        'ad_guid',
        'display_name',
        'quota_bytes',
        'used_bytes',
        'is_admin',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'last_login' => 'datetime',
            'password' => 'hashed',
            'quota_bytes' => 'integer',
            'used_bytes' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's folder path on the LaCie drive
     */
    public function getFolderPathAttribute(): string
    {
        $username = $this->ad_username ?: (string) $this->id;
        // Prevent weird characters creating unintended paths
        $username = preg_replace('/[^A-Za-z0-9._-]/', '_', $username);
        return "users/{$username}/files";
    }

    /**
     * Get quota usage percentage
     */
    public function getQuotaUsagePercentageAttribute(): float
    {
        if ($this->quota_bytes === 0) {
            return 0;
        }
        
        return ($this->used_bytes / $this->quota_bytes) * 100;
    }

    /**
     * Check if user is approaching quota limit (>80%)
     */
    public function isApproachingQuotaLimit(): bool
    {
        return $this->quota_usage_percentage > 80;
    }

    /**
     * Check if user has exceeded quota
     */
    public function hasExceededQuota(): bool
    {
        return $this->used_bytes >= $this->quota_bytes;
    }
}
