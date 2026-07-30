<?php

declare(strict_types=1);

namespace Tests\Unit\Hadith;

use App\Domain\Hadith\Services\ArabicTextNormalizer;
use PHPUnit\Framework\TestCase;

class ArabicTextNormalizerTest extends TestCase
{
    private ArabicTextNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new ArabicTextNormalizer;
    }

    /**
     * The real opening of Sahih al-Bukhari 1, as extracted, must fold to the
     * same string as its undiacritised form — otherwise a reader who types the
     * plain spelling never finds the diacritised text that is actually stored.
     */
    public function test_the_diacritised_bukhari_1_body_folds_to_its_plain_form(): void
    {
        $diacritised = 'إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ، وَإِنَّمَا لِكُلِّ امْرِئٍ مَا نَوَى';
        $plain = 'إنما الأعمال بالنيات، وإنما لكل امرئ ما نوى';

        $this->assertSame(
            $this->normalizer->normalize($plain),
            $this->normalizer->normalize($diacritised),
        );
    }

    /** @dataProvider letterVariants */
    public function test_letter_variants_fold_together(string $a, string $b, string $why): void
    {
        $this->assertSame(
            $this->normalizer->normalize($a),
            $this->normalizer->normalize($b),
            $why,
        );
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function letterVariants(): array
    {
        return [
            'alef hamza above' => ['أحمد', 'احمد', 'أ and ا are the same name'],
            'alef hamza below' => ['إسناد', 'اسناد', 'إ folds to ا'],
            'alef madda' => ['آمن', 'امن', 'آ folds to ا'],
            'ya hamza' => ['امرئ', 'امري', 'ئ folds to ي'],
            'waw hamza' => ['مؤمن', 'مومن', 'ؤ folds to و'],
            'alef maqsura' => ['نوى', 'نوي', 'ى folds to ي'],
            'ta marbuta' => ['صحيحة', 'صحيحه', 'ة folds to ه'],
            'farsi yeh' => ['صحیح', 'صحيح', 'the source carries both yehs'],
            'urdu heh' => ['إسنادہ', 'اسناده', 'Urdu heh goal folds to ha'],
            'keheh' => ['کتاب', 'كتاب', 'Farsi keheh folds to kaf'],
            'tatweel' => ['صــحيح', 'صحيح', 'tatweel is decoration'],
        ];
    }

    public function test_it_strips_invisible_marks_that_would_split_a_word(): void
    {
        $withMarks = "صحيح\u{200F}\u{200B}";

        $this->assertSame('صحيح', $this->normalizer->normalize($withMarks));
    }

    public function test_it_collapses_whitespace_and_decorative_punctuation(): void
    {
        // The expected form carries no hamza: folding is the point.
        $this->assertSame(
            'قال انما الاعمال',
            $this->normalizer->normalize('قال   ‏"‏ إنما الأعمال‏.‏'),
        );
    }

    public function test_latin_locales_are_left_alone(): void
    {
        $body = "Narrated 'Umar bin Al-Khattab";

        $this->assertSame($body, $this->normalizer->forLocale($body, 'en'));
    }

    public function test_arabic_locale_is_normalised(): void
    {
        $this->assertSame(
            'انما الاعمال بالنيات',
            $this->normalizer->forLocale('إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ', 'ar'),
        );
    }
}
