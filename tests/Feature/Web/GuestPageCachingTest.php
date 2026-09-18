<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Http\Middleware\CacheGuestPages;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * CacheGuestPages contract.
 *
 * Expanding CACHEABLE_ROUTES is the single highest-leverage capacity change available
 * (Cloudflare will not cache HTML without an explicit directive, so ~95% of public
 * traffic was reaching PHP), but it is also the change most able to leak one visitor's
 * page to another. These tests pin the guarantees rather than the route list.
 */
class GuestPageCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_listing_pages_are_edge_cacheable_and_drop_cookies(): void
    {
        $response = $this->get(route('public.articles'));

        $response->assertOk();
        $this->assertStringContainsString('s-maxage=1800', (string) $response->headers->get('Cache-Control'));
        // The visitor's own browser must revalidate: without max-age it caches
        // heuristically, and a reader kept seeing the page from before an editor
        // enabled new translations or tafsirs.
        $this->assertStringContainsString('max-age=0', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('stale-while-revalidate', (string) $response->headers->get('Cache-Control'));
        $this->assertNull(
            $response->headers->get('Set-Cookie'),
            'a shared response must not carry a Set-Cookie, or the edge stores one visitor session'
        );
    }

    public function test_authenticated_responses_are_never_shared(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('public.articles'));

        $response->assertOk();

        // Assert the directives, not their order — Symfony normalises Cache-Control
        // (it emits "no-store, private").
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringNotContainsString('s-maxage', $cacheControl);
    }

    /**
     * The locale a guest sees comes from ?lang= or the session, neither of which is in
     * the edge cache key — and the cacheable branch strips the very cookie that carries
     * it. Sharing a non-default render under the plain URL would pin that language for
     * every later visitor.
     */
    public function test_non_default_locale_renders_are_not_shared(): void
    {
        $viaQuery = $this->get(route('public.articles', ['lang' => 'en']));
        $viaQuery->assertOk();
        $this->assertStringNotContainsString('s-maxage', (string) $viaQuery->headers->get('Cache-Control'));

        $viaSession = $this->withSession(['locale' => 'en'])->get(route('public.articles'));
        $viaSession->assertOk();
        $this->assertStringNotContainsString('s-maxage', (string) $viaSession->headers->get('Cache-Control'));
    }

    /**
     * Every route on the allow-list must actually exist. A rename would otherwise
     * silently drop it from caching with no failure anywhere.
     */
    public function test_every_cacheable_route_name_is_registered(): void
    {
        $reflection = new \ReflectionClass(CacheGuestPages::class);
        /** @var array<int, string> $names */
        $names = $reflection->getConstant('CACHEABLE_ROUTES');

        $missing = array_values(array_filter(
            $names,
            static fn (string $name): bool => ! Route::has($name)
        ));

        $this->assertSame([], $missing, 'unregistered route names in CACHEABLE_ROUTES: '.implode(', ', $missing));
        $this->assertGreaterThan(30, count($names));
    }

    /**
     * Guards the exclusions that carry a reason. Each of these would lose behaviour if
     * cached: a GET-path view counter, or a CSRF form whose token would go stale.
     */
    public function test_routes_with_get_path_side_effects_or_forms_are_not_cacheable(): void
    {
        $reflection = new \ReflectionClass(CacheGuestPages::class);
        /** @var array<int, string> $names */
        $names = $reflection->getConstant('CACHEABLE_ROUTES');

        foreach ([
            'public.seerah.event',   // increments views inline on the GET
            'public.articles.show',  // async view POST reads a CSRF token from the HTML
            'public.media.program',  // per-request view recording
            'public.media.episode',  // view recording + watch progress
            'public.contact',        // @csrf form
            'public.login',          // @csrf form
            'public.register',       // @csrf form
            'public.profile',        // per-user
            'public.favorites',      // per-user
            'public.quran.ask',      // @csrf form + per-user quota
        ] as $name) {
            $this->assertNotContains(
                $name,
                $names,
                "[{$name}] must not be edge-cacheable — see the reasoning in CacheGuestPages."
            );
        }
    }
}
