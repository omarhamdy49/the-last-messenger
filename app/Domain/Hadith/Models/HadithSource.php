<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HadithSource Model
 *
 * Represents source references for Hadiths (e.g., Sahih Bukhari 1234, Muslim 567)
 * Stores source book name, reference codes, URLs, and verification metadata.
 *
 * @property int $id
 * @property int $hadith_id Reference to the Hadith
 * @property string|null $title Source book/site title
 * @property string|null $reference Reference code/number (e.g., "1234")
 * @property array $name Translatable name (ar/en)
 * @property string|null $description Source description
 * @property string|null $type Source type (book, manuscript, digital, etc.)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithSource extends Model
{
    use HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_sources';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hadith_id',
        'local_source_id',
        'source_file',
        'source_type',
        'title',
        'author',
        'reference',
        'url',
        'sunnah_urn',
        'verification_method',
        'verified_at',
        'name',
        'description',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'sunnah_urn' => 'integer',
        'verified_at' => 'datetime',
        'name' => 'array',
        'description' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTranslationModelClass(): string
    {
        return HadithSourceTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * Get the Hadith this source belongs to
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Scope: Filter by source book
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBook($query, string $book)
    {
        return $query->where('title', 'like', "%{$book}%");
    }

    /**
     * Scope: Filter by reference code
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByReferenceCode($query, string $code)
    {
        return $query->where('reference', $code);
    }

    /**
     * Scope: Filter by type
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    /**
     * Get full source reference (e.g., "Sahih Bukhari 1234")
     */
    public function getFullReferenceAttribute(): string
    {
        $parts = array_filter([
            $this->title,
            $this->reference,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get formatted reference with Arabic
     */
    public function getFormattedReferenceAttribute(): string
    {
        $parts = array_filter([
            $this->title,
            $this->reference,
            $this->url,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Check if source has Arabic reference
     */
    public function hasArabicReference(): bool
    {
        return false;
    }

    /**
     * Get source name accessor (backward compatibility)
     * Prioritizes title field over translatable name
     */
    public function getNameAttribute(): array
    {
        if (! empty($this->title)) {
            return [
                'ar' => $this->title,
                'en' => $this->title,
            ];
        }

        return $this->attributes['name'] ?? ['ar' => '', 'en' => ''];
    }
}
