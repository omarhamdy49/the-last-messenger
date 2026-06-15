<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Quran;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Once several translation editions can exist per language, the DTO's lang-keyed
 * map must deterministically resolve to the edition flagged is_default — never a
 * non-default edition that merely happens to load last.
 */
class VerseDtoTranslationEditionTest extends TestCase
{
    public function test_lang_map_prefers_default_edition_regardless_of_order(): void
    {
        // Non-default edition is loaded FIRST, default SECOND — default must win.
        $nonDefault = $this->translation('en', 'Pickthall rendering', isDefault: false);
        $default = $this->translation('en', 'Saheeh rendering', isDefault: true);

        $dto = VerseDTO::fromModel($this->verseWith([$nonDefault, $default]));

        $this->assertSame('Saheeh rendering', $dto->translations['en']);
    }

    public function test_default_is_not_overwritten_by_a_later_non_default(): void
    {
        // Default FIRST, non-default SECOND — the non-default must not clobber it.
        $default = $this->translation('en', 'Saheeh rendering', isDefault: true);
        $nonDefault = $this->translation('en', 'Pickthall rendering', isDefault: false);

        $dto = VerseDTO::fromModel($this->verseWith([$default, $nonDefault]));

        $this->assertSame('Saheeh rendering', $dto->translations['en']);
    }

    public function test_arabic_source_text_resolves_to_default_arabic_edition(): void
    {
        $ar = $this->translation('ar', 'بسم الله', isDefault: true);

        $dto = VerseDTO::fromModel($this->verseWith([$ar]));

        $this->assertSame('بسم الله', $dto->textArabic);
    }

    private function translation(string $lang, string $text, bool $isDefault): VerseTranslation
    {
        $resource = new TranslationResource(['language' => $lang, 'is_default' => $isDefault]);

        $row = new VerseTranslation(['lang' => $lang, 'text' => $text]);
        $row->setRelation('resource', $resource);

        return $row;
    }

    /**
     * @param  array<int, VerseTranslation>  $translations
     */
    private function verseWith(array $translations): Verse
    {
        $verse = new Verse([
            'surah_id' => 1,
            'verse_number' => 1,
            'verse_key' => '1:1',
        ]);
        $verse->id = 1; // not fillable; set directly for the DTO's typed id
        $verse->setRelation('translations', new Collection($translations));

        return $verse;
    }
}
