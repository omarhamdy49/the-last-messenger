<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithCommentary extends Model
{
    use HasFactory;

    protected $table = 'hadith_commentaries';

    protected $fillable = [
        'hadith_id',
        'scholar_id',
        'locale',
        'author_name',
        'source_reference',
        'source_url',
        'review_status',
        'text',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'scholar_id' => 'integer',
    ];

    public const SUPPORTED_LANGUAGES = ['ar', 'en', 'ur', 'fr', 'tr'];

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scholar(): BelongsTo
    {
        return $this->belongsTo(HadithScholar::class, 'scholar_id');
    }

    public function scopeForHadith(Builder $query, int $hadithId): Builder
    {
        return $query->where('hadith_id', $hadithId);
    }

    public function scopeByLanguage(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeByAuthor(Builder $query, string $author): Builder
    {
        return $query->where('author_name', 'like', "%{$author}%");
    }

    public function getLanguageCodeAttribute(): string
    {
        return $this->locale;
    }

    public function getAuthorAttribute(): ?string
    {
        return $this->author_name;
    }

    public function getCommentaryTextAttribute(): string
    {
        return $this->text;
    }

    public function getAuthorName(): string
    {
        return $this->author_name ?? 'Unknown Author';
    }

    public static function isSupportedLanguage(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LANGUAGES, true);
    }
}
