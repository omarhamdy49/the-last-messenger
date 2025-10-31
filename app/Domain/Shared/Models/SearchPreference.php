<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Search Preference Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property array|null $default_filters
 * @property string $default_sort
 * @property string $default_order
 * @property int $default_per_page
 * @property bool $show_facets
 * @property bool $highlight_results
 * @property string $language
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SearchPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module',
        'default_filters',
        'default_sort',
        'default_order',
        'default_per_page',
        'show_facets',
        'highlight_results',
        'language',
    ];

    protected $casts = [
        'default_filters' => 'array',
        'default_per_page' => 'integer',
        'show_facets' => 'boolean',
        'highlight_results' => 'boolean',
    ];

    /**
     * Get the user who owns this preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: By user and module
     */
    public function scopeByUserAndModule($query, int $userId, string $module)
    {
        return $query->where('user_id', $userId)->where('module', $module);
    }
}
