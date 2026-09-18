<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The mushaf catalogue the reader offers, keyed on Quran Foundation mushaf ids.
 *
 * Ids and font families come from the QF font-rendering guide
 * (api-docs.quran.foundation/docs/tutorials/fonts/font-rendering). Page layouts
 * (pages + word positions) are filled later by the `mushafs` sync snapshot; this
 * seeder only creates the rows an editor enables. Re-running it never touches the
 * editor-controlled flags (`is_active`, `is_default`) of an existing row.
 *
 * `font_version` selects the local font folder under public/fonts/quran:
 * v1 / v2 are per-page QCF glyph fonts; hafs / indopak are single Unicode fonts.
 */
final class QuranMushafSeeder extends Seeder
{
    /**
     * @var list<array{id: int, name: string, description: string, font_version: string, default_font_name: string, is_default: bool}>
     */
    private const MUSHAFS = [
        ['id' => 1, 'name' => 'QCF V2', 'description' => 'Madani Mushaf, Quran Complex Font version 2 (glyph-based)', 'font_version' => 'v2', 'default_font_name' => 'v2', 'is_default' => true],
        ['id' => 2, 'name' => 'QCF V1', 'description' => 'Madani Mushaf, Quran Complex Font version 1 (glyph-based)', 'font_version' => 'v1', 'default_font_name' => 'v1', 'is_default' => false],
        ['id' => 3, 'name' => 'IndoPak', 'description' => 'IndoPak script', 'font_version' => 'indopak', 'default_font_name' => 'indopak', 'is_default' => false],
        ['id' => 4, 'name' => 'Uthmani', 'description' => 'Uthmani Unicode text', 'font_version' => 'hafs', 'default_font_name' => 'uthmani', 'is_default' => false],
        ['id' => 5, 'name' => 'QPC Hafs', 'description' => 'QPC Uthmanic Hafs Unicode text', 'font_version' => 'hafs', 'default_font_name' => 'qpc_hafs', 'is_default' => false],
        ['id' => 6, 'name' => 'IndoPak 15 lines', 'description' => 'IndoPak Nastaleeq, 15-line layout', 'font_version' => 'indopak', 'default_font_name' => 'indopak', 'is_default' => false],
        ['id' => 7, 'name' => 'IndoPak 16 lines', 'description' => 'IndoPak Nastaleeq, 16-line layout', 'font_version' => 'indopak', 'default_font_name' => 'indopak', 'is_default' => false],
        ['id' => 19, 'name' => 'QCF V4 Tajweed', 'description' => 'Madani Mushaf with colour-coded tajweed (glyph-based, QCF V2 codes)', 'font_version' => 'v2', 'default_font_name' => 'v4_tajweed', 'is_default' => false],
    ];

    public function run(): void
    {
        $now = now();
        $existing = DB::table('quran_mushafs')->pluck('id')->all();

        foreach (self::MUSHAFS as $mushaf) {
            $descriptive = [
                'name' => $mushaf['name'],
                'description' => $mushaf['description'],
                'font_version' => $mushaf['font_version'],
                'default_font_name' => $mushaf['default_font_name'],
                'updated_at' => $now,
            ];

            if (in_array($mushaf['id'], $existing, true)) {
                DB::table('quran_mushafs')->where('id', $mushaf['id'])->update($descriptive);

                continue;
            }

            DB::table('quran_mushafs')->insert($descriptive + [
                'id' => $mushaf['id'],
                'is_default' => $mushaf['is_default'],
                // The default mushaf is usable as soon as its layout is synced.
                'is_active' => $mushaf['is_default'],
                'created_at' => $now,
            ]);
        }

        $this->command?->info('Quran mushafs seeded: '.count(self::MUSHAFS));
    }
}
