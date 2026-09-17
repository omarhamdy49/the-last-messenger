<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The `people` -> `person_translations` pass was removed with the Genealogy
 * module: neither table exists on a fresh build. The Seerah participants pass
 * went with the 005 Seerah refactor. The remaining two are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->migrateTableTranslations('quran_reciters', 'reciter_translations', 'reciter_id', ['name', 'description']);
        $this->migrateTableTranslations('languages', 'language_translations', 'language_id', ['name']);
    }

    public function down(): void
    {
        DB::table('reciter_translations')->delete();
        DB::table('language_translations')->delete();
    }

    private function migrateTableTranslations(string $parentTable, string $translationTable, string $fkColumn, array $fields): void
    {
        $records = DB::table($parentTable)->get();

        foreach ($records as $record) {
            $locales = [];

            foreach ($fields as $field) {
                $jsonValue = $record->{$field} ?? null;
                if (! $jsonValue) {
                    continue;
                }

                $decoded = is_string($jsonValue) ? json_decode($jsonValue, true) : $jsonValue;
                if (! is_array($decoded)) {
                    continue;
                }

                foreach ($decoded as $locale => $value) {
                    if (! isset($locales[$locale])) {
                        $locales[$locale] = [];
                    }
                    $locales[$locale][$field] = $value;
                }
            }

            foreach ($locales as $locale => $values) {
                $hasContent = false;
                foreach ($values as $value) {
                    if (! empty(trim((string) ($value ?? '')))) {
                        $hasContent = true;
                        break;
                    }
                }
                if (! $hasContent) {
                    continue;
                }

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

        return ! empty(trim((string) ($firstValue ?? '')));
    }
};
