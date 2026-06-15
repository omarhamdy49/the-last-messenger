<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VerseTranslation Model (Quran Verse Translation per language)
 *
 * @property int $id
 * @property int $verse_id
 * @property int|null $resource_id
 * @property string $lang
 * @property string $text
 * @property string|null $text_notashkeel
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VerseTranslation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'quran_verse_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'verse_id',
        'resource_id',
        'lang',
        'text',
        'text_notashkeel',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verse_id' => 'integer',
        'resource_id' => 'integer',
    ];

    /**
     * Get the verse this translation belongs to
     */
    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }

    /**
     * Get the translation resource (edition) this row belongs to.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(TranslationResource::class, 'resource_id');
    }

    /**
     * Scope: Filter by translation resource (edition)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByResource($query, int $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Scope: Filter by language
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLang($query, string $lang)
    {
        return $query->where('lang', $lang);
    }

    /**
     * Scope: Search in text (with or without tashkeel)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $term)
    {
        $supportsFullText = $query->getConnection()->getDriverName() === 'mysql';

        return $query->where(function ($q) use ($term, $supportsFullText) {
            if ($supportsFullText) {
                $q->whereRaw('MATCH(text, text_notashkeel) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);
            }

            $q->orWhere('text', 'LIKE', "%{$term}%")
                ->orWhere('text_notashkeel', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope: Arabic translations
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArabic($query)
    {
        return $query->where('lang', 'ar');
    }

    /**
     * Scope: English translations
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnglish($query)
    {
        return $query->where('lang', 'en');
    }
}
