<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Blog\Repositories;

use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Models\TagTranslation;
use App\Domain\Blog\Repositories\TagRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit test for TagRepository::recalculateAllUsageCounts()
 *
 * Verifies that the single GROUP BY aggregation correctly updates
 * tag usage counts and returns the number of updated tags.
 */
final class TagRepositoryTest extends TestCase
{
    private TagRepository $repository;

    /**
     * Define environment setup - use SQLite in-memory for fast unit tests
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal schema without running all migrations
        $this->createSchema();

        $this->repository = new TagRepository();
    }

    protected function tearDown(): void
    {
        // Drop all tables after each test
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tag_translations');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('content_versions');
        Schema::dropIfExists('articles');

        parent::tearDown();
    }

    private function createSchema(): void
    {
        // Create tags table
        Schema::create('tags', function ($table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });

        // Create tag_translations table
        Schema::create('tag_translations', function ($table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['tag_id', 'locale']);
        });

        // Create articles table (minimal)
        Schema::create('articles', function ($table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->string('featured_image')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('writer_id')->nullable();
            $table->timestamps();
        });

        // Create taggables pivot table
        Schema::create('taggables', function ($table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
        });

        // Create content_versions table (needed by Article's HasVersions trait)
        Schema::create('content_versions', function ($table) {
            $table->id();
            $table->morphs('versionable');
            $table->integer('version_number')->default(1);
            $table->enum('change_type', ['created', 'updated', 'published', 'unpublished', 'deleted', 'restored'])->default('updated');
            $table->foreignId('changed_by')->nullable();
            $table->string('change_summary', 500)->nullable();
            $table->json('diff')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Register Article morph map
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'article' => \App\Models\Article::class,
        ]);
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

    private function createArticle(string $slug): \App\Models\Article
    {
        return \App\Models\Article::create([
            'slug' => $slug,
            'status' => 'published',
            'featured_image' => null,
            'published_at' => now(),
            'writer_id' => 1,
        ]);
    }

    private function attachTagToArticle(Tag $tag, \App\Models\Article $article): void
    {
        DB::table('taggables')->insert([
            'tag_id' => $tag->id,
            'taggable_type' => $article->getMorphClass(),
            'taggable_id' => $article->id,
        ]);
    }

    public function test_tags_with_correct_usage_count_are_not_updated(): void
    {
        $tag1 = $this->createTag('correct-tag-1', 2);
        $tag2 = $this->createTag('correct-tag-2', 1);
        $article1 = $this->createArticle('article-1');
        $article2 = $this->createArticle('article-2');

        $this->attachTagToArticle($tag1, $article1);
        $this->attachTagToArticle($tag1, $article2);
        $this->attachTagToArticle($tag2, $article1);

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        $this->assertSame(0, $updatedCount);
        $this->assertSame(2, $tag1->fresh()->usage_count);
        $this->assertSame(1, $tag2->fresh()->usage_count);
    }

    public function test_tags_with_incorrect_usage_count_are_updated(): void
    {
        $tag1 = $this->createTag('incorrect-tag-1', 0);
        $tag2 = $this->createTag('incorrect-tag-2', 5);
        $article1 = $this->createArticle('article-1');
        $article2 = $this->createArticle('article-2');

        $this->attachTagToArticle($tag1, $article1);
        $this->attachTagToArticle($tag1, $article2);
        $this->attachTagToArticle($tag2, $article1);

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        $this->assertSame(2, $updatedCount);
        $this->assertSame(2, $tag1->fresh()->usage_count);
        $this->assertSame(1, $tag2->fresh()->usage_count);
    }

    public function test_tags_with_zero_usage_are_set_to_zero(): void
    {
        $tag1 = $this->createTag('unused-tag-1', 3);
        $tag2 = $this->createTag('unused-tag-2', 7);

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        $this->assertSame(2, $updatedCount);
        $this->assertSame(0, $tag1->fresh()->usage_count);
        $this->assertSame(0, $tag2->fresh()->usage_count);
    }

    public function test_returns_correct_count_of_updated_tags(): void
    {
        $correct1 = $this->createTag('correct-1', 1);
        $correct2 = $this->createTag('correct-2', 0);
        $incorrect1 = $this->createTag('incorrect-1', 0);
        $incorrect2 = $this->createTag('incorrect-2', 10);
        $incorrect3 = $this->createTag('incorrect-3', 5);

        $article = $this->createArticle('article-1');

        $this->attachTagToArticle($correct1, $article);
        $this->attachTagToArticle($incorrect1, $article);
        $this->attachTagToArticle($incorrect2, $article);

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        $this->assertSame(3, $updatedCount);
    }

    public function test_cache_is_cleared_after_recalculation(): void
    {
        Cache::put('tags:count', 100, now()->addHour());
        Cache::put('tags:total_usage', 200, now()->addHour());
        Cache::put('tags:most_used', ['id' => 1], now()->addHour());

        $tag = $this->createTag('test-tag', 5);

        $this->repository->recalculateAllUsageCounts();

        $this->assertNull(Cache::get('tags:count'));
        $this->assertNull(Cache::get('tags:total_usage'));
        $this->assertNull(Cache::get('tags:most_used'));
    }

    public function test_handles_mixed_correct_and_incorrect_counts(): void
    {
        $correct = $this->createTag('correct', 2);
        $tooHigh = $this->createTag('too-high', 10);
        $tooLow = $this->createTag('too-low', 0);
        $zero = $this->createTag('zero', 0);

        $article1 = $this->createArticle('article-1');
        $article2 = $this->createArticle('article-2');

        $this->attachTagToArticle($correct, $article1);
        $this->attachTagToArticle($correct, $article2);
        $this->attachTagToArticle($tooHigh, $article1);
        $this->attachTagToArticle($tooLow, $article1);
        $this->attachTagToArticle($tooLow, $article2);

        $updatedCount = $this->repository->recalculateAllUsageCounts();

        $this->assertSame(2, $updatedCount);
        $this->assertSame(2, $correct->fresh()->usage_count);
        $this->assertSame(1, $tooHigh->fresh()->usage_count);
        $this->assertSame(2, $tooLow->fresh()->usage_count);
        $this->assertSame(0, $zero->fresh()->usage_count);
    }

    public function test_handles_empty_tags_table(): void
    {
        $updatedCount = $this->repository->recalculateAllUsageCounts();

        $this->assertSame(0, $updatedCount);
    }

    public function test_single_aggregation_query_pattern(): void
    {
        $tag1 = $this->createTag('tag-1', 0);
        $tag2 = $this->createTag('tag-2', 0);
        $tag3 = $this->createTag('tag-3', 0);

        $article = $this->createArticle('article-1');

        $this->attachTagToArticle($tag1, $article);
        $this->attachTagToArticle($tag2, $article);
        $this->attachTagToArticle($tag3, $article);

        DB::enableQueryLog();
        $this->repository->recalculateAllUsageCounts();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $aggregationQueries = array_filter($queries, function ($query) {
            return str_contains($query['query'], 'group by');
        });

        $this->assertCount(1, $aggregationQueries, 'Should use exactly one GROUP BY query');

        $updateQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'update') &&
                   str_contains(strtolower($query['query']), 'tags');
        });

        $this->assertCount(3, $updateQueries, 'Should execute individual updates for each tag');
    }
}
