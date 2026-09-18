<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\ReaderOptions;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ChapterRepositoryInterface;
use App\Domain\Quran\Repositories\MushafRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the public reader pages from local data. Payload keys match what the kept
 * Blade views and the shared audio player already read (camelCase `surah`, `verses`,
 * `reciters`, …); new features add keys next to them.
 */
final class ReaderService
{
    public function __construct(
        private readonly ChapterRepositoryInterface $chapters,
        private readonly VerseRepositoryInterface $verses,
        private readonly ResourceRepositoryInterface $resources,
        private readonly MushafRepositoryInterface $mushafs,
        private readonly AudioRepositoryInterface $audio,
    ) {}

    /**
     * Reader options from the query string (`?script=`, `?translations=1,2`).
     *
     * @param  array<string, mixed>  $query
     */
    public function options(array $query, string $locale): ReaderOptions
    {
        $script = Script::fromExternal((string) ($query['script'] ?? '')) ?? Script::default();
        if ($script->isGlyph()) {
            $script = Script::default();
        }

        $requested = array_filter(array_map('intval', explode(',', (string) ($query['translations'] ?? ''))));
        $translationIds = $requested === []
            ? array_values(array_filter([$this->defaultTranslation($locale)?->id]))
            : $this->resources->activeByIds(ResourceKind::TRANSLATION, array_slice($requested, 0, 5))->pluck('id')->all();

        $wbw = filter_var($query['wbw'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return new ReaderOptions(
            script: $script,
            translationIds: array_map('intval', $translationIds),
            words: $wbw,
            wbwLanguage: $wbw ? ($locale === 'ar' ? (string) config('quran.defaults.wbw_language', 'en') : $locale) : null,
            withTajweed: $script === Script::UTHMANI_TAJWEED,
        );
    }

    /**
     * All chapters for the index page and the reader's surah switcher.
     *
     * @return list<array{number: int, nameAr: string, nameEn: string, transliteration: string, verses: int, type: string, juz: int, revelationOrder: int, pages: array{0: int, 1: int}}>
     */
    public function chapterList(string $locale): array
    {
        return Cache::remember(QuranCacheKeys::chapters('list:'.$locale), 3600, function () use ($locale): array {
            $firstJuz = $this->chapters->firstJuzMap();

            return $this->chapters->all()->map(fn (Chapter $c): array => [
                'number' => $c->id,
                'nameAr' => $c->name_arabic,
                'nameEn' => $c->displayName($locale === 'ar' ? 'en' : $locale),
                'transliteration' => $c->name_simple,
                'verses' => $c->verses_count,
                'type' => $c->revelation_place === RevelationPlace::MADINAH ? 'madani' : 'makki',
                'juz' => $firstJuz[$c->id] ?? 1,
                'revelationOrder' => $c->revelation_order,
                'pages' => [$c->first_page, $c->last_page],
            ])->values()->all();
        });
    }

    /**
     * Everything the surah page renders except the reciter list (cached separately,
     * because audio changes on its own schedule). Null for an unknown/unpublished chapter.
     *
     * @return array<string, mixed>|null
     */
    public function chapterPayload(int $number, ReaderOptions $options, string $locale): ?array
    {
        $key = QuranCacheKeys::readerPayload('chapter:'.$number, $options->hash(), $locale);

        return Cache::remember($key, 3600, function () use ($number, $options, $locale): ?array {
            $chapter = $this->chapters->find($number);
            if ($chapter === null || ! $chapter->is_published) {
                return null;
            }

            $translationResources = $this->translationResources($locale);
            $default = $this->defaultTranslation($locale);
            $transliteration = $this->transliterationResource();
            $selectedIds = array_values(array_unique(array_filter([...$options->translationIds, $default?->id, $transliteration?->id])));

            $verses = $this->verses->byChapter($number, $options->scriptColumns());
            $payload = $this->verses->payload($verses->pluck('id')->all(), new ReaderOptions(
                script: $options->script, translationIds: $selectedIds, words: $options->words, wbwLanguage: $options->wbwLanguage));
            $info = $this->chapters->info($number, $locale);
            $firstJuz = $this->chapters->firstJuzMap();
            $names = collect($this->chapterList($locale))->pluck('nameAr', 'number');

            return [
                'surah' => [
                    'number' => $chapter->id,
                    'nameAr' => $chapter->name_arabic,
                    'nameEn' => $chapter->displayName($locale === 'ar' ? 'en' : $locale),
                    'name' => $chapter->displayName($locale),
                    'transliteration' => $chapter->name_simple,
                    'revelationPlace' => $chapter->revelation_place->value,
                    'revelationPlaceName' => $chapter->location?->name($locale),
                    'revelationOrder' => $chapter->revelation_order,
                    'versesCount' => $chapter->verses_count,
                    'bismillahPre' => $chapter->bismillah_pre,
                    'type' => $chapter->revelation_place === RevelationPlace::MADINAH ? 'madani' : 'makki',
                    'juz' => $firstJuz[$chapter->id] ?? 1,
                    'pages' => [$chapter->first_page, $chapter->last_page],
                    'info' => $info === null ? null : self::plain($info['short_text'] ?: $info['text']),
                ],
                'verses' => $verses->map(fn (Verse $v): array => $this->readerVerse($v, $options, $payload[$v->id] ?? [], $default?->id, $transliteration?->id))->values()->all(),
                'chapterInfo' => $info === null ? null : [
                    'text' => self::plain($info['text']),
                    'source' => $info['source'],
                    'resource' => $info['resource_name'],
                    'language' => $info['language_iso'],
                ],
                'translationResources' => $translationResources,
                'tafsirResources' => $this->tafsirResources($locale),
                'defaultTranslationId' => $default?->id ?? 0,
                'defaultTranslationLang' => $default?->language_iso ?? 'en',
                'mushaf' => $this->mushafs->chapterLines((int) config('quran.mushaf.default_id', 1), $number),
                'scripts' => array_values(array_map(static fn (Script $s): array => ['value' => $s->qfPath(), 'label' => $s->label($locale)],
                    array_filter(Script::cases(), static fn (Script $s): bool => $s->isSelectable()))),
                'selectedScript' => $options->script->qfPath(),
                'wordByWord' => $options->words,
                'prevSurah' => $number > 1 ? $number - 1 : null,
                'nextSurah' => $number < 114 ? $number + 1 : null,
                'prevSurahName' => $names[$number - 1] ?? null,
                'nextSurahName' => $names[$number + 1] ?? null,
            ];
        });
    }

    /**
     * Reciters for the shared audio player on a chapter page: whole-chapter reciters
     * that actually have this chapter's file.
     *
     * Verse-by-verse recitations are deliberately not offered. The reader plays one
     * gapless recording and locates an ayah inside it with the chapter's timings, so
     * a chain of per-ayah files would be a second, worse way to do the same thing —
     * a pause at every ayah and a request per verse.
     *
     * @return list<array<string, mixed>>
     */
    public function readerReciters(int $chapter, string $locale): array
    {
        return Cache::remember(QuranCacheKeys::readerReciters($chapter, $locale), 3600, function () use ($chapter, $locale): array {
            $files = $this->audio->activeChapterFiles($chapter)->keyBy('resource_id');

            $rows = [];
            foreach ($this->resources->active(ResourceKind::CHAPTER_RECITER) as $reciter) {
                $file = $files->get($reciter->id);
                if ($file === null) {
                    continue;
                }
                $rows[] = $this->playerReciter($reciter, $locale, AudioService::absolutize($file->url), (bool) $file->has_timestamps);
            }

            return ReciterLabeller::disambiguate($rows, ['recordingLabel', 'sourceHint', 'kindLabel']);
        });
    }

    /**
     * One translation resource for a whole chapter, verse id => plain text (lazy load).
     *
     * @return array<int, string>
     */
    public function chapterTranslation(int $chapter, int $resourceId): array
    {
        if ($this->resources->activeByIds(ResourceKind::TRANSLATION, [$resourceId])->isEmpty()) {
            return [];
        }

        $ids = $this->verses->byChapter($chapter)->pluck('id')->all();
        $payload = $this->verses->payload($ids, new ReaderOptions(translationIds: [$resourceId]));

        $texts = [];
        foreach ($payload as $verseId => $parts) {
            if (isset($parts['translations'][$resourceId])) {
                $texts[$verseId] = self::plain($parts['translations'][$resourceId]['text']);
            }
        }

        return $texts;
    }

    /**
     * One tafsir resource for a whole chapter, verse id => plain text (lazy load).
     *
     * @return array<int, string>
     */
    public function chapterTafsir(int $chapter, int $resourceId): array
    {
        if ($this->resources->activeByIds(ResourceKind::TAFSIR, [$resourceId])->isEmpty()) {
            return [];
        }

        $ids = $this->verses->byChapter($chapter)->pluck('id')->all();
        $texts = [];
        foreach ($this->verses->payload($ids, new ReaderOptions(tafsirIds: [$resourceId])) as $verseId => $parts) {
            if (isset($parts['tafsirs'][$resourceId])) {
                $texts[$verseId] = self::plain($parts['tafsirs'][$resourceId]['text']);
            }
        }

        return $texts;
    }

    /**
     * A public translation / tafsir resource page header, or null when inactive.
     *
     * @return array{id: int, kind: string, name: string, author: ?string, language: ?string, info: string}|null
     */
    public function publicResource(ResourceKind $kind, int $id, string $locale): ?array
    {
        $resource = $this->resources->activeByIds($kind, [$id])->first();

        return $resource === null ? null : [
            'id' => $resource->id,
            'kind' => $kind->value,
            'name' => $resource->displayName($locale),
            'author' => $resource->author_name,
            'language' => $resource->language_iso,
            'info' => self::plain($resource->info ?: $resource->getTranslation('description', $locale, false)),
        ];
    }

    /**
     * One verse's tafsir text for one active tafsir resource ('' when missing).
     */
    /**
     * One verse's words, for the modal's word list. Lazy on purpose: the reading view
     * does not ship word data, and a chapter's worth of it dwarfs the page.
     *
     * @return list<array{text: string, translation: ?string, transliteration: ?string, audio_url: ?string}>
     */
    public function verseWords(int $verseId, string $locale): array
    {
        $payload = $this->verses->payload([$verseId], new ReaderOptions(words: true, wbwLanguage: $this->wordGlossLanguage($locale)));

        return array_map(static fn (array $w): array => [
            'text' => (string) $w['text_uthmani'],
            'type' => (string) $w['char_type'],
            'translation' => $w['translation'],
            'transliteration' => $w['transliteration'],
            'audio_url' => AudioService::absolutize($w['audio_url']),
        ], $payload[$verseId]['words'] ?? []);
    }

    /**
     * The language to read word meanings in. Quran Foundation publishes word-by-word
     * glosses per language and an installation only enables some; on an Arabic site
     * with only the English set enabled, asking for Arabic returns a word list with
     * every meaning null. English is the fallback because it is the set QF ships for
     * every word.
     */
    private function wordGlossLanguage(string $locale): string
    {
        $hasLocale = $this->resources->active(ResourceKind::WORD_TRANSLATION, $locale)->isNotEmpty();

        return $hasLocale ? $locale : 'en';
    }

    /**
     * Every language a chapter's introduction exists in, for the info modal's tabs.
     *
     * @return list<array{language: string, text: string, source: ?string, resource: string}>
     */
    public function chapterInfos(int $chapter): array
    {
        return Cache::remember(QuranCacheKeys::chapterInfos($chapter), 3600, function () use ($chapter): array {
            return array_map(static fn (array $row): array => [
                'language' => $row['language'],
                'text' => self::plain($row['text']),
                'source' => $row['source'],
                'resource' => $row['resource'],
            ], $this->chapters->infos($chapter));
        });
    }

    public function verseTafsir(int $verseId, int $resourceId): string
    {
        if ($this->resources->activeByIds(ResourceKind::TAFSIR, [$resourceId])->isEmpty()) {
            return '';
        }

        $payload = $this->verses->payload([$verseId], new ReaderOptions(tafsirIds: [$resourceId]));

        return self::plain($payload[$verseId]['tafsirs'][$resourceId]['text'] ?? '');
    }

    /**
     * Player timings for a chapter reciter: [[ayah, startMs, endMs], ...].
     *
     * @return list<array{0: int, 1: int, 2: int}>
     */
    public function timings(int $chapter, int $reciterId, AudioService $audio): array
    {
        return array_values(array_map(
            static fn (array $t): array => [(int) (explode(':', $t['verse_key'])[1] ?? 0), $t['from'], $t['to']],
            $audio->timings($reciterId, $chapter),
        ));
    }

    /**
     * Verses of a juz / hizb / rubʿ / manzil / rukūʿ, paginated, in the `_reader` shape.
     *
     * @return array{verses: list<array<string, mixed>>, pagination: array<string, int|bool>}
     */
    public function sectionPayload(SectionType $type, int $number, int $page, int $perPage, string $locale): array
    {
        $key = QuranCacheKeys::readerPayload("section:{$type->value}:{$number}:{$page}:{$perPage}", 'default', $locale);

        return Cache::remember($key, 3600, function () use ($type, $number, $page, $perPage, $locale): array {
            $paginator = $this->verses->pageBySection($type, $number, $page, $perPage, ['text_uthmani']);

            return [
                'verses' => $this->listVerses(collect($paginator->items()), $locale),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                    'has_prev' => $paginator->currentPage() > 1,
                    'has_next' => $paginator->currentPage() < $paginator->lastPage(),
                ],
            ];
        });
    }

    /**
     * Verses of one mushaf page in the `_reader` shape.
     *
     * @return list<array<string, mixed>>
     */
    public function pagePayload(int $page, string $locale): array
    {
        return Cache::remember(QuranCacheKeys::readerPayload("page:{$page}", 'default', $locale), 3600,
            fn (): array => $this->listVerses($this->verses->byPage($page, null, ['text_uthmani']), $locale));
    }

    public function defaultTranslation(string $locale): ?QuranResource
    {
        $active = $this->resources->active(ResourceKind::TRANSLATION);
        $transliterationId = $this->transliterationResource()?->id;
        $active = $active->reject(static fn (QuranResource $r): bool => $r->id === $transliterationId)->values();

        $pick = static fn (string $language): ?QuranResource => $active->where('language_iso', $language)->sortByDesc('is_default')->first();

        return ($locale !== 'ar' ? $pick($locale) : null)
            ?? $active->first(static fn (QuranResource $r): bool => in_array($r->qf_id, (array) config('quran.defaults.translations.en', []), true))
            ?? $pick('en')
            ?? $active->first();
    }

    /**
     * Strip markup (footnote markers included) for the `x-text` bindings of the reader.
     */
    public static function plain(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }
        $html = (string) preg_replace('#<sup\b[^>]*>.*?</sup>#is', '', $html);
        $html = (string) preg_replace('#<(br|/p|/div|/li)\b[^>]*>#i', "\n", $html);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * The tajweed column ends with `<span class=end>٣</span>`, the ayah number. It is a
     * marker, not part of the verse, and as plain text it would be copied with the ayah
     * and counted as one more word when a tap is resolved.
     */
    private static function withoutEndMarker(string $html): string
    {
        return (string) preg_replace('#<span\b[^>]*\bclass\s*=\s*["\']?end["\']?[^>]*>.*?</span>#is', '', $html);
    }

    /**
     * Footnotes as plain text, keyed as they arrived. QF stores them either as
     * `{id: html}` or as a list of `{id, text}`; both reach us through the snapshot.
     *
     * @return array<array-key, string>
     */
    private static function plainNotes(mixed $notes): array
    {
        if (! is_array($notes)) {
            return [];
        }

        $plain = [];
        foreach ($notes as $key => $note) {
            $id = is_array($note) ? ($note['id'] ?? $key) : $key;
            $text = self::plain(is_array($note) ? (string) ($note['text'] ?? '') : (string) $note);
            if ($text !== '') {
                $plain[$id] = $text;
            }
        }

        return $plain;
    }

    /**
     * @param  array<string, mixed>  $parts  words/translations/tafsirs/audio from VerseRepository::payload
     * @return array<string, mixed>
     */
    private function readerVerse(Verse $verse, ReaderOptions $options, array $parts, ?int $defaultTranslationId, ?int $transliterationId): array
    {
        $translations = [];
        foreach ((array) ($parts['translations'] ?? []) as $resourceId => $row) {
            if ($resourceId !== $transliterationId) {
                $translations[$resourceId] = [
                    'text' => self::plain($row['text']),
                    // A footnote is HTML too ("<p>At its proper times…</p>"), and the page
                    // prints it as text — so it has to arrive as text, like the verse
                    // translation above it.
                    'foot_notes' => self::plainNotes($row['foot_notes']),
                ];
            }
        }

        return [
            'id' => $verse->id,
            'number' => $verse->verse_number,
            'verse_key' => $verse->verse_key,
            // `arabic` is text, never markup. The tajweed script's column is HTML
            // (`<tajweed class=slnt>…`), and everything that reads `arabic` — the verse
            // modal, copy, the tappable word split, the letter weights — treats it as
            // text, so the markup leaked through as visible tags. The coloured version
            // travels separately in `tajweed`, sanitised, for the one element that
            // renders it as HTML.
            'arabic' => self::plain(self::withoutEndMarker((string) ($verse->getAttribute($options->script->value) ?? $verse->text_uthmani))),
            'tajweed' => $options->withTajweed ? Verse::sanitizeTajweed($verse->text_uthmani_tajweed) : null,
            'transliteration' => $transliterationId !== null && isset($parts['translations'][$transliterationId])
                ? self::plain($parts['translations'][$transliterationId]['text']) : null,
            'translation' => $defaultTranslationId !== null ? ($translations[$defaultTranslationId]['text'] ?? null) : null,
            'translations' => $translations,
            'juz' => $verse->juz_number,
            'hizb' => $verse->hizb_number,
            'rub' => $verse->rub_el_hizb_number,
            'page' => $verse->page_number,
            'has_sajdah' => $verse->sajdah_number !== null,
            'sajdah_type' => $verse->sajdah_type,
            'words' => $options->words ? array_map(static fn (array $w): array => [
                'text' => (string) $w['text_uthmani'],
                'type' => $w['char_type'],
                'translation' => $w['translation'],
                'transliteration' => $w['transliteration'],
                'audio_url' => $w['audio_url'],
            ], (array) ($parts['words'] ?? [])) : [],
            // Only a copy we hold. The `image_url` QF ships points at a Rackspace CDN
            // (c22506.r6.cf1.rackcdn.com) that no longer answers, so offering it would
            // put a dead "verse image" control on every ayah. Run
            // `quran:qf:download-assets --images` against a working source to light it up.
            'image_url' => $verse->image_path !== null ? asset('storage/'.$verse->image_path) : null,
        ];
    }

    /**
     * Verses across chapters (juz/page views): chapter names, locale translation.
     *
     * @param  Collection<int, Verse>  $verses
     * @return list<array<string, mixed>>
     */
    private function listVerses(Collection $verses, string $locale): array
    {
        $default = $locale === 'ar' ? null : $this->defaultTranslation($locale);
        $transliteration = $this->transliterationResource();
        $payload = $this->verses->payload($verses->pluck('id')->all(),
            new ReaderOptions(translationIds: array_values(array_filter([$default?->id, $transliteration?->id]))));
        $names = collect($this->chapterList($locale))->pluck('nameAr', 'number');

        return $verses->map(fn (Verse $v): array => [
            'surahNumber' => $v->chapter_id,
            'surahName' => (string) ($names[$v->chapter_id] ?? ''),
            'number' => $v->verse_number,
            'verseKey' => $v->verse_key,
            'arabic' => (string) $v->text_uthmani,
            'translation' => $default !== null ? (self::plain($payload[$v->id]['translations'][$default->id]['text'] ?? null) ?: null) : null,
            'transliteration' => $transliteration !== null ? (self::plain($payload[$v->id]['translations'][$transliteration->id]['text'] ?? null) ?: null) : null,
            'has_sajdah' => $v->sajdah_number !== null,
        ])->values()->all();
    }

    /**
     * @return list<array{id: int, name: string, language: ?string, direction: string, is_default: bool}>
     */
    private function translationResources(string $locale): array
    {
        $transliterationId = $this->transliterationResource()?->id;

        return $this->resources->active(ResourceKind::TRANSLATION)
            ->reject(static fn (QuranResource $r): bool => $r->id === $transliterationId)
            ->map(static fn (QuranResource $r): array => [
                'id' => $r->id,
                'name' => $r->displayName($locale),
                'language' => $r->language_iso,
                'direction' => in_array($r->language_iso, ['ar', 'ur', 'fa', 'he', 'ps', 'dv', 'ku', 'ug', 'sd'], true) ? 'rtl' : 'ltr',
                'is_default' => $r->is_default,
            ])->values()->all();
    }

    /**
     * @return list<array{id: int, name: string, language: ?string}>
     */
    private function tafsirResources(string $locale): array
    {
        return $this->resources->active(ResourceKind::TAFSIR)
            ->map(static fn (QuranResource $r): array => ['id' => $r->id, 'name' => $r->displayName($locale), 'language' => $r->language_iso, 'is_default' => (bool) $r->is_default])
            ->values()->all();
    }

    private function transliterationResource(): ?QuranResource
    {
        $qfId = (int) config('quran.defaults.transliteration_qf_id', 57);

        return $this->resources->active(ResourceKind::TRANSLATION)->first(static fn (QuranResource $r): bool => $r->qf_id === $qfId);
    }

    /**
     * @return array<string, mixed>
     */
    private function playerReciter(QuranResource $reciter, string $locale, string $surahAudioUrl, bool $hasTimestamps): array
    {
        $kindLabel = __('quran.public.kind_chapter');
        $nameAr = (string) ($reciter->getTranslation('name', 'ar', false) ?? '');
        $label = implode(' · ', array_filter([$reciter->style?->label($locale), $reciter->qirat]));

        return [
            'id' => $reciter->id,
            'kind' => $reciter->kind->value,
            'nameAr' => $nameAr !== '' ? $nameAr : $reciter->name,
            'nameEn' => $nameAr === '' ? $reciter->name : $reciter->name,
            'slug' => $reciter->slug,
            'profileId' => $reciter->id,
            'profileName' => $reciter->displayName($locale),
            'recordingLabel' => $label,
            'kindLabel' => $kindLabel,
            'sourceHint' => ReciterLabeller::sourceHint($reciter->name),
            'isDefault' => $reciter->is_default,
            'surahAudioUrl' => $surahAudioUrl,
            'hasTimestamps' => $hasTimestamps,
            'sourceId' => null,
            'sourceRevision' => null,
        ];
    }
}
