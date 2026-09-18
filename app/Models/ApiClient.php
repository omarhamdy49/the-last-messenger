<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApiClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * An application allowed to call the public API.
 *
 * @property int $id
 * @property string $name
 * @property string $key public identifier sent as X-Api-Key
 * @property string $secret_hash HMAC-SHA256 of the secret under APP_KEY
 * @property list<string> $scopes
 * @property list<string>|null $allowed_origins
 * @property int $rate_limit_per_minute
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_used_at
 * @property int|null $created_by
 */
class ApiClient extends Model
{
    /** @use HasFactory<ApiClientFactory> */
    use HasFactory;

    public const SCOPES = ['*', 'quran', 'hadith', 'seerah', 'media', 'articles', 'user'];

    protected $fillable = ['name', 'scopes', 'allowed_origins', 'rate_limit_per_minute', 'is_active', 'expires_at', 'created_by'];

    protected $hidden = ['secret_hash'];

    protected $casts = [
        'scopes' => 'array',
        'allowed_origins' => 'array',
        'rate_limit_per_minute' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'created_by' => 'integer',
    ];

    protected static function booted(): void
    {
        // The middleware caches clients by key; any change must be visible at once.
        static::saved(fn (self $client) => Cache::forget(self::cacheKey($client->key)));
        static::deleted(fn (self $client) => Cache::forget(self::cacheKey($client->key)));
    }

    public static function cacheKey(string $key): string
    {
        return 'api_client:'.$key;
    }

    public static function generateKey(): string
    {
        return Str::random(32);
    }

    public static function generateSecret(): string
    {
        return Str::random(48);
    }

    public static function hashSecret(string $secret): string
    {
        return hash_hmac('sha256', $secret, (string) config('app.key'));
    }

    /**
     * Set a new key/secret pair and return the plain secret (shown once, never stored).
     */
    public function issueCredentials(bool $newKey = true): string
    {
        if ($newKey || $this->key === null) {
            $previous = $this->key;
            $this->key = self::generateKey();
            if ($previous !== null) {
                Cache::forget(self::cacheKey($previous));
            }
        }

        $secret = self::generateSecret();
        $this->secret_hash = self::hashSecret($secret);

        return $secret;
    }

    public function secretMatches(string $secret): bool
    {
        return hash_equals($this->secret_hash, self::hashSecret($secret));
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? [];

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }

    /**
     * A request without an Origin header (server-to-server, mobile) is not a
     * browser cross-origin call and is allowed. With an allow-list, the origin must
     * match exactly.
     */
    public function allowsOrigin(?string $origin): bool
    {
        if ($origin === null || $origin === '' || $this->allowed_origins === null || $this->allowed_origins === []) {
            return true;
        }

        return in_array(rtrim($origin, '/'), array_map(static fn (string $o): string => rtrim($o, '/'), $this->allowed_origins), true);
    }

    public function isUsable(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
