<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use Database\Factories\Quran\VerseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An ayah. The primary key is the QF verse id (1–6236 in mushaf order); the public
 * identity is `verse_key` ("2:255").
 *
 * @property int $id
 * @property int $chapter_id
 * @property int $verse_number
 * @property string $verse_key
 * @property int $verse_index
 * @property int $juz_number
 * @property int $hizb_number
 * @property int $rub_el_hizb_number
 * @property int $ruku_number
 * @property int $manzil_number
 * @property int $page_number
 * @property string|null $sajdah_type
 * @property int|null $sajdah_number
 * @property string|null $image_url
 * @property string|null $image_path
 * @property int|null $image_width
 * @property string|null $text_uthmani
 * @property string|null $text_uthmani_simple
 * @property string|null $text_uthmani_tajweed
 * @property string|null $text_imlaei
 * @property string|null $text_imlaei_simple
 * @property string|null $text_indopak
 * @property string|null $text_indopak_nastaleeq
 * @property string|null $text_qpc_hafs
 * @property string|null $text_qpc_nastaleeq
 * @property string|null $code_v1
 * @property string|null $code_v2
 * @property int|null $v1_page
 * @property int|null $v2_page
 * @property bool $is_locked
 */
class Verse extends Model
{
    /** @use HasFactory<VerseFactory> */
    use HasFactory;

    public const COUNT = 6236;

    /**
     * Script and glyph columns — the ones an editor lock protects from re-import.
     *
     * @var list<string>
     */
    public const TEXT_COLUMNS = [
        'text_uthmani', 'text_uthmani_simple', 'text_uthmani_tajweed', 'text_imlaei', 'text_imlaei_simple',
        'text_indopak', 'text_indopak_nastaleeq', 'text_qpc_hafs', 'text_qpc_nastaleeq', 'code_v1', 'code_v2',
    ];

    protected $table = 'quran_verses';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id', 'chapter_id', 'verse_number', 'verse_key', 'verse_index',
        'juz_number', 'hizb_number', 'rub_el_hizb_number', 'ruku_number', 'manzil_number', 'page_number',
        'sajdah_type', 'sajdah_number', 'image_url', 'image_path', 'image_width',
        'text_uthmani', 'text_uthmani_simple', 'text_uthmani_tajweed', 'text_imlaei', 'text_imlaei_simple',
        'text_indopak', 'text_indopak_nastaleeq', 'text_qpc_hafs', 'text_qpc_nastaleeq',
        'code_v1', 'code_v2', 'v1_page', 'v2_page', 'is_locked',
    ];

    protected $casts = [
        'id' => 'integer',
        'chapter_id' => 'integer',
        'verse_number' => 'integer',
        'verse_index' => 'integer',
        'juz_number' => 'integer',
        'hizb_number' => 'integer',
        'rub_el_hizb_number' => 'integer',
        'ruku_number' => 'integer',
        'manzil_number' => 'integer',
        'page_number' => 'integer',
        'sajdah_number' => 'integer',
        'image_width' => 'integer',
        'v1_page' => 'integer',
        'v2_page' => 'integer',
        'is_locked' => 'boolean',
    ];

    protected static function newFactory(): VerseFactory
    {
        return VerseFactory::new();
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function words(): HasMany
    {
        return $this->hasMany(Word::class, 'verse_id')->orderBy('position');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'verse_id');
    }

    public function tafsirs(): HasMany
    {
        return $this->hasMany(Tafsir::class, 'verse_id');
    }

    public function audioFiles(): HasMany
    {
        return $this->hasMany(AudioFile::class, 'verse_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInChapter(Builder $query, int $chapterId): Builder
    {
        return $query->where('chapter_id', $chapterId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInSection(Builder $query, SectionType $type, int $number): Builder
    {
        return $query->where($type->verseColumn(), $number);
    }

    /**
     * Default-mushaf page. Other mushafs resolve pages through quran_mushaf_pages.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOnPage(Builder $query, int $page): Builder
    {
        return $query->where('page_number', $page);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSajdah(Builder $query): Builder
    {
        return $query->whereNotNull('sajdah_number');
    }

    public function textFor(Script $script): ?string
    {
        return $this->getAttribute($script->value);
    }

    /**
     * QF tajweed HTML reduced to `<tajweed class>` / `<span class>` markup: every other
     * tag and attribute is removed, so the result is safe to print unescaped.
     */
    public static function sanitizeTajweed(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return (string) preg_replace_callback(
            '/<(tajweed|span)\b([^>]*)>/i',
            static function (array $m): string {
                // Quran Foundation writes the attribute unquoted — `<tajweed class=slnt>` —
                // so a quotes-only pattern dropped every class and left bare <tajweed> tags
                // that colour nothing.
                preg_match('/\bclass\s*=\s*(?:"([a-z0-9_ -]*)"|\'([a-z0-9_ -]*)\'|([a-z0-9_-]+))/i', $m[2], $class);
                $value = ($class[1] ?? '') !== '' ? $class[1] : ((($class[2] ?? '') !== '') ? $class[2] : ($class[3] ?? ''));

                return '<'.strtolower($m[1]).($value !== '' ? ' class="'.$value.'"' : '').'>';
            },
            strip_tags($html, '<tajweed><span>'),
        );
    }

    /**
     * Parse "2:255" into [2, 255], or null when malformed.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function parseKey(string $key): ?array
    {
        if (preg_match('/^(\d{1,3}):(\d{1,3})$/', trim($key), $m) !== 1) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }
}
