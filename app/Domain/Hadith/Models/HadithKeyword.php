<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HadithKeyword Model
 *
 * Represents searchable keywords extracted from Hadiths for improved search functionality
 * Stores keywords in both Arabic and English with frequency tracking
 *
 * @property int $id
 * @property int $hadith_id Reference to the Hadith
 * @property string|null $keyword_ar Keyword in Arabic
 * @property string|null $keyword_en Keyword in English
 * @property int $frequency Usage frequency of this keyword
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithKeyword extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_keywords';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hadith_id',
        'keyword_ar',
        'keyword_en',
        'frequency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'frequency' => 'integer',
    ];

    /**
     * Disable timestamps if table doesn't have them
     * (Based on migration, the table doesn't have created_at/updated_at)
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the Hadith this keyword belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Scope: Search by keyword in Arabic
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchArabic($query, string $keyword)
    {
        return $query->where('keyword_ar', 'like', "%{$keyword}%");
    }

    /**
     * Scope: Search by keyword in English
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchEnglish($query, string $keyword)
    {
        return $query->where('keyword_en', 'like', "%{$keyword}%");
    }

    /**
     * Scope: Search by keyword in any language
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('keyword_ar', 'like', "%{$keyword}%")
              ->orWhere('keyword_en', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope: Get popular keywords (high frequency)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minFrequency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $minFrequency = 5)
    {
        return $query->where('frequency', '>=', $minFrequency)
            ->orderByDesc('frequency');
    }

    /**
     * Scope: Order by frequency
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFrequency($query, string $direction = 'desc')
    {
        return $query->orderBy('frequency', $direction);
    }

    /**
     * Scope: Get unique keywords (grouped by keyword)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $language
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnique($query, string $language = 'ar')
    {
        $column = $language === 'ar' ? 'keyword_ar' : 'keyword_en';

        return $query->select($column, \DB::raw('SUM(frequency) as total_frequency'))
            ->groupBy($column)
            ->orderByDesc('total_frequency');
    }

    /**
     * Increment keyword frequency
     *
     * @param  int  $amount
     * @return void
     */
    public function incrementFrequency(int $amount = 1): void
    {
        $this->increment('frequency', $amount);
    }

    /**
     * Get keyword in specific language
     *
     * @param  string  $locale
     * @return string|null
     */
    public function getKeyword(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->keyword_ar : $this->keyword_en;
    }

    /**
     * Get keyword in both languages as array
     *
     * @return array
     */
    public function getKeywordsAttribute(): array
    {
        return [
            'ar' => $this->keyword_ar,
            'en' => $this->keyword_en,
        ];
    }

    /**
     * Check if keyword exists in specific language
     *
     * @param  string  $locale
     * @return bool
     */
    public function hasKeyword(string $locale = 'ar'): bool
    {
        $keyword = $this->getKeyword($locale);

        return !empty($keyword);
    }

    /**
     * Get top keywords globally (static method)
     *
     * @param  int  $limit
     * @param  string  $language
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTopKeywords(int $limit = 50, string $language = 'ar')
    {
        return static::unique($language)->limit($limit)->get();
    }
}
