<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Json\HadithJsonTransformer;
use PHPUnit\Framework\TestCase;

class HadithJsonTransformerTest extends TestCase
{
    public function test_single_hadith_primary_collection_keeps_declared_source_reference(): void
    {
        $transformer = new HadithJsonTransformer;

        $result = $transformer->transform([
            'hadith_text' => [
                'ar' => 'نص الحديث',
                'en' => 'Hadith text',
            ],
            'collections' => [
                [
                    'name_ar' => 'صحيح البخاري',
                    'name_en' => 'Sahih al-Bukhari',
                    'book_name_ar' => 'كتاب بدء الوحي',
                    'book_name_en' => 'Revelation',
                    'chapter_number' => 1,
                    'hadith_number' => '1',
                ],
            ],
            'metadata' => [
                'id' => 100,
                'id_in_book' => 999,
                'chapter_id' => 1,
                'collection' => [
                    'name_ar' => 'صحيح البخاري',
                    'name_en' => 'Sahih al-Bukhari',
                    'total_hadiths' => 7563,
                ],
            ],
        ]);

        $this->assertSame('single_hadith', $result['type']);
        $this->assertSame('1', $result['records'][0]['hadith']['hadith_number']);
    }

    public function test_single_hadith_primary_collection_can_match_by_arabic_name(): void
    {
        $transformer = new HadithJsonTransformer;

        $result = $transformer->transform([
            'hadith_text' => [
                'ar' => 'نص الحديث',
                'en' => 'Hadith text',
            ],
            'collections' => [
                [
                    'name_ar' => 'الأدب المفرد',
                    'name_en' => 'Al-Adab Al-Mufrad (Imam al-Bukhari)',
                    'book_name_ar' => 'كتاب الأدب',
                    'book_name_en' => 'Etiquette and Manners',
                    'hadith_number' => '600',
                ],
            ],
            'metadata' => [
                'id' => 47991,
                'id_in_book' => 602,
                'chapter_id' => 30,
                'collection' => [
                    'name_ar' => 'الأدب المفرد',
                    'name_en' => 'Al-Adab Al-Mufrad',
                    'total_hadiths' => 1326,
                ],
            ],
        ]);

        $this->assertSame('600', $result['records'][0]['hadith']['hadith_number']);
    }
}
