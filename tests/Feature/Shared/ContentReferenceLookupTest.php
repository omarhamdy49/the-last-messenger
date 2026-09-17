<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\People\Models\Muslim;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Contracts\ContentReference;
use App\Domain\Shared\Enums\LinkedContentType;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Providers\Composition\ContentReferenceLookupService;
use Database\Factories\MediaProgramFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentReferenceLookupTest extends TestCase
{
    use RefreshDatabase;

    private ContentReferenceLookupService $lookup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lookup = app(ContentReferenceLookupService::class);
    }

    /** @return array<string, list<int>> three ids per type, all publicly visible */
    private function seedAll(): array
    {
        $surah = Surah::create([
            'revelation_place' => 'makkah', 'revelation_order' => 1, 'bismillah_pre' => true,
            'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatihah', 'transliteration' => 'Al-Fatihah', 'verses_count' => 7,
        ]);
        $program = MediaProgramFactory::new()->create(['is_published' => true]);
        $collection = HadithCollection::factory()->create();

        $ids = [];
        foreach ([1, 2, 3] as $n) {
            $verse = Verse::create([
                'surah_id' => $surah->id, 'verse_number' => $n, 'verse_key' => "{$surah->id}:{$n}",
                'juz_number' => 1, 'hizb_number' => 1, 'rub_el_hizb_number' => 1, 'ruku_number' => 1,
                'manzil_number' => 1, 'page_number' => 1,
            ]);
            DB::table('quran_verse_translations')->insert(['verse_id' => $verse->id, 'lang' => 'ar', 'text' => "آية {$n}"]);
            $ids['verse'][] = $verse->id;

            $ids['hadith'][] = Hadith::factory()->create(['collection_id' => $collection->id])->id;
            $ids['muslim'][] = Muslim::factory()->create()->id;
            $ids['article'][] = $this->article("مقال {$n}", Article::STATUS_PUBLISHED)->id;
            $ids['episode'][] = $this->episode($program->id, $n, true)->id;
            $ids['tag'][] = $this->labelled(Tag::query()->create(['slug' => "tag-{$n}"]), 'tag_translations', 'tag_id', "وسم {$n}")->id;
            $ids['category'][] = $this->labelled(
                Category::query()->create(['slug' => "cat-{$n}", 'type' => 'blog', 'status' => 'active']),
                'category_translations', 'category_id', "تصنيف {$n}",
            )->id;
        }

        return $ids;
    }

    private function article(string $title, string $status, bool $ready = true): Article
    {
        $article = Article::factory()->create(['status' => $status]);
        ArticleLocale::create([
            'article_id' => $article->id, 'locale' => 'ar', 'title' => $title,
            'slug' => 'slug-'.$article->id, 'excerpt' => $title, 'is_ready' => $ready,
        ]);

        return $article;
    }

    private function episode(int $programId, int $n, bool $published): MediaEpisode
    {
        $episode = MediaEpisode::create([
            'program_id' => $programId, 'season_number' => 1, 'episode_number' => $n, 'slug' => "ep-{$programId}-{$n}",
            'video_url' => 'https://www.youtube.com/embed/abcdefghij'.$n, 'is_published' => $published,
        ]);
        DB::table('media_episode_translations')->insert([
            'media_episode_id' => $episode->id, 'locale' => 'ar', 'title' => "حلقة {$n}", 'is_ready' => true,
        ]);

        return $episode;
    }

    private function labelled(Tag|Category $model, string $table, string $fk, string $name): Tag|Category
    {
        DB::table($table)->insert([$fk => $model->id, 'locale' => 'ar', 'name' => $name, 'is_ready' => true]);

        return $model;
    }

    /** @param list<ContentReference> $refs */
    private function ids(array $refs): array
    {
        return array_map(fn (ContentReference $r): int => $r->id, $refs);
    }

    public function test_every_type_resolves_in_input_order_and_omits_unknown_ids(): void
    {
        $ids = $this->seedAll();

        $request = array_map(fn (array $list): array => [$list[2], 999999, $list[0]], $ids);
        $result = $this->lookup->resolve($request + ['bogus' => [1]], 'ar', true);

        $this->assertSame(LinkedContentType::values(), array_keys($result));

        foreach ($ids as $type => $list) {
            $this->assertSame([$list[2], $list[0]], $this->ids($result[$type]), $type);
            foreach ($result[$type] as $ref) {
                $this->assertNotSame('', $ref->title, $type);
            }
        }

        $verse = $result['verse'][0];
        $this->assertSame('آية 3', $verse->title);
        $this->assertStringEndsWith('/quran/surah/'.Verse::find($ids['verse'][2])->surah_id.'#v3', $verse->url);

        $hadith = Hadith::with('collection')->find($ids['hadith'][2]);
        $this->assertSame($hadith->text_ar, $result['hadith'][0]->title);
        $this->assertSame(route('public.hadith.show', $hadith->collection->slug.':'.$hadith->reference_key), $result['hadith'][0]->url);

        $muslim = Muslim::find($ids['muslim'][2]);
        $this->assertSame($muslim->name_ar, $result['muslim'][0]->title);
        $this->assertSame(route('public.sahaba.show', $muslim->slug), $result['muslim'][0]->url);

        $this->assertSame('مقال 3', $result['article'][0]->title);
        $this->assertSame(route('public.articles.show', 'slug-'.$ids['article'][2]), $result['article'][0]->url);

        $episode = MediaEpisode::with('program')->find($ids['episode'][2]);
        $this->assertSame('حلقة 3', $result['episode'][0]->title);
        $this->assertSame(route('public.media.episode', ['slug' => $episode->program->slug, 'episode' => 3]), $result['episode'][0]->url);
        $this->assertStringStartsWith('https://i.ytimg.com/', (string) $result['episode'][0]->thumbUrl);

        $this->assertSame('وسم 3', $result['tag'][0]->title);
        $this->assertNull($result['tag'][0]->url);
        $this->assertSame('تصنيف 3', $result['category'][0]->title);
    }

    public function test_public_only_hides_non_visible_content_but_admin_sees_it(): void
    {
        $draft = $this->article('مسودة', Article::STATUS_DRAFT);
        $notReady = $this->article('غير جاهز', Article::STATUS_PUBLISHED, ready: false);
        $episode = $this->episode(MediaProgramFactory::new()->create()->id, 1, false);
        $hiddenCollection = HadithCollection::factory()->create(['is_published' => false]);
        $hadith = Hadith::factory()->create(['collection_id' => $hiddenCollection->id]);
        $person = Muslim::factory()->create(['is_published' => false]);

        $request = [
            'article' => [$draft->id, $notReady->id],
            'episode' => [$episode->id],
            'hadith' => [$hadith->id],
            'muslim' => [$person->id],
        ];

        $public = $this->lookup->resolve($request, 'ar', true);
        foreach (array_keys($request) as $type) {
            $this->assertSame([], $public[$type], $type);
        }

        $admin = $this->lookup->resolve($request, 'ar');
        $this->assertSame([$draft->id, $notReady->id], $this->ids($admin['article']));
        $this->assertSame([$episode->id], $this->ids($admin['episode']));
        $this->assertSame([$hadith->id], $this->ids($admin['hadith']));
        $this->assertSame([$person->id], $this->ids($admin['muslim']));

        // Visible to the admin, but not a public page.
        $this->assertNull($admin['article'][0]->url);
        $this->assertNull($admin['episode'][0]->url);
        $this->assertNull($admin['hadith'][0]->url);
        $this->assertNull($admin['muslim'][0]->url);
    }

    public function test_query_count_does_not_grow_with_ids(): void
    {
        $ids = $this->seedAll();

        $count = function (array $request): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->lookup->resolve($request, 'ar', true);
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $one = $count(array_map(fn (array $list): array => [$list[0]], $ids));
        $three = $count($ids);

        $this->assertSame($one, $three);
    }

    public function test_options_list_tags_and_categories_only(): void
    {
        $ids = $this->seedAll();

        $this->assertSame(
            array_combine($ids['tag'], ['وسم 1', 'وسم 2', 'وسم 3']),
            $this->lookup->options(LinkedContentType::TAG, 'en'),
        );
        $this->assertSame(
            array_combine($ids['category'], ['تصنيف 1', 'تصنيف 2', 'تصنيف 3']),
            $this->lookup->options(LinkedContentType::CATEGORY, 'ar'),
        );
        $this->assertSame([], $this->lookup->options(LinkedContentType::VERSE, 'ar'));
    }
}
