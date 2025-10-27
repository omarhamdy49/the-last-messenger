<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use App\Domain\Blog\Models\Article;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Sahaba\Enums\CompanionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * Sahabi Model (Companion of Prophet Muhammad ﷺ)
 *
 * @property int $id
 * @property string $slug
 * @property array $name
 * @property array|null $full_name
 * @property array|null $kunya
 * @property array|null $laqab
 * @property string $gender
 * @property CompanionStatus $status
 * @property array|null $biography
 * @property array|null $virtues
 * @property array|null $famous_narrations
 * @property string|null $birth_date_hijri
 * @property string|null $death_date_hijri
 * @property \Carbon\Carbon|null $birth_date_gregorian
 * @property \Carbon\Carbon|null $death_date_gregorian
 * @property int|null $age_at_death
 * @property string|null $birth_place
 * @property string|null $death_place
 * @property string|null $burial_place
 * @property int|null $father_id
 * @property int|null $mother_id
 * @property string|null $tribe
 * @property string|null $clan
 * @property string|null $conversion_date_hijri
 * @property int|null $conversion_order
 * @property array|null $roles
 * @property int $hadith_count
 * @property array|null $battles
 * @property string|null $profile_image
 * @property array|null $gallery
 * @property int|null $related_article_id
 * @property int $views_count
 * @property bool $is_featured
 * @property int $importance_rank
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Sahabi extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sahaba';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'full_name',
        'kunya',
        'laqab',
        'gender',
        'status',
        'biography',
        'virtues',
        'famous_narrations',
        'birth_date_hijri',
        'death_date_hijri',
        'birth_date_gregorian',
        'death_date_gregorian',
        'age_at_death',
        'birth_place',
        'death_place',
        'burial_place',
        'father_id',
        'mother_id',
        'tribe',
        'clan',
        'conversion_date_hijri',
        'conversion_order',
        'roles',
        'hadith_count',
        'battles',
        'profile_image',
        'gallery',
        'related_article_id',
        'views_count',
        'is_featured',
        'importance_rank',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'full_name' => 'array',
        'kunya' => 'array',
        'laqab' => 'array',
        'biography' => 'array',
        'virtues' => 'array',
        'famous_narrations' => 'array',
        'birth_date_gregorian' => 'date',
        'death_date_gregorian' => 'date',
        'age_at_death' => 'integer',
        'conversion_order' => 'integer',
        'roles' => 'array',
        'hadith_count' => 'integer',
        'battles' => 'array',
        'gallery' => 'array',
        'status' => CompanionStatus::class,
        'views_count' => 'integer',
        'is_featured' => 'boolean',
        'importance_rank' => 'integer',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = [
        'name',
        'full_name',
        'kunya',
        'laqab',
        'biography',
        'virtues',
        'famous_narrations',
    ];

    /**
     * Get the related article
     *
     * @return BelongsTo
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'related_article_id');
    }

    /**
     * Get father (from people table)
     *
     * @return BelongsTo
     */
    public function father(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'father_id');
    }

    /**
     * Get mother (from people table)
     *
     * @return BelongsTo
     */
    public function mother(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'mother_id');
    }

    /**
     * Get people related to this companion
     *
     * @return HasMany
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'sahaba_id');
    }

    /**
     * Increment views count
     *
     * @param int $amount
     * @return void
     */
    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
    }

    /**
     * Check if companion participated in a battle
     *
     * @param string $battleName
     * @return bool
     */
    public function participatedInBattle(string $battleName): bool
    {
        if (! $this->battles) {
            return false;
        }

        return in_array($battleName, $this->battles, true);
    }

    /**
     * Get display name (combines name and laqab)
     *
     * @param string $language
     * @return string
     */
    public function getDisplayName(string $language = 'ar'): string
    {
        $name = $this->name[$language] ?? $this->name['ar'] ?? '';
        $laqab = $this->laqab[$language] ?? null;

        if ($laqab) {
            return "{$name} {$laqab}";
        }

        return $name;
    }

    /**
     * Scope: Filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param CompanionStatus|string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, CompanionStatus|string $status)
    {
        $statusValue = $status instanceof CompanionStatus ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Scope: Filter by gender
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gender
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope: Filter by tribe
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tribe
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTribe($query, string $tribe)
    {
        return $query->where('tribe', 'like', "%{$tribe}%");
    }

    /**
     * Scope: Participated in battle
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $battle
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParticipatedIn($query, string $battle)
    {
        return $query->whereJsonContains('battles', $battle);
    }

    /**
     * Scope: Featured companions
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Order by importance
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByImportance($query, string $direction = 'desc')
    {
        return $query->orderBy('importance_rank', $direction);
    }

    /**
     * Scope: Early Muslims (by conversion order)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEarlyMuslims($query, int $limit = 10)
    {
        return $query->whereNotNull('conversion_order')
            ->orderBy('conversion_order')
            ->limit($limit);
    }

    /**
     * Scope: Most hadith narrators
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopNarrators($query, int $limit = 10)
    {
        return $query->where('hadith_count', '>', 0)
            ->orderByDesc('hadith_count')
            ->limit($limit);
    }
}

