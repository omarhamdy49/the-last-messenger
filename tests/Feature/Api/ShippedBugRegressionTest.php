<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Models\SavedFilter;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regressions for four defects that were live in production.
 *
 * Each existed because the surrounding tests asserted structure
 * (assertJsonStructure / assertJsonPath('success', true)) and never a field's value, so
 * a wrong value or an extra key sailed through.
 */
class ShippedBugRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SurahResource emitted `revelation_order` as `number`, so Al-Fatihah (id 1,
     * revelation_order 5) reported number 5 to every consumer. The chapter number is
     * the id, and since 006 it is the primary key, so the two can no longer diverge.
     */
    public function test_chapter_payload_reports_the_id_as_the_number(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
        Chapter::factory()->number(1)->create(['revelation_order' => 5]);

        $this->getJson('/api/v1/quran/chapters/1')
            ->assertOk()
            ->assertJsonPath('data.chapter.id', 1)
            ->assertJsonPath('data.chapter.revelation_order', 5);
    }

    /**
     * TranslationResource read `$this->resource->getName()`. Inside a JsonResource,
     * `$this->resource` is Laravel's reserved property holding the wrapped model — the
     * translation row — not the `resource()` belongsTo, so four Quran endpoints 500'd.
     * The QF-shaped payload names its resource inline; assert the values, not the shape.
     */
    public function test_verse_translation_names_its_resource(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
        Chapter::factory()->number(1)->withVerses(1)->create();
        $resource = QuranResource::factory()->translation('en')->asDefault()->create(['name' => 'Sahih International', 'slug' => 'sahih-international']);
        Translation::factory()->create(['resource_id' => $resource->id, 'verse_id' => Verse::query()->value('id'), 'text' => 'In the name of Allah']);

        $this->getJson("/api/v1/quran/verses/by_key/1:1?translations={$resource->id}")
            ->assertOk()
            ->assertJsonPath('data.verse.translations.0.resource_id', $resource->id)
            ->assertJsonPath('data.verse.translations.0.resource_name', 'Sahih International')
            ->assertJsonPath('data.verse.translations.0.text', 'In the name of Allah');
    }

    /**
     * `roles` resource only(['index','show']) registered GET roles/{role} before
     * roles/create, so the static segment matched {role} and dispatched to
     * RoleController::show(int $id) with 'create' — a TypeError 500. The page was dead.
     */
    public function test_role_create_route_resolves_to_the_create_action(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/su/portal/v1/admin/roles/create', 'GET')
        );

        $this->assertSame('admin.roles.create', $route->getName());
        $this->assertStringEndsWith('RoleController@create', $route->getActionName());
    }

    /**
     * POST /api/v1/filters/{id}/apply returned raw Eloquent models through
     * ApiResponse::paginatedResponse, and getDefaultRelations(ARTICLE) eager-loads
     * `writer`. users.email is not in User::$hidden, so writer email addresses leaked
     * alongside full article bodies.
     */
    public function test_applying_a_filter_does_not_leak_writer_emails(): void
    {
        $writer = User::factory()->create(['email' => 'writer-pii@example.test']);
        $owner = User::factory()->create();

        app()->setLocale('ar');

        // The FK on `articles` is writer_id — this is the relation
        // getDefaultRelations(ARTICLE) eager-loads as `writer`.
        $article = Article::factory()->create([
            'slug' => 'pii-probe',
            'status' => 'published',
            'writer_id' => $writer->id,
        ]);

        // ArticlePublicVisibility gates on a locale row with is_ready = true for the
        // current locale; without it the search returns nothing and this test would
        // pass vacuously. Same fixture shape as tests/Feature/PublicVisibilityGatingTest.
        // Both locales: the SetLocale middleware resolves the request locale itself, so
        // seeding only one risks the gate missing and the test passing vacuously.
        foreach (['ar', 'en'] as $locale) {
            ArticleLocale::create([
                'article_id' => $article->id,
                'locale' => $locale,
                'title' => 'مقال للاختبار',
                'slug' => 'pii-probe-'.$locale,
                'excerpt' => 'مقال للاختبار',
                'is_ready' => true,
            ]);
        }

        $filter = SavedFilter::create([
            'user_id' => $owner->id,
            'name' => 'Published articles',
            'module' => 'article',
            'filters' => ['status' => 'published'],
            'is_public' => false,
            'is_default' => false,
            'usage_count' => 0,
        ]);

        // The API is private by default: bearer token + the api:access ability.
        Sanctum::actingAs($owner, ['api:access']);

        $response = $this->postJson("/api/v1/filters/{$filter->id}/apply", ['per_page' => 5]);

        $response->assertOk();

        // Guard against a vacuous pass: if the filter matches nothing there is no
        // payload to leak, and the assertions below would succeed on an empty array.
        $this->assertGreaterThan(
            0,
            $response->json('meta.total'),
            'fixture matched no articles, so the leak assertions below prove nothing'
        );

        $body = $response->getContent();
        $this->assertStringNotContainsString('writer-pii@example.test', $body);
        $this->assertStringNotContainsString('"email"', $body);
        $this->assertStringNotContainsString('remember_token', $body);
    }
}
