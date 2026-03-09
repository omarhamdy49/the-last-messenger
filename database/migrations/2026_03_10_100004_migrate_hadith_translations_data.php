<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateTranslations('hadith_sources', 'hadith_source_translations', 'hadith_source_id', ['name', 'description']);
        $this->migrateTranslations('hadith_themes', 'hadith_theme_translations', 'hadith_theme_id', ['name', 'description']);
        $this->migrateTranslations('hadith_chapters', 'hadith_chapter_translations', 'hadith_chapter_id', ['name', 'description']);
    }

    public function down(): void
    {
        DB::table('hadith_source_translations')->delete();
        DB::table('hadith_theme_translations')->delete();
        DB::table('hadith_chapter_translations')->delete();
    }

    private function migrateTranslations(string $parentTable, string $translationTable, string $fkColumn, array $fields): void
    {
        $records = DB::table($parentTable)->get();
        foreach ($records as $record) {
            $locales = [];
            foreach ($fields as $field) {
                $jsonValue = $record->{$field} ?? null;
                if (!$jsonValue) continue;
                $decoded = is_string($jsonValue) ? json_decode($jsonValue, true) : $jsonValue;
                if (!is_array($decoded)) continue;
                foreach ($decoded as $locale => $value) {
                    if (!isset($locales[$locale])) $locales[$locale] = [];
                    $locales[$locale][$field] = $value;
                }
            }

            foreach ($locales as $locale => $values) {
                $hasContent = false;
                foreach ($values as $value) {
                    if (!empty(trim((string) ($value ?? '')))) {
                        $hasContent = true;
                        break;
                    }
                }
                if (!$hasContent) continue;

                $exists = DB::table($translationTable)
                    ->where($fkColumn, $record->id)
                    ->where('locale', $locale)
                    ->exists();

                $data = array_merge($values, [
                    $fkColumn => $record->id,
                    'locale' => $locale,
                    'is_ready' => $this->isLocaleReady($values),
                ]);

                if ($exists) {
                    DB::table($translationTable)
                        ->where($fkColumn, $record->id)
                        ->where('locale', $locale)
                        ->update($data);
                } else {
                    DB::table($translationTable)->insert($data);
                }
            }
        }
    }

    private function isLocaleReady(array $fields): bool
    {
        $firstValue = reset($fields);
        return !empty(trim((string) ($firstValue ?? '')));
    }
};
