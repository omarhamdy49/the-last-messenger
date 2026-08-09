<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use App\Domain\Shared\Services\SearchService;
use App\Domain\Topics\Enums\TopicItemType;
use App\Domain\Topics\Models\Topic;
use App\Domain\Topics\Models\TopicItem;
use App\Domain\Topics\Repositories\TopicRepositoryInterface;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
use Database\Factories\MediaProgramFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PublicVisibilityGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ar');
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
    }

    public function test_core_001_unified_search_excludes_draft_articles_from_results_and_counts(): void
    {
        $published = $this->createArticle('published-search', Article::STATUS_PUBLISHED, 'Gate search article');
        $draft = $this->createArticle('draft-search', Article::STATUS_DRAFT, 'Gate search article draft');
        $search = app(SearchService::class);

        $results = $search->searchWithin('article', 'Gate search', [], ['per_page' => 20]);

        $this->assertSame([$published->id], $results->getCollection()->pluck('id')->all());
        $this->assertNotContains($draft->id, $results->getCollection()->pluck('id')->all());
        $this->assertSame(1, $results->total());
        $this->assertSame(1, $search->countWithin('article', 'Gate search'));
    }

    public function test_core_002_topic_items_and_counts_exclude_draft_articles(): void
    {
        $topic = Topic::create(['slug' => 'visibility-topic', 'icon' => 'bookmark']);
        $published = $this->createArticle('published-topic', Article::STATUS_PUBLISHED, 'Published topic article');
        $draft = $this->createArticle('draft-topic', Article::STATUS_DRAFT, 'Draft topic article');

        foreach ([$published, $draft] as $sortOrder => $article) {
            TopicItem::create([
                'topic_id' => $topic->id,
                'itemable_type' => TopicItemType::ARTICLE->value,
                'itemable_id' => $article->id,
                'sort_order' => $sortOrder,
            ]);
        }

        $repository = app(TopicRepositoryInterface::class);
        $items = $repository->itemsForTopic($topic->id, TopicItemType::ARTICLE->value, 'ar');

        $this->assertSame([$published->id], $items->pluck('itemable_id')->all());
        $this->assertNotContains($draft->id, $items->pluck('itemable_id')->all());
        $this->assertSame(1, $repository->itemCounts($topic->id, 'ar')[TopicItemType::ARTICLE->value]);
        $this->assertSame(1, (int) $repository->allOrderedWithCounts('ar')->first()->articles_count);
    }

    public function test_med_002_public_episode_api_excludes_unpublished_video_urls(): void
    {
        $program = MediaProgramFactory::new()->create([
            'slug' => 'visibility-program',
            'is_published' => true,
        ]);
        $published = $this->createEpisode($program, 1, true, 'https://video.example/published');
        $draft = $this->createEpisode($program, 2, false, 'https://video.example/editorial-draft');

        $response = $this->getJson("/api/v1/media/programs/{$program->slug}/episodes");

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonFragment(['id' => $published->id, 'video_url' => $published->video_url])
            ->assertJsonMissing(['id' => $draft->id])
            ->assertJsonMissing(['video_url' => $draft->video_url]);
    }

    private function createArticle(string $slug, string $status, string $title): Article
    {
        $article = Article::factory()->create(['slug' => $slug, 'status' => $status]);
        ArticleLocale::create([
            'article_id' => $article->id,
            'locale' => 'ar',
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $title,
            'is_ready' => true,
        ]);

        return $article;
    }

    private function createEpisode(
        MediaProgram $program,
        int $episodeNumber,
        bool $published,
        string $videoUrl,
    ): MediaEpisode {
        $episode = MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => $episodeNumber,
            'slug' => "visibility-episode-{$episodeNumber}",
            'video_url' => $videoUrl,
            'duration_seconds' => 1200,
            'views_count' => 0,
            'is_featured' => false,
            'is_published' => $published,
        ]);
        MediaEpisodeTranslation::create([
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => "حلقة {$episodeNumber}",
            'is_ready' => true,
        ]);

        return $episode;
    }

    public function test_ssg_002_public_sahaba_lists_and_search_exclude_unpublished_people(): void
    {
        // A companion is now a `muslims` row carrying the `sahabi` role, and
        // publication is a plain boolean rather than a per-locale ready flag.
        $published = $this->createCompanion('published-companion', true);
        $draft = $this->createCompanion('draft-companion', false);

        $repository = app(MuslimRepositoryInterface::class);

        $listed = $repository->paginate(new MuslimListFilters(roleSlug: 'sahabi'))
            ->getCollection()->pluck('id')->all();

        $this->assertContains($published->id, $listed);
        $this->assertNotContains($draft->id, $listed);

        // ...but the admin picker still offers the unpublished person, because
        // an unpublished person is a valid ancestor.
        $picked = $repository->pickerSearch('companion')->pluck('id')->all();
        $this->assertContains($draft->id, $picked);
    }

    private function createCompanion(string $slug, bool $published): Muslim
    {
        $role = IslamicRole::query()->firstOrCreate(
            ['slug' => 'sahabi'],
            ['name_ar' => 'صحابي', 'name_en' => 'Companion'],
        );

        $muslim = Muslim::create([
            'slug' => $slug,
            'name_ar' => 'صحابي',
            'name_en' => 'Companion '.$slug,
            'is_published' => $published,
        ]);
        $muslim->roles()->attach($role->id, ['is_featured' => false, 'sort_order' => 0]);

        return $muslim;
    }
}
