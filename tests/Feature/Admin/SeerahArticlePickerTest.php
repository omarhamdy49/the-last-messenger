<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeerahArticlePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_picker_paginates_articles_and_only_returns_choice_fields(): void
    {
        $admin = $this->createAdminUser();
        $ids = [];
        for ($i = 0; $i < 17; $i++) {
            $ids[] = $this->article($admin, 'Article '.$i)->id;
        }
        $deleted = $this->article($admin, 'Deleted article');
        $deleted->delete();
        $this->actingAs($admin);

        $first = $this->getJson(route('admin.seerah-events.articles', ['per_page' => 10000]))
            ->assertOk()->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 17)->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 15);
        $second = $this->getJson(route('admin.seerah-events.articles', ['page' => 2]))
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.current_page', 2);

        $this->assertSame(array_reverse($ids), array_merge(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')));
        $this->assertSame(['id', 'title'], array_keys($first->json('data.0')));
    }

    public function test_picker_searches_translated_titles_and_article_ids(): void
    {
        $admin = $this->createAdminUser();
        $article = $this->article($admin, 'الهجرة إلى المدينة');
        $article->locales()->create(['locale' => 'en', 'title' => 'Migration to Madinah', 'slug' => 'migration']);
        $this->article($admin, 'Unrelated article');
        $this->actingAs($admin);

        foreach (['الهجرة', ' Migration ', (string) $article->id] as $query) {
            $this->getJson(route('admin.seerah-events.articles', ['q' => $query]))
                ->assertOk()->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $article->id)
                ->assertJsonPath('data.0.title', 'الهجرة إلى المدينة');
        }
        $this->getJson(route('admin.seerah-events.articles', ['q' => 'No matching title']))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_create_and_edit_only_load_the_selected_article(): void
    {
        $admin = $this->createAdminUser();
        $selected = $this->article($admin, 'Selected article');
        $this->article($admin, 'Unrelated catalog article');
        $event = SeerahEvent::factory()->create(['related_article_id' => $selected->id]);
        $this->actingAs($admin);

        DB::enableQueryLog();
        $this->get(route('admin.seerah-events.create'))->assertOk()
            ->assertViewHas('selectedArticle', null)
            ->assertDontSee('Unrelated catalog article')->assertDontSee('<select name="related_article_id"', false);
        $this->get(route('admin.seerah-events.edit', $event))->assertOk()
            ->assertViewHas('selectedArticle', fn ($article) => $article->id === $selected->id)
            ->assertDontSee('Unrelated catalog article')->assertDontSee('<select name="related_article_id"', false);

        $queries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($query) => preg_match('/from [`"]articles[`"]/', $query));
        DB::disableQueryLog();
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertMatchesRegularExpression('/(?:[`"]id[`"] = \\?|[`"]id[`"] in \\()/i', $query);
        }
    }

    public function test_validation_redirect_keeps_changed_or_cleared_selection(): void
    {
        $admin = $this->createAdminUser();
        $original = $this->article($admin, 'Original');
        $replacement = $this->article($admin, 'Replacement');
        $event = SeerahEvent::factory()->create(['related_article_id' => $original->id]);
        $this->actingAs($admin);

        foreach (['admin.seerah-events.create', 'admin.seerah-events.edit'] as $route) {
            $url = route($route, $route === 'admin.seerah-events.edit' ? [$event] : []);
            $this->withSession(['_old_input' => ['related_article_id' => $replacement->id]])
                ->get($url)->assertOk()
                ->assertViewHas('selectedArticle', fn ($article) => $article->id === $replacement->id);
            $this->withSession(['_old_input' => ['related_article_id' => '']])
                ->get($url)->assertOk()->assertViewHas('selectedArticle', null);
        }
    }

    public function test_picker_validates_search_and_page_inputs(): void
    {
        $this->actingAs($this->createAdminUser());
        foreach ([['q' => ['bad']], ['q' => str_repeat('a', 201)], ['page' => 0], ['page' => 'bad']] as $input) {
            $this->getJson(route('admin.seerah-events.articles', $input))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($input));
        }
    }

    public function test_picker_requires_admin_access(): void
    {
        $this->getJson(route('admin.seerah-events.articles'))->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.seerah-events.articles'))->assertForbidden();
    }

    private function article(User $writer, string $title): Article
    {
        $article = Article::query()->create(['writer_id' => $writer->id]);
        $article->locales()->create(['locale' => 'ar', 'title' => $title, 'slug' => 'article-'.$article->id]);

        return $article;
    }
}
