<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Infrastructure\Mp3Quran\Mp3QuranCatalogue;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\Audio\RecordingSourceService;
use Illuminate\Support\Str;

/**
 * Creates one of our reciters from one mp3quran recording.
 *
 * One row per *recording*, not per reciter: a qari's Hafs murattal, mujawwad,
 * mu'allim and Warsh mushafs are different audio, and the catalogue we already
 * had models them as separate entries (عبد الباسط عبد الصمد (مرتل) alongside
 * (مجود)). The style suffix follows that convention, and only appears when the
 * reciter has more than one recording to tell apart.
 *
 * A folder identifies a recording, so it — not a name — is the identity used to
 * decide whether we already have it and to restore whatever an admin curated
 * before a --fresh rebuild.
 */
final class Mp3QuranReciterImporter
{
    /**
     * Riwayat we already have a house spelling for. Everything else falls back
     * to mp3quran's own English wording rather than inventing a
     * transliteration for a riwayah no row has ever used.
     */
    private const RIWAYAH = [
        1 => "Hafs 'an 'Asim",
        2 => "Warsh 'an Nafi'",
        10 => "Warsh 'an Nafi'",
        18 => "Warsh 'an Nafi'",
        // Not riwayat: mp3quran files a mu'allim or mujawwad mushaf under its
        // own id, and both of these are Hafs recordings.
        21 => "Hafs 'an 'Asim",
        22 => "Hafs 'an 'Asim",
    ];

    /** Arabic style word => our `style` column and the suffix we display. */
    private const STYLES = [
        'مجود' => 'mujawwad',
        'معلم' => 'muallim',
        'مرتل' => 'murattal',
    ];

    private const STYLE_EN = [
        'mujawwad' => 'Mujawwad',
        'muallim' => 'Muallim',
        'murattal' => 'Murattal',
    ];

    /** @var array<string, true> every slug already taken, including ones reserved this run */
    private array $slugs;

    public function __construct(private readonly Mp3QuranCatalogue $catalogue)
    {
        $this->slugs = Reciter::query()->pluck('slug')->flip()->all();
    }

    /**
     * What a recording would become, with its slug reserved so a dry run and a
     * real run number collisions the same way.
     *
     * @param  array<string, mixed>  $moshaf
     * @param  array<string, mixed>|null  $kept  a snapshot to restore, from snapshot()
     * @return array<string, mixed>
     */
    public function preview(array $moshaf, ?array $kept = null): array
    {
        $kept ??= [];
        $siblings = count($this->catalogue->moshafsFor($moshaf['reciter_id']));
        $style = $this->style($moshaf);

        $nameAr = self::houseSpelling($moshaf['reciter']).$this->suffix($moshaf, $style, $siblings, arabic: true);
        $nameEn = $this->catalogue->englishName($moshaf['reciter_id']);
        $nameEn = $nameEn === null ? null : $nameEn.$this->suffix($moshaf, $style, $siblings, arabic: false);

        return [
            'slug' => $kept['slug'] ?? $this->reserveSlug($nameEn ?? $nameAr, $moshaf['id']),
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'style' => $style,
            'riwayah' => $this->riwayah($moshaf),
            'restored' => $kept !== [],
        ];
    }

    /**
     * Create the reciter, its translations and its whole-surah audio.
     *
     * Runs inside the caller's transaction. The audio goes in with one insert
     * because the reciter is new by definition — there is nothing to collide
     * with, so there is nothing to upsert against.
     *
     * @param  array<string, mixed>  $moshaf
     * @param  array<string, mixed>  $preview  from preview()
     * @param  array<string, mixed>|null  $kept  the same snapshot preview() was given
     * @param  bool  $active  publish immediately; a preserved row keeps its own flag
     */
    public function import(array $moshaf, array $preview, ?array $kept = null, bool $active = true): Reciter
    {
        $kept ??= [];

        $reciter = Reciter::create([
            'slug' => $preview['slug'],
            'kind' => $kept['kind'] ?? 'recitation',
            'style' => $kept['style'] ?? $preview['style'],
            'riwayah' => $kept['riwayah'] ?? $preview['riwayah'],
            'country' => $kept['country'] ?? null,
            'profile_image' => $kept['profile_image'] ?? null,
            'is_featured' => $kept['is_featured'] ?? false,
            // Live immediately: an imported reciter that does not show up in
            // the public list or the surah picker has not been imported.
            'is_active' => $kept['is_active'] ?? $active,
            'display_order' => $kept['display_order'] ?? 0,
            'followers_count' => $kept['followers_count'] ?? 0,
        ]);

        $source = app(RecordingSourceService::class)->catalogueSource($reciter, $moshaf, $this->catalogue->readIdFor($moshaf['server']));

        $reciter->syncTranslations($kept['translations'] ?? [
            'name' => array_filter([
                'ar' => $preview['name_ar'],
                'en' => $preview['name_en'],
            ]),
        ]);

        $now = now();
        $rows = [];

        foreach (Mp3QuranCatalogue::audioUrls($moshaf) as $surahId => $url) {
            $rows[] = [
                'reciter_id' => $reciter->id,
                'source_id' => $source->id,
                'surah_id' => $surahId,
                'audio_url' => $url,
                'format' => 'mp3',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Recitation::insert($rows);

        return $reciter;
    }

    /**
     * Curated and non-mp3quran columns worth carrying across a rebuild: a
     * truncate would otherwise throw away portraits, ordering and the everyayah
     * folder that per-verse audio is built from, none of which mp3quran knows.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(Reciter $reciter): array
    {
        $translations = [];

        foreach ($reciter->translations as $translation) {
            $translations['name'][$translation->locale] = $translation->name;
            $translations['description'][$translation->locale] = $translation->description;
        }

        return [
            'slug' => $reciter->slug,
            'kind' => $reciter->kind,
            'style' => $reciter->style,
            'riwayah' => $reciter->riwayah,
            'country' => $reciter->country,
            'profile_image' => $reciter->profile_image,
            'is_featured' => $reciter->is_featured,
            'is_active' => $reciter->is_active,
            'display_order' => $reciter->display_order,
            'followers_count' => $reciter->followers_count,
            'translations' => $translations,
        ];
    }

    /**
     * mp3quran writes the theophoric compounds solid — عبدالله, عبدالباسط —
     * while our whole catalogue writes them split. The reciter picker sorts
     * alphabetically on this string, so a solid form files عبدالله الخلف far
     * away from every other عبد الله.
     */
    public static function houseSpelling(string $name): string
    {
        $words = array_map(
            // Only in front of the article: every compound in the catalogue is
            // عبد + الـ, and without that guard the surname عبدل becomes عبد ل.
            static fn (string $word) => str_starts_with($word, 'عبدال')
                ? 'عبد '.mb_substr($word, 3)
                : $word,
            preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        );

        return implode(' ', $words);
    }

    /**
     * @param  array<string, mixed>  $moshaf
     */
    private function style(array $moshaf): ?string
    {
        foreach (self::STYLES as $arabic => $style) {
            if (str_contains($moshaf['name'], $arabic)) {
                return $style;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $moshaf
     */
    private function riwayah(array $moshaf): string
    {
        // "Rewayat Hafs A'n Assem - Murattal" — the part before the dash, and
        // without the prefix, since our own values read "Hafs 'an 'Asim".
        $english = $this->catalogue->englishMoshafName($moshaf['id']);
        $fallback = $english === null
            ? $moshaf['name']
            : trim(Str::after(trim(explode(' - ', $english)[0]), 'Rewayat '));

        return self::RIWAYAH[$moshaf['rewaya_id']] ?? $fallback;
    }

    /**
     * A style suffix only earns its place when the reciter has more than one
     * recording; a qari with a single mushaf is just their name.
     *
     * @param  array<string, mixed>  $moshaf
     */
    private function suffix(array $moshaf, ?string $style, int $siblings, bool $arabic): string
    {
        if ($siblings < 2) {
            return '';
        }

        // Warsh next to a Hafs murattal is told apart by the riwayah, not the
        // style — which is how عبد الباسط عبد الصمد (ورش) is already named.
        if (! in_array($moshaf['rewaya_id'], [1, 21, 22], true)) {
            // "ورش عن نافع - مرتل" / "Rewayat Warsh A'n Nafi - Murattal".
            $word = $arabic
                ? trim(explode(' عن ', $moshaf['name'])[0])
                : (explode(' ', (string) $this->catalogue->englishMoshafName($moshaf['id']))[1] ?? '');

            return $word === '' ? '' : " ({$word})";
        }

        if ($style === null) {
            return '';
        }

        return ' ('.($arabic ? array_search($style, self::STYLES, true) : self::STYLE_EN[$style]).')';
    }

    /**
     * Deterministic and unique: the same catalogue always produces the same
     * slugs, and a clash is settled by the recording's own id rather than a
     * counter that shifts when the catalogue grows.
     */
    private function reserveSlug(string $name, int $moshafId): string
    {
        $slug = Str::slug($name) ?: 'reciter';

        if (isset($this->slugs[$slug])) {
            $slug = $slug.'-'.$moshafId;
        }

        $this->slugs[$slug] = true;

        return $slug;
    }
}
