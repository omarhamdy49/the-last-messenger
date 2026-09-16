<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'code' => 'ar',
                'name' => [
                    'ar' => 'العربية',
                    'en' => 'Arabic',
                    'fr' => 'Arabe',
                    'tr' => 'Arapça',
                    'ur' => 'عربی',
                ],
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'flag_icon' => '🇸🇦',
            ],
            [
                'code' => 'en',
                'name' => [
                    'ar' => 'الإنجليزية',
                    'en' => 'English',
                    'fr' => 'Anglais',
                    'tr' => 'İngilizce',
                    'ur' => 'انگریزی',
                ],
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
                'flag_icon' => '🇬🇧',
            ],
            [
                'code' => 'fr',
                'name' => [
                    'ar' => 'الفرنسية',
                    'en' => 'French',
                    'fr' => 'Français',
                    'tr' => 'Fransızca',
                    'ur' => 'فرانسیسی',
                ],
                'native_name' => 'Français',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
                'flag_icon' => '🇫🇷',
            ],
            [
                'code' => 'tr',
                'name' => [
                    'ar' => 'التركية',
                    'en' => 'Turkish',
                    'fr' => 'Turc',
                    'tr' => 'Türkçe',
                    'ur' => 'ترکی',
                ],
                'native_name' => 'Türkçe',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
                'flag_icon' => '🇹🇷',
            ],
            [
                'code' => 'ur',
                'name' => [
                    'ar' => 'الأردية',
                    'en' => 'Urdu',
                    'fr' => 'Ourdou',
                    'tr' => 'Urduca',
                    'ur' => 'اردو',
                ],
                'native_name' => 'اردو',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 5,
                'flag_icon' => '🇵🇰',
            ],
        ];

        foreach ($languages as $language) {
            // `name` is per-locale and lives in language_translations; the
            // languages table itself carries only native_name. Splitting them
            // here keeps this seeder aligned with the translations schema.
            $translations = $language['name'];
            unset($language['name']);

            $language['is_active'] = in_array($language['code'], config('languages.default_enabled'), true);
            $record = Language::firstOrCreate(
                ['code' => $language['code']],
                $language
            );

            foreach ($translations as $locale => $name) {
                $record->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name, 'is_ready' => true]
                );
            }
        }

        $this->command->info('✅ Seeded 5 languages successfully.');
        $this->command->info('   - Arabic (ar) - RTL, Default');
        $this->command->info('   - English (en) - LTR');
        $this->command->info('   - French (fr) - LTR');
        $this->command->info('   - Turkish (tr) - LTR');
        $this->command->info('   - Urdu (ur) - RTL');
    }
}
