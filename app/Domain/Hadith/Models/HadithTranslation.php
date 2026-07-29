<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Shared\Helpers\FullTextSearchHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithTranslation extends Model
{
    use HasFactory;

    protected $table = 'hadith_translations';

    protected $fillable = [
        'hadith_id',
        'locale',
        'text',
        'search_text',
        'translator_name',
        'source_urn',
        'source_url',
        'notes',
    ];

    protected $casts = [
        'hadith_id' => 'integer',
        'source_urn' => 'integer',
    ];

    public const LANG_ARABIC = 'ar';

    public const LANG_ENGLISH = 'en';

    public const LANG_TURKISH = 'tr';

    public const LANG_FRENCH = 'fr';

    public const LANG_URDU = 'ur';

    public static function getSupportedLanguages(): array
    {
        return ['ar', 'en', 'tr', 'fr', 'ur'];
    }

    public function hadith(): BelongsTo
    {
        return $this->belongsTo(Hadith::class, 'hadith_id');
    }

    public function scopeByLanguage($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeArabic($query)
    {
        return $query->where('locale', self::LANG_ARABIC);
    }

    public function scopeEnglish($query)
    {
        return $query->where('locale', self::LANG_ENGLISH);
    }

    public function scopeSearchMatn($query, string $searchTerm)
    {
        return FullTextSearchHelper::apply($query, ['text'], $searchTerm);
    }

    public function scopeSearchMatnLike($query, string $searchTerm)
    {
        return $query->where('text', 'like', "%{$searchTerm}%");
    }

    public function getLanguageCodeAttribute(): string
    {
        return $this->locale;
    }

    public function getUrnAttribute(): ?int
    {
        return $this->source_urn;
    }

    public function isArabic(): bool
    {
        return $this->locale === self::LANG_ARABIC;
    }

    public function isEnglish(): bool
    {
        return $this->locale === self::LANG_ENGLISH;
    }

    public function isRtl(): bool
    {
        return in_array($this->locale, [self::LANG_ARABIC, self::LANG_URDU], true);
    }

    public function getExcerpt(int $length = 150): string
    {
        return mb_substr($this->text, 0, $length).(mb_strlen($this->text) > $length ? '...' : '');
    }
}
