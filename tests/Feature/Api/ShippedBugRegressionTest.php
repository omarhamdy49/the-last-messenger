<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\TranslationResource as TranslationEdition;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Shared\Models\SavedFilter;
use App\Http\Resources\SurahResource;
use App\Http\Resources\TranslationResource;
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

    private function makeSurah(int $revelationOrder = 5): Surah
    {
        return Surah::create([
            'revelation_place' => 'makkah',
            'revelation_order' => $revelationOrder,
            'bismillah_pre' => true,
            'name_ar' => 'الفاتحة',
            'name_en' => 'Al-Fatihah',
            'transliteration' => 'Al-Fatihah',
            'verses_count' => 7,
        ]);
    }

    /**
     * SurahResource emitted `revelation_order` as `number`, so Al-Fatihah (id 1,
     * revelation_order 5) reported number 5 to every consumer. The surah number is the id.
     */
    public function test_surah_resource_reports_the_id_as_the_number(): void
    {
        $surah = $this->makeSurah(revelationOrder: 5);

        $payload = (new SurahResource($surah))->resolve(Request::create('/'));

        $this->assertSame($surah->id, $payload['number']);
        $this->assertSame(5, $payload['revelation_order']);
        $this->assertNotSame(
            $payload['revelation_order'],
            $payload['number'],
            'number must be the surah id, not its revelation order'
        );
    }

    /**
     * TranslationResource read `$this->resource->getName()`. Inside a JsonResource,
     * `$this->resource` is Laravel's reserved property holding the wrapped model — the
     * VerseTranslation — not the `resource()` belongsTo. VerseTranslation has no
     * getName(), so this raised BadMethodCallException and four Quran endpoints 500'd.
     */
    public function test_translation_resource_serialises_its_edition(): void
    {
        $surah = $this->makeSurah();

        $verse = Verse::create([
            'surah_id' => $surah->id,
            'verse_number' => 1,
            'verse_key' => "{$surah->id}:1",
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'manzil_number' => 1,
            'page_number' => 1,
        ]);

        $edition = TranslationEdition::create([
            'slug' => 'sahih-international',
            'name' => ['en' => 'Sahih International', 'ar' => 'صحيح انترناشونال'],
            'language' => 'en',
            'is_default' => true,
            'is_active' => true,
        ]);

        $translation = VerseTranslation::create([
            'verse_id' => $verse->id,
            'resource_id' => $edition->id,
            'lang' => 'en',
            'text' => 'In the name of Allah',
        ]);

        $payload = (new TranslationResource($translation->load('resource')))
            ->resolve(Request::create('/'));

        $this->assertIsArray($payload['edition']);
        $this->assertSame($edition->id, $payload['edition']['id']);
        $this->assertSame('sahih-international', $payload['edition']['slug']);
        // getName() reads name[locale] with slug as last resort — a null here would mean
        // the eager load dropped `name`/`slug` again.
        $this->assertSame('صحيح انترناشونال', $payload['edition']['name']);
        $this->assertSame('en', $payload['edition']['language']);
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
