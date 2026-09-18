<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use App\Domain\Quran\Import\QuranFoundation\Mappers\ResourceMapper;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The QF resource catalogue → quran_resources, never enabling anything.
 *
 * 1. Every kind with a `/resources/*` catalogue, names translated into the site
 *    locales QF actually translated, plus the optional `/info` blurb.
 * 2. Discovery through a bootstrap sync (no rows applied): word-by-word resources
 *    and mushafs exist only in sync, and only some chapter reciters are shareable
 *    through it — those are flagged `metadata.sync_available`.
 *
 * Editor-controlled columns are never written for existing rows.
 */
final class CatalogueImporter
{
    private const LOCALES = ['ar', 'fr', 'tr', 'ur'];

    /** QF's `translated_name.language_name` for each site locale. */
    private const LANGUAGE_NAMES = ['ar' => 'arabic', 'fr' => 'french', 'tr' => 'turkish', 'ur' => 'urdu', 'en' => 'english'];

    public function __construct(
        private readonly Endpoints $qf,
        private readonly ImportRepositoryInterface $repository,
    ) {}

    /**
     * @param  list<ResourceKind>|null  $kinds  null = every kind with a catalogue
     */
    public function run(?array $kinds = null, bool $withInfo = true, bool $discover = true, bool $dryRun = false): ImportReport
    {
        $report = new ImportReport('catalogue');
        $kinds ??= array_values(array_filter(ResourceKind::cases(), static fn (ResourceKind $k): bool => $k->qfCataloguePath() !== null));

        $isoByName = $this->languageIsoByName();

        foreach ($kinds as $kind) {
            if ($kind->qfCataloguePath() === null) {
                continue;
            }
            $this->importKind($kind, $isoByName, $withInfo, $dryRun, $report);
        }

        if (! $dryRun && array_filter($kinds, static fn (ResourceKind $k): bool => $k->isReciter()) !== []) {
            $this->cacheRecitationStyles($report);
        }

        if ($discover) {
            $this->discover($report, $dryRun);
        }

        if (! $dryRun) {
            QuranCacheKeys::flushResources();
        }

        return $report;
    }

    /**
     * Rows QF lists for a kind that are not imported yet (admin "Import from QF" picker).
     *
     * @return list<array{qf_id: int, name: string, author_name: ?string, language: ?string}>
     */
    public function preview(ResourceKind $kind): array
    {
        $key = $kind->qfCatalogueKey() ?? throw new \InvalidArgumentException("{$kind->value} has no QF catalogue");
        $items = $this->qf->catalogue($kind, 'en')[$key] ?? [];
        $imported = DB::table('quran_resources')->where('kind', $kind->value)->whereNotNull('qf_id')->pluck('qf_id')->map(static fn (mixed $v): int => (int) $v)->all();

        $rows = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && ! in_array($id, $imported, true)) {
                $rows[] = [
                    'qf_id' => $id,
                    'name' => (string) ($item['name'] ?? $item['reciter_name'] ?? $id),
                    'author_name' => isset($item['author_name']) ? (string) $item['author_name'] : null,
                    'language' => isset($item['language_name']) ? (string) $item['language_name'] : null,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $isoByName
     */
    private function importKind(ResourceKind $kind, array $isoByName, bool $withInfo, bool $dryRun, ImportReport $report): void
    {
        $key = (string) $kind->qfCatalogueKey();
        $payload = $this->qf->catalogue($kind, 'en');
        if (! isset($payload[$key]) || ! is_array($payload[$key])) {
            throw new QfPayloadException((string) $kind->qfCataloguePath(), $key);
        }

        $items = array_values($payload[$key]);
        $report->expected += count($items);
        $report->gate(count($items) > 0, "{$kind->value}: QF catalogue returned ".count($items).' row(s)');

        $rows = $this->withUniqueSlugs($kind, array_map(
            static fn (array $item): array => ResourceMapper::row($kind, $item, $isoByName),
            $items,
        ));

        // Names QF translated into our locales, keyed by qf id.
        $names = [];
        foreach ($items as $item) {
            $english = ResourceMapper::translatedName($item, 'english');
            if ($english !== null) {
                $names[(int) $item['id']]['en'] = $english;
            }
        }
        foreach (self::LOCALES as $locale) {
            foreach ($this->qf->catalogue($kind, $locale)[$key] ?? [] as $item) {
                $name = ResourceMapper::translatedName($item, self::LANGUAGE_NAMES[$locale]);
                if ($name !== null) {
                    $names[(int) $item['id']][$locale] = $name;
                }
            }
        }

        if ($dryRun) {
            $report->note("dry run: {$kind->value} ".count($rows).' row(s) not written');

            return;
        }

        $updateColumns = ResourceMapper::DESCRIPTIVE_COLUMNS;
        if ($kind->isReciter()) {
            // Keep an editor's choice of fetch source; only fill it when empty.
            $rows = $this->keepExistingExternalSource($kind, $rows);
            $updateColumns = [...$updateColumns, 'external_source', 'external_ref_id'];
        }

        $report->add($this->repository->upsert('quran_resources', $rows, ['kind', 'qf_id'], $updateColumns));

        $idsByQf = DB::table('quran_resources')->where('kind', $kind->value)->whereNotNull('qf_id')->pluck('id', 'qf_id')->all();
        $translationRows = [];
        foreach ($names as $qfId => $byLocale) {
            if (! isset($idsByQf[$qfId])) {
                continue;
            }
            foreach ($byLocale as $locale => $name) {
                $translationRows[] = ['resource_id' => (int) $idsByQf[$qfId], 'locale' => $locale, 'name' => $name, 'is_ready' => true];
            }
        }
        $this->repository->upsert('quran_resource_translations', $translationRows, ['resource_id', 'locale'], ['name', 'is_ready']);

        if ($withInfo && $kind->hasQfInfo()) {
            $this->importInfo($kind, $idsByQf, $report);
        }
    }

    /**
     * @param  array<int|string, int|string>  $idsByQf
     */
    private function importInfo(ResourceKind $kind, array $idsByQf, ImportReport $report): void
    {
        $missing = 0;
        foreach ($idsByQf as $qfId => $id) {
            try {
                $info = $this->qf->resourceInfo($kind, (int) $qfId)['info']['info'] ?? null;
            } catch (QfApiException $e) {
                if ($e->status !== 404) {
                    throw $e;
                }
                $info = null;
            }

            if (! is_string($info) || trim($info) === '') {
                $missing++;

                continue;
            }

            DB::table('quran_resources')->where('id', $id)->where(function ($q) use ($info): void {
                $q->whereNull('info')->orWhere('info', '!=', $info);
            })->update(['info' => $info, 'updated_at' => now()]);
        }

        if ($missing > 0) {
            $report->note("{$kind->value}: {$missing} resource(s) have no QF info text");
        }
    }

    /**
     * A QF slug that collides with another row of the same kind (a manual row, or
     * a different QF id) gets the QF id appended — otherwise MySQL's
     * ON DUPLICATE KEY UPDATE would match the slug key and overwrite that row.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function withUniqueSlugs(ResourceKind $kind, array $rows): array
    {
        $owners = DB::table('quran_resources')->where('kind', $kind->value)->pluck('qf_id', 'slug')->all();
        $seen = [];

        foreach ($rows as &$row) {
            $slug = (string) $row['slug'];
            $owner = $owners[$slug] ?? null;
            $takenByOther = array_key_exists($slug, $owners) && (int) $owner !== (int) $row['qf_id'];
            if ($takenByOther || isset($seen[$slug])) {
                $slug = substr($slug, 0, 100).'-'.$row['qf_id'];
                $row['slug'] = $slug;
            }
            $seen[$slug] = true;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function keepExistingExternalSource(ResourceKind $kind, array $rows): array
    {
        $existing = DB::table('quran_resources')->where('kind', $kind->value)->whereNotNull('qf_id')
            ->whereNotNull('external_source')->get(['qf_id', 'external_source', 'external_ref_id'])->keyBy('qf_id');

        foreach ($rows as &$row) {
            $current = $existing->get($row['qf_id']);
            if ($current !== null) {
                $row['external_source'] = $current->external_source;
                $row['external_ref_id'] = $current->external_ref_id;
            }
        }
        unset($row);

        return $rows;
    }

    private function cacheRecitationStyles(ImportReport $report): void
    {
        $styles = $this->qf->recitationStyles()['recitation_styles'] ?? null;
        if (! is_array($styles)) {
            $report->fail('resources/recitation_styles', 'unexpected payload');

            return;
        }

        Cache::forever(QuranCacheKeys::recitationStyles(), $styles);
    }

    /**
     * Walk a bootstrap sync (applying nothing) to learn which resources exist only in
     * sync (word-by-word, mushafs) and which chapter reciters are shareable through it.
     */
    private function discover(ImportReport $report, bool $dryRun): void
    {
        $groups = [
            SyncResourceGroup::WORD_BY_WORD_TRANSLATIONS,
            SyncResourceGroup::WORD_BY_WORD_TRANSLITERATIONS,
            SyncResourceGroup::MUSHAFS,
            SyncResourceGroup::CHAPTER_RECITATIONS,
        ];
        $filter = implode(';', array_map(static fn (SyncResourceGroup $g): string => $g->value.':*', $groups));

        $found = [];
        $page = $this->qf->sync(['bootstrap' => 'true', 'resources' => $filter, 'per_page' => 100]);
        $pages = 1;
        while (true) {
            foreach ($page['sync']['mutations'] ?? [] as $mutation) {
                $found[(string) ($mutation['resource_group'] ?? '')][] = (int) ($mutation['resource_id'] ?? 0);
            }
            if (! ($page['sync']['has_more'] ?? false) || ! is_string($page['sync']['next_page_url'] ?? null)) {
                break;
            }
            if (++$pages > 50) {
                $report->fail('discovery', 'sync bootstrap exceeded 50 pages; stopped');
                break;
            }
            $page = $this->qf->followRelative($page['sync']['next_page_url']);
        }

        foreach ($found as $group => $ids) {
            $report->note("discovered {$group}: ".count($ids).' ('.implode(',', array_slice($ids, 0, 20)).(count($ids) > 20 ? ',…' : '').')');
        }

        if ($dryRun) {
            return;
        }

        $this->discoveredGlossResources(ResourceKind::WORD_TRANSLATION, $found[SyncResourceGroup::WORD_BY_WORD_TRANSLATIONS->value] ?? [], $report);
        $this->discoveredGlossResources(ResourceKind::WORD_TRANSLITERATION, $found[SyncResourceGroup::WORD_BY_WORD_TRANSLITERATIONS->value] ?? [], $report);

        foreach ($found[SyncResourceGroup::MUSHAFS->value] ?? [] as $mushafId) {
            DB::table('quran_mushafs')->insertOrIgnore([
                'id' => $mushafId,
                'name' => "Mushaf {$mushafId}",
                'is_active' => false,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $shareable = $found[SyncResourceGroup::CHAPTER_RECITATIONS->value] ?? [];
        foreach (QuranResource::query()->select(['id', 'qf_id', 'metadata'])->ofKind(ResourceKind::CHAPTER_RECITER)->whereNotNull('qf_id')->get() as $reciter) {
            $metadata = $reciter->metadata ?? [];
            $available = in_array((int) $reciter->qf_id, $shareable, true);
            if (($metadata['sync_available'] ?? null) !== $available) {
                $metadata['sync_available'] = $available;
                $reciter->forceFill(['metadata' => $metadata])->saveQuietly();
            }
        }
    }

    /**
     * @param  list<int>  $qfIds
     */
    private function discoveredGlossResources(ResourceKind $kind, array $qfIds, ImportReport $report): void
    {
        $rows = [];
        foreach ($qfIds as $qfId) {
            $rows[] = [
                'kind' => $kind->value,
                'qf_id' => $qfId,
                'qf_content_id' => $qfId,
                'source' => ResourceSource::QF->value,
                'slug' => ($kind === ResourceKind::WORD_TRANSLATION ? 'qf-wbw-translation-' : 'qf-wbw-transliteration-').$qfId,
                // Names/languages are not in any catalogue; the snapshot fills language_iso.
                'name' => ($kind === ResourceKind::WORD_TRANSLATION ? 'Word by word translation #' : 'Word by word transliteration #').$qfId,
            ];
        }

        if ($rows !== []) {
            $report->add($this->repository->upsert('quran_resources', $this->withUniqueSlugs($kind, $rows), ['kind', 'qf_id'], ['qf_content_id']));
        }
    }

    /**
     * @return array<string, string> lower-case QF language name => iso code
     */
    private function languageIsoByName(): array
    {
        $map = DB::table('quran_languages')->pluck('iso_code', 'name')
            ->mapWithKeys(static fn (string $iso, string $name): array => [strtolower($name) => $iso])->all();

        return $map + array_flip(self::LANGUAGE_NAMES);
    }
}
