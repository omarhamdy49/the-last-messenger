<?php

declare(strict_types=1);

namespace Database\Seeders\Hadith;

use App\Domain\Hadith\Models\Collection;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            [
                'name_ar' => 'صحيح البخاري',
                'name_en' => 'Sahih al-Bukhari',
                'name_tr' => 'Sahih-i Buhari',
                'name_fr' => 'Sahih al-Bukhari',
                'name_ur' => 'صحیح بخاری',
                'description_ar' => 'أصح كتاب بعد القرآن الكريم، جمعه الإمام محمد بن إسماعيل البخاري',
                'description_en' => 'The most authentic book after the Quran, compiled by Imam Muhammad ibn Ismail al-Bukhari',
                'compiler' => 'الإمام محمد بن إسماعيل البخاري',
                'death_year_hijri' => 256,
                'is_featured' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name_ar' => 'صحيح مسلم',
                'name_en' => 'Sahih Muslim',
                'name_tr' => 'Sahih-i Muslim',
                'name_fr' => 'Sahih Muslim',
                'name_ur' => 'صحیح مسلم',
                'description_ar' => 'ثاني أصح كتب الحديث بعد صحيح البخاري، جمعه الإمام مسلم بن الحجاج',
                'description_en' => 'The second most authentic hadith collection after Sahih al-Bukhari',
                'compiler' => 'الإمام مسلم بن الحجاج النيسابوري',
                'death_year_hijri' => 261,
                'is_featured' => true,
                'is_active' => true,
                'display_order' => 2,
            ],
        ];

        foreach ($collections as $collection) {
            Collection::create($collection);
        }

        $this->command->info('✅ Collections seeded successfully!');
    }
}
