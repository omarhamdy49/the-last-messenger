<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Search History Model
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $query
 * @property string|null $module
 * @property array|null $filters
 * @property array|null $results_meta
 * @property int $results_count
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $searched_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SearchHistory extends Model
{
    use HasFactory;

    protected $table = 'search_history';

    protected $fillable = [
        'user_id',
        'query',
        'module',
        'filters',
        'results_meta',
        'results_count',
        'ip_address',
        'user_agent',
        'searched_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'results_meta' => 'array',
        'results_count' => 'integer',
        'searched_at' => 'datetime',
    ];

    /**
     * Get the user who performed this search
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Recent searches
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('searched_at', 'desc')->limit($limit);
    }

    /**
     * Scope: By module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Popular searches
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->selectRaw('query, module, COUNT(*) as search_count')
            ->groupBy('query', 'module')
            ->orderByDesc('search_count')
            ->limit($limit);
    }
}
