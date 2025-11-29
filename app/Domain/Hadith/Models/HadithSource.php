<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * HadithSource Model
 *
 * Represents source references for Hadiths (e.g., Sahih Bukhari 1234, Muslim 567)
 * Stores source book name, reference codes, and Arabic references
 *
 * @property int $id
 * @property int $hadith_id Reference to the Hadith
 * @property string|null $source_book Source book name (e.g., "Sahih Bukhari")
 * @property string|null $reference_code Reference code/number (e.g., "1234")
 * @property string|null $arabic_ref Arabic reference
 * @property array $name Translatable name (ar/en)
 * @property string|null $description Source description
 * @property string|null $type Source type (book, manuscript, digital, etc.)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithSource extends Model
{
    use HasTranslations;

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
        'source_book',
        'reference_code',
        'arabic_ref',
        'name',
        'description',
        'type',
    ];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = [
        'name',
        'description',
    ];

    /**
     * Disable timestamps if table doesn't have them
     * (Based on migration, the table doesn't have created_at/updated_at)
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the Hadith this source belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    /**
     * Scope: Filter by source book
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $book
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBook($query, string $book)
    {
        return $query->where('source_book', 'like', "%{$book}%");
    }

    /**
     * Scope: Filter by reference code
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByReferenceCode($query, string $code)
    {
        return $query->where('reference_code', $code);
    }

    /**
     * Scope: Filter by type
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get full source reference (e.g., "Sahih Bukhari 1234")
     *
     * @return string
     */
    public function getFullReferenceAttribute(): string
    {
        $parts = array_filter([
            $this->source_book,
            $this->reference_code,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get formatted reference with Arabic
     *
     * @return string
     */
    public function getFormattedReferenceAttribute(): string
    {
        $parts = array_filter([
            $this->source_book,
            $this->reference_code,
            $this->arabic_ref ? "({$this->arabic_ref})" : null,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Check if source has Arabic reference
     *
     * @return bool
     */
    public function hasArabicReference(): bool
    {
        return !empty($this->arabic_ref);
    }

    /**
     * Get source name accessor (backward compatibility)
     * Prioritizes source_book field over translatable name
     *
     * @return array
     */
    public function getNameAttribute(): array
    {
        if (!empty($this->source_book)) {
            return [
                'ar' => $this->source_book,
                'en' => $this->source_book,
            ];
        }

        return $this->attributes['name'] ?? ['ar' => '', 'en' => ''];
    }
}
