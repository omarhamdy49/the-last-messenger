<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\SearchRepositoryInterface;
use App\Domain\Quran\Support\ArabicNormalizer;

/**
 * Local Quran search in the Quran Foundation Search API shape (`navigation[]`,
 * `verses[]`). Arabic matches the diacritic-free imlaei text; translations match the
 * selected (or locale default) translation resources.
 */
final class SearchService
{
    public function __construct(
        private readonly SearchRepositoryInterface $search,
        private readonly NavigationService $navigation,
        private readonly ReaderService $reader,
        private readonly ResourceRepositoryInterface $resources,
    ) {}

    /**
     * @return array{navigation: list<array<string, string>>, verses: list<array<string, mixed>>}
     */
    public function quick(string $query, string $locale, int $navigationLimit = 5, int $verseLimit = 5): array
    {
        $query = mb_substr(trim($query), 0, 100);

        return [
            'navigation' => $this->navigation->resolveQuick($query, $locale, $navigationLimit),
            'verses' => mb_strlen($query) < 2 ? [] : $this->advanced($query, [], false, false, 1, $verseLimit, $locale)['verses'],
        ];
    }

    /**
     * @param  list<int>  $translationIds
     * @return array{query: string, verses: list<array<string, mixed>>, pagination: array{current_page: int, per_page: int, total_records: int, total_pages: int}}
     */
    public function advanced(string $query, array $translationIds, bool $exact, bool $highlight, int $page, int $size, string $locale): array
    {
        $query = mb_substr(trim($query), 0, 100);
        $size = max(1, min(50, $size));
        $page = max(1, $page);

        if ($translationIds === []) {
            $default = $this->reader->defaultTranslation($locale);
            $translationIds = $default === null ? [] : [$default->id];
        } else {
            $translationIds = $this->resources->activeByIds(ResourceKind::TRANSLATION, array_slice($translationIds, 0, 5))->pluck('id')->all();
        }

        $arabic = ArabicNormalizer::stripMarks($query);
        $terms = $exact ? [$query] : array_values(array_filter(preg_split('/\s+/u', $query) ?: []));
        if ($terms === []) {
            return ['query' => $query, 'verses' => [], 'pagination' => ['current_page' => $page, 'per_page' => $size, 'total_records' => 0, 'total_pages' => 0]];
        }

        $result = $this->search->verses(
            arabicTerms: $exact ? [$arabic] : array_values(array_filter(preg_split('/\s+/u', $arabic) ?: [])),
            translationTerms: $terms,
            translationIds: $translationIds,
            page: $page,
            size: $size,
        );

        $names = collect($this->reader->chapterList($locale))->pluck('nameAr', 'number');

        return [
            'query' => $query,
            'verses' => array_map(function (object $row) use ($names, $terms, $highlight): array {
                $translation = $row->translation === null ? null : ReaderService::plain((string) $row->translation);

                return [
                    'verse_key' => (string) $row->verse_key,
                    'verse_id' => (int) $row->id,
                    'chapter' => (int) $row->chapter_id,
                    'verse' => (int) $row->verse_number,
                    'chapter_name' => (string) ($names[(int) $row->chapter_id] ?? ''),
                    'text' => (string) $row->text_uthmani,
                    'translation' => $translation === null ? null : ($highlight ? self::highlight($translation, $terms) : e($translation)),
                    'url' => route('public.quran.surah', (int) $row->chapter_id).'#v'.(int) $row->verse_number,
                ];
            }, $result['rows']),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $size,
                'total_records' => $result['total'],
                'total_pages' => (int) ceil($result['total'] / $size),
            ],
        ];
    }

    /**
     * HTML-escape the text, then wrap each term in <em>. Safe to print unescaped.
     *
     * @param  list<string>  $terms
     */
    public static function highlight(string $text, array $terms): string
    {
        $escaped = e($text);
        foreach ($terms as $term) {
            $term = e(trim($term));
            if (mb_strlen($term) >= 2) {
                $escaped = (string) preg_replace('/'.preg_quote($term, '/').'/iu', '<em>$0</em>', $escaped);
            }
        }

        return $escaped;
    }
}
