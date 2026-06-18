<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\Site\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
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
 * @property string|null $avatar_path
 * @property string|null $provider
 * @property string|null $provider_id
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Pin Spatie role/permission resolution to the `web` guard.
     *
     * The same user model is reachable through multiple auth guards (web, sanctum,
     * admin), but roles and permissions are defined once for the `web` guard. This
     * keeps every hasRole()/hasPermissionTo() check resolving against those records,
     * regardless of which guard authenticated the request.
     */
    protected string $guard_name = 'web';

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
        'avatar_path',
        'provider',
        'provider_id',
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
     * Get all bookmarks (favorites) this user has created.
     *
     * A favorite belongs to a user via `user_id`; the bookmarked content is
     * the polymorphic side. (Previously this was incorrectly declared as a
     * morphMany, which never matched any rows.)
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get user's preferred language
     */
    public function getPreferredLanguage(): string
    {
        return $this->language ?? 'ar';
    }

    /**
     * Set user preference
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->update(['preferences' => $preferences]);
    }

    /**
     * Get user preference
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    /**
     * Public URL for the user's avatar, or null when none was uploaded.
     * Avatars live as plain files on the `public` disk (MediaLibrary is not
     * compatible with the custom media table).
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path !== null
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    /**
     * Send the email verification link via the public-site notification, whose
     * signed URL targets the `public.verification.verify` route (this app does
     * not register the framework's default `verification.verify` name).
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
