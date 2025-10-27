<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $language
 * @property array|null $preferences
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'language',
        'preferences',
        'last_login_at',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    /**
     * Get all favorites for this user (polymorphic relationship)
     *
     * @return MorphMany
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }

    /**
     * Update last login timestamp
     *
     * @return void
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get user's preferred language
     *
     * @return string
     */
    public function getPreferredLanguage(): string
    {
        return $this->language ?? 'ar';
    }

    /**
     * Set user preference
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['preferences' => $preferences]);
    }

    /**
     * Get user preference
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }
}
