<?php

declare(strict_types=1);

namespace Database\Seeders\Blog;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Models\Tag;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Base Blog Article Seeder
 *
 * Shared machinery for the article batch seeders. Each batch seeder
 * provides article definitions; this class persists them idempotently.
 *
 * Idempotency: the English locale slug is the natural key — if an
 * article_locale row with that slug already exists, the article is skipped.
 */
abstract class BaseBlogArticleSeeder extends Seeder
{
    private const WORDS_PER_MINUTE = 200;

    public function run(): void
    {
        $writerId = $this->resolveWriterId();
        $created = 0;
        $skipped = 0;

        foreach ($this->articles() as $definition) {
            if ($this->articleExists($definition['slug_en'])) {
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($definition, $writerId): void {
                $this->createArticle($definition, $writerId);
            });
            $created++;
        }

        $this->command?->info(sprintf(
            '%s: %d created, %d already present.',
            class_basename(static::class),
            $created,
            $skipped
        ));
    }

    /**
     * Article definitions for this batch.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function articles(): array;

    private function articleExists(string $englishSlug): bool
    {
        return ArticleLocale::query()
            ->select(['id'])
            ->where('locale', 'en')
            ->where('slug', $englishSlug)
            ->exists();
    }

    private function resolveWriterId(): int
    {
        $writer = User::query()
            ->select(['id'])
            ->where('email', 'supm@dwit.com')
            ->first()
            ?? User::query()->select(['id'])->orderBy('id')->firstOrFail();

        return $writer->id;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function createArticle(array $definition, int $writerId): void
    {
        $categoryId = $this->resolveCategoryId($definition['category']);
        $publishedAt = now()
            ->subDays((int) $definition['days_ago'])
            ->setTime(random_int(7, 21), random_int(0, 59));

        $article = Article::create([
            'thumb' => null,
            'writer_id' => $writerId,
            'read_time' => $this->calculateReadTime($definition['ar']['body']),
            'views' => random_int(100, 5000),
            'is_featured' => (bool) ($definition['featured'] ?? false),
            'is_pinned' => false,
            'category_id' => $categoryId,
            'hijri_date' => $definition['hijri_date'] ?? null,
            'status' => 'published',
            'scheduled_at' => null,
        ]);

        foreach (['ar', 'en'] as $locale) {
            $content = $definition[$locale];
            $slug = $locale === 'en' ? $definition['slug_en'] : $definition['slug_ar'];

            $article->locales()->create([
                'locale' => $locale,
                'title' => $content['title'],
                'slug' => $slug,
                'description' => $content['description'] ?? $content['excerpt'],
                'excerpt' => $content['excerpt'],
                'content' => [
                    'body' => $content['body'],
                    'toc' => $this->generateToc($content['body']),
                    'summary' => $content['description'] ?? $content['excerpt'],
                ],
                'seo_metadata' => [
                    'title' => $content['title'],
                    'description' => $content['excerpt'],
                ],
                'is_ready' => true,
            ]);
        }

        $article->categories()->syncWithoutDetaching([$categoryId]);
        $article->tags()->syncWithoutDetaching($this->resolveTagIds($definition['tags']));
        $this->attachSeries($article, $definition['series'] ?? []);
        $this->backdate($article, $publishedAt);
    }

    private function resolveCategoryId(string $slug): int
    {
        return Category::query()
            ->select(['id'])
            ->where('slug', $slug)
            ->firstOrFail()
            ->id;
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<int, int>
     */
    private function resolveTagIds(array $slugs): array
    {
        return Tag::query()
            ->select(['id'])
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<string, int>  $seriesPositions  Series slug => position.
     */
    private function attachSeries(Article $article, array $seriesPositions): void
    {
        if ($seriesPositions === []) {
            return;
        }

        $seriesIds = Series::query()
            ->select(['id', 'slug'])
            ->whereIn('slug', array_keys($seriesPositions))
            ->pluck('id', 'slug');

        $pivot = [];
        foreach ($seriesPositions as $slug => $position) {
            if (isset($seriesIds[$slug])) {
                $pivot[$seriesIds[$slug]] = ['position' => $position];
            }
        }

        $article->series()->syncWithoutDetaching($pivot);
    }

    /**
     * Stagger publication dates over the past year by writing timestamps
     * directly (created_at is not mass-assignable on Article).
     */
    private function backdate(Article $article, \DateTimeInterface $publishedAt): void
    {
        Article::query()
            ->whereKey($article->id)
            ->update(['created_at' => $publishedAt, 'updated_at' => $publishedAt]);
    }

    /**
     * Multibyte-safe read time (str_word_count ignores Arabic words).
     */
    private function calculateReadTime(string $html): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags($html))) ?: [];

        return max(1, (int) ceil(count($words) / self::WORDS_PER_MINUTE));
    }

    /**
     * Build a TOC from h2/h3 headings. Mirrors TocGeneratorService but adds
     * a positional fallback anchor for Arabic headings (Str::slug drops them).
     *
     * @return array<int, array{title: string, link: string, level: int}>
     */
    private function generateToc(string $html): array
    {
        $toc = [];

        preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h[2-3]>/iu', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $text = strip_tags($match[2]);
            $anchor = Str::slug($text);

            $toc[] = [
                'title' => $text,
                'link' => $anchor !== '' ? $anchor : 'section-'.($index + 1),
                'level' => (int) $match[1],
            ];
        }

        return $toc;
    }
}
