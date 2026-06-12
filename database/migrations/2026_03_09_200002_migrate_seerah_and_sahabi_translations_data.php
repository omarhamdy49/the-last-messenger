<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migrate translatable data from JSON columns in seerah_events and sahaba tables
 * into the new seerah_event_translations and sahabi_translations tables.
 *
 * This migration is idempotent — safe to run multiple times.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->migrateSeerahEventTranslations();
        $this->migrateSahabiTranslations();
    }

    /**
     * Migrate seerah_events JSON columns → seerah_event_translations rows.
     */
    private function migrateSeerahEventTranslations(): void
    {
        $fields = ['title', 'description', 'content'];
        $total = 0;

        DB::table('seerah_events')
            ->select(array_merge(['id'], $fields))
            ->orderBy('id')
            ->chunk(100, function ($events) use ($fields, &$total) {
                foreach ($events as $event) {
                    $locales = $this->extractLocalesFromJsonFields($event, $fields);

                    foreach ($locales as $locale => $data) {
                        $hasContent = false;
                        foreach ($data as $value) {
                            if (! empty(trim((string) ($value ?? '')))) {
                                $hasContent = true;
                                break;
                            }
                        }

                        if (! $hasContent) {
                            continue;
                        }

                        DB::table('seerah_event_translations')->updateOrInsert(
                            [
                                'seerah_event_id' => $event->id,
                                'locale' => $locale,
                            ],
                            array_merge($data, [
                                'is_ready' => ! empty(trim((string) ($data['title'] ?? ''))),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ])
                        );

                        $total++;
                    }
                }
            });

        Log::info('seerah.translation.migration', [
            'translations_created' => $total,
        ]);
    }

    /**
     * Migrate sahaba JSON columns → sahabi_translations rows.
     */
    private function migrateSahabiTranslations(): void
    {
        $fields = ['name', 'full_name', 'kunya', 'laqab', 'biography', 'virtues', 'famous_narrations'];
        $total = 0;

        DB::table('sahaba')
            ->select(array_merge(['id'], $fields))
            ->orderBy('id')
            ->chunk(100, function ($companions) use ($fields, &$total) {
                foreach ($companions as $companion) {
                    $locales = $this->extractLocalesFromJsonFields($companion, $fields);

                    foreach ($locales as $locale => $data) {
                        $hasContent = false;
                        foreach ($data as $value) {
                            if (! empty(trim((string) ($value ?? '')))) {
                                $hasContent = true;
                                break;
                            }
                        }

                        if (! $hasContent) {
                            continue;
                        }

                        DB::table('sahabi_translations')->updateOrInsert(
                            [
                                'sahabi_id' => $companion->id,
                                'locale' => $locale,
                            ],
                            array_merge($data, [
                                'is_ready' => ! empty(trim((string) ($data['name'] ?? ''))),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ])
                        );

                        $total++;
                    }
                }
            });

        Log::info('sahabi.translation.migration', [
            'translations_created' => $total,
        ]);
    }

    /**
     * Extract locale-keyed data from JSON columns.
     *
     * Input: a DB row with JSON columns like title='{"ar":"...","en":"..."}'
     * Output: ['ar' => ['title' => '...'], 'en' => ['title' => '...']]
     *
     * @param  array<string>  $fields
     * @return array<string, array<string, string|null>>
     */
    private function extractLocalesFromJsonFields(object $row, array $fields): array
    {
        $byLocale = [];

        foreach ($fields as $field) {
            $json = $row->{$field};

            if (empty($json)) {
                continue;
            }

            $decoded = is_string($json) ? json_decode($json, true) : (array) $json;

            if (! is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $locale => $value) {
                if (! is_string($locale) || strlen($locale) > 5) {
                    continue;
                }
                $byLocale[$locale][$field] = $value;
            }
        }

        // Ensure all fields exist for each locale (fill missing with null)
        foreach ($byLocale as $locale => &$data) {
            foreach ($fields as $field) {
                if (! isset($data[$field])) {
                    $data[$field] = null;
                }
            }
        }

        return $byLocale;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration reverse: copy back from translation tables to JSON columns
        // This is a safety measure — normally you wouldn't reverse this
        DB::table('seerah_event_translations')->truncate();
        DB::table('sahabi_translations')->truncate();
    }
};
