<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate series translations
        $this->migrateTranslations('series', 'series_translations', 'series_id', ['title', 'description']);

        // Migrate categories translations
        $this->migrateTranslations('categories', 'categories_translations', 'category_id', ['name', 'description']);

        // Migrate tags translations
        $this->migrateTranslations('tags', 'tags_translations', 'tag_id', ['name', 'description']);
    }

    public function down(): void
    {
        // Clear translation tables
        DB::table('series_translations')->delete();
        DB::table('categories_translations')->delete();
        DB::table('tags_translations')->delete();
    }

    private function migrateTranslations(string $parentTable, string $translationTable, string $fkColumn, array $fields): void
    {
        $parentRecords = DB::table($parentTable)->get();

        foreach ($parentRecords as $record) {
            $locales = $this->extractLocalesFromFields($record, $fields);

            foreach ($locales as $locale => $values) {
                // Check if any field has content
                $hasContent = false;
                foreach ($values as $value) {
                    if (!empty(trim((string) ($value ?? '')))) {
                        $hasContent = true;
                        break;
                    }
                }

                if (!$hasContent) {
                    continue;
                }

                // Check if record exists
                $exists = DB::table($translationTable)
                    ->where($fkColumn, $record->id)
                    ->where('locale', $locale)
                    ->exists();

                $data = array_merge($values, [
                    $fkColumn => $record->id,
                    'locale' => $locale,
                    'is_ready' => $this->isLocaleReady($values),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($exists) {
                    unset($data['created_at']);
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

    private function extractLocalesFromFields($record, array $fields): array
    {
        $locales = [];

        foreach ($fields as $field) {
            $jsonValue = $record->{$field} ?? null;
            if (!$jsonValue) {
                continue;
            }

            $decoded = is_string($jsonValue) ? json_decode($jsonValue, true) : $jsonValue;
            if (!is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $locale => $value) {
                if (!isset($locales[$locale])) {
                    $locales[$locale] = [];
                }
                $locales[$locale][$field] = $value;
            }
        }

        return $locales;
    }

    private function isLocaleReady(array $fields): bool
    {
        // Locale is ready if the first field has content
        $firstValue = reset($fields);
        return !empty(trim((string) ($firstValue ?? '')));
    }
};
