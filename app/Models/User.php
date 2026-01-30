<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'avatar_initials',
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
            'password' => 'hashed',
        ];
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'tenant_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'user_id');
    }

    /**
     * Get user initials from name (First + Last)
     */
    public function getInitialsAttribute(): string
    {
        if ($this->avatar_initials) {
            return $this->avatar_initials;
        }

        $nameParts = explode(' ', trim($this->name));
        if (count($nameParts) >= 2) {
            return strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Get full avatar URL or null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset($this->avatar) : null;
    }
}
