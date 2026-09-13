<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Blog;

use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Models\TagTranslation;
use App\Domain\Blog\Repositories\TagRepository;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration test for tag recalculation with realistic data (100+ tags).
 *
 * Verifies that TagRepository::recalculateAllUsageCounts() correctly handles
 * large datasets while maintaining low query counts through optimized SQL patterns.
 */
final class TagRecalculationTest extends TestCase
{
    use RefreshDatabase;

    private TagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->repository = new TagRepository;
    }

    private function createTag(string $slug, int $usageCount = 0): Tag
    {
        $tag = Tag::create([
            'slug' => $slug,
            'type' => 'general',
            'usage_count' => $usageCount,
        ]);

        TagTranslation::create([
            'tag_id' => $tag->id,
            'locale' => 'ar',
            'name' => "وسم {$slug}",
        ]);

        return $tag;
    }

    private function createArticle(string $slug): Article
    {
        return Article::create([
            'slug' => $slug,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
            'writer_id' => null,
            'title' => ['ar' => "مقال {$slug}", 'en' => "Article {$slug}"],
            'content' => ['ar' => 'محتوى المقال', 'en' => 'Article content'],
        ]);
    }

    private function attachTagToArticle(Tag $tag, Article $article): void
    {
        DB::table('taggables')->insert([
            'tag_id' => $tag->id,
            'taggable_type' => $article->getMorphClass(),
            'taggable_id' => $article->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Realistic Data Tests (100+ tags)
    // ------------------------------------------------------------------

    public function test_recalculates_usage_counts_for_100_plus_tags_efficiently(): void
    {
        // Create 150 tags with incorrect usage counts
        $tags = [];
        for ($i = 1; $i <= 150; $i++) {
            $tags[] = $this->createTag("tag-{$i}", rand(5, 20));
        }

        // Create 50 articles
        $articles = [];
        for ($i = 1; $i <= 50; $i++) {
            $articles[] = $this->createArticle("article-{$i}");
        }

        // Randomly assign tags to articles (each article gets 3-7 tags)
        foreach ($articles as $article) {
            $tagCount = rand(3, 7);
            $selectedTags = array_rand($tags, $tagCount);

            if (! is_array($selectedTags)) {
                $selectedTags = [$selectedTags];
            }

            foreach ($selectedTags as $tagIndex) {
                $this->attachTagToArticle($tags[$tagIndex], $article);
            }
        }

        // Enable query logging to verify efficiency
        DB::enableQueryLog();
        $updatedCount = $this->repository->recalculateAllUsageCounts();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify that updates occurred
        $this->assertGreaterThan(0, $updatedCount);

        // Verify low query count - should have:
        // 1. One SELECT to get all tags
        // 2. One GROUP BY aggregation query
        // 3. Individual UPDATEs for changed tags (but NOT 150+ queries)
        $selectQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'select');
        });

        $groupByQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'group by');
        });

        $updateQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'update') &&
                   str_contains(strtolower($query['query']), 'tags');
        });

        // Should have exactly one GROUP BY query (the key optimization)
        $this->assertCount(1, $groupByQueries, 'Should use exactly one GROUP BY query for aggregation');

        // Should have SELECT queries but not excessive
        $this->assertLessThan(10, count($selectQueries), 'Should not have excessive SELECT queries');

        // One batched UPDATE regardless of how many tags changed — that is the
        // N+1 this test exists to catch.
        $this->assertCount(1, $updateQueries, 'Should write every changed tag in one UPDATE');

        // Total queries must not scale with the 150 tags.
        $this->assertLessThan(10, count($queries), 'Query count must be independent of tag count');
    }

    public function test_correctly_calculates_usage_counts_for_large_dataset(): void
    {
        // Create 120 tags
        $tags = [];
        for ($i = 1; $i <= 120; $i++) {
            $tags[] = $this->createTag("tag-{$i}", 0);
        }

        // Create 30 articles
        $articles = [];
        for ($i = 1; $i <= 30; $i++) {
            $articles[] = $this->createArticle("article-{$i}");
        }

        // Attach specific tags to specific articles for verification
        // Tag 0: 5 articles
        for ($i = 0; $i < 5; $i++) {
            $this->attachTagToArticle($tags[0], $articles[$i]);
        }

        // Tag 1: 10 articles
        for ($i = 0; $i < 10; $i++) {
            $this->attachTagToArticle($tags[1], $articles[$i]);
        }

        // Tag 2: 1 article
        $this->attachTagToArticle($tags[2], $articles[0]);

        // Tags 3-119: no articles (should remain 0)

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        // Verify counts are correct
        $this->assertSame(5, $tags[0]->fresh()->usage_count);
        $this->assertSame(10, $tags[1]->fresh()->usage_count);
        $this->assertSame(1, $tags[2]->fresh()->usage_count);
        $this->assertSame(0, $tags[3]->fresh()->usage_count);
        $this->assertSame(0, $tags[119]->fresh()->usage_count);
    }

    public function test_handles_mixed_scenarios_with_large_dataset(): void
    {
        // Create 100 tags with various initial states
        $tags = [];

        // 20 tags with correct counts (should not be updated)
        for ($i = 1; $i <= 20; $i++) {
            $tags[] = $this->createTag("correct-{$i}", 0);
        }

        // 40 tags with incorrect high counts
        for ($i = 1; $i <= 40; $i++) {
            $tags[] = $this->createTag("high-{$i}", 50);
        }

        // 40 tags with incorrect low counts
        for ($i = 1; $i <= 40; $i++) {
            $tags[] = $this->createTag("low-{$i}", 0);
        }

        // Create 25 articles
        $articles = [];
        for ($i = 1; $i <= 25; $i++) {
            $articles[] = $this->createArticle("article-{$i}");
        }

        // Attach high and low tags to articles (1 tag per article)
        for ($i = 0; $i < 25; $i++) {
            // Attach a "high" tag (should be updated from 50 to 1)
            if ($i < 20) {
                $this->attachTagToArticle($tags[20 + $i], $articles[$i]);
            }

            // Attach a "low" tag (should be updated from 0 to 1)
            if ($i < 20) {
                $this->attachTagToArticle($tags[60 + $i], $articles[$i]);
            }
        }

        // "correct" tags remain at 0 (correct)

        DB::enableQueryLog();
        $updatedCount = $this->repository->recalculateAllUsageCounts();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should only update the tags that have incorrect counts (40 high + 20 low = 60)
        // 20 "high" tags get updated from 50 to 1
        // 20 "low" tags get updated from 0 to 1
        // 20 "high" tags with no articles get updated from 50 to 0
        // 20 "low" tags with no articles stay at 0 (no update)
        $this->assertSame(60, $updatedCount);

        // Verify query efficiency
        $this->assertLessThan(80, count($queries), 'Total queries should be efficient for 100 tags');
    }

    public function test_performance_with_highly_used_tags(): void
    {
        // Create 100 tags
        $tags = [];
        for ($i = 1; $i <= 100; $i++) {
            $tags[] = $this->createTag("tag-{$i}", 0);
        }

        // Create 200 articles (simulate realistic blog)
        $articles = [];
        for ($i = 1; $i <= 200; $i++) {
            $articles[] = $this->createArticle("article-{$i}");
        }

        // Each article gets 5 tags
        foreach ($articles as $index => $article) {
            // Distribute tags evenly
            for ($j = 0; $j < 5; $j++) {
                $tagIndex = ($index * 5 + $j) % 100;
                $this->attachTagToArticle($tags[$tagIndex], $article);
            }
        }

        DB::enableQueryLog();
        $updatedCount = $this->repository->recalculateAllUsageCounts();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // All 100 tags should be updated (from 0 to their actual counts)
        $this->assertSame(100, $updatedCount);

        // Verify each tag has approximately 10 uses (200 articles * 5 tags / 100 tags)
        $totalUsage = (int) Tag::sum('usage_count');
        $this->assertSame(1000, $totalUsage);

        // Verify efficient query pattern even with 1000 total taggings
        $groupByQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'group by');
        });

        $this->assertCount(1, $groupByQueries, 'Should use single GROUP BY even with 1000 taggings');
        $this->assertLessThan(120, count($queries), 'Should handle 1000 taggings efficiently');
    }

    public function test_maintains_correctness_with_duplicate_prevention(): void
    {
        // Create 50 tags
        $tags = [];
        for ($i = 1; $i <= 50; $i++) {
            $tags[] = $this->createTag("tag-{$i}", 0);
        }

        $article = $this->createArticle('article-1');

        // Attach same tag multiple times (should be prevented by unique constraint)
        $this->attachTagToArticle($tags[0], $article);

        try {
            $this->attachTagToArticle($tags[0], $article);
            $this->fail('Should not allow duplicate tag attachment');
        } catch (\Exception $e) {
            // Expected - unique constraint violation
            $this->assertTrue(true);
        }

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        // Tag should have count of 1, not 2
        $this->assertSame(1, $tags[0]->fresh()->usage_count);
    }
}
