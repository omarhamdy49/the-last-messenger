<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Shared\Models\SearchHistory;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ApiSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_ROUTE_NAMES = [
        'v1.auth.login',
        'v1.auth.register',
        'v1.health',
    ];

    public function test_only_the_explicit_bootstrap_allowlist_is_public(): void
    {
        $routes = $this->apiRoutes();

        $publicNames = $routes
            ->reject(fn (LaravelRoute $route): bool => in_array('auth:sanctum', $route->gatherMiddleware(), true))
            ->map(fn (LaravelRoute $route): ?string => $route->getName())
            ->sort()
            ->values()
            ->all();

        $expected = self::PUBLIC_ROUTE_NAMES;
        sort($expected);

        $this->assertSame($expected, $publicNames);
    }

    public function test_every_api_route_is_rate_limited(): void
    {
        $unthrottled = $this->apiRoutes()
            ->reject(fn (LaravelRoute $route): bool => collect($route->gatherMiddleware())
                ->contains(fn (string $middleware): bool => str_starts_with($middleware, 'throttle:')))
            ->map(fn (LaravelRoute $route): string => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unthrottled, 'Unthrottled API routes: '.implode(', ', $unthrottled));
    }

    public function test_every_protected_route_requires_the_api_access_ability(): void
    {
        $missingAbility = $this->apiRoutes()
            ->reject(fn (LaravelRoute $route): bool => in_array($route->getName(), self::PUBLIC_ROUTE_NAMES, true))
            ->reject(fn (LaravelRoute $route): bool => in_array('abilities:api:access', $route->gatherMiddleware(), true))
            ->map(fn (LaravelRoute $route): string => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        $this->assertSame([], $missingAbility, 'Routes missing api:access ability: '.implode(', ', $missingAbility));
    }

    public function test_authenticated_api_responses_have_defensive_headers(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);

        $this->getJson('/api/v1/languages/current')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_token_without_api_access_ability_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['profile:read']);

        $this->getJson('/api/v1/languages/current')->assertForbidden();
    }

    public function test_user_cannot_read_another_users_engagement(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($other, ['api:access']);

        $this->getJson("/api/v1/engagement/users/{$owner->id}")
            ->assertForbidden();
    }

    public function test_interaction_type_cannot_be_a_php_class_name(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);

        $this->postJson('/api/v1/interactions', [
            'interactable_type' => Article::class,
            'interactable_id' => 1,
            'interaction_type' => 'view',
        ])->assertUnprocessable();
    }

    public function test_interactions_do_not_reveal_draft_content(): void
    {
        $user = User::factory()->create();
        $draft = Article::factory()->create([
            'writer_id' => $user->id,
            'status' => 'draft',
        ]);
        Sanctum::actingAs($user, ['api:access']);

        $this->postJson('/api/v1/interactions', [
            'interactable_type' => 'Article',
            'interactable_id' => $draft->id,
            'interaction_type' => 'view',
        ])->assertNotFound();
    }

    public function test_search_suggestions_require_three_distinct_users(): void
    {
        $users = User::factory()->count(4)->create();
        foreach ($users->take(2) as $user) {
            SearchHistory::query()->create([
                'user_id' => $user->id,
                'query' => 'private-term',
                'results_count' => 1,
                'searched_at' => now(),
            ]);
        }

        Sanctum::actingAs($users[3], ['api:access']);
        $this->getJson('/api/v1/search/suggestions?q=private')
            ->assertOk()
            ->assertJsonCount(0, 'data.suggestions');

        SearchHistory::query()->create([
            'user_id' => $users[2]->id,
            'query' => 'private-term',
            'results_count' => 1,
            'searched_at' => now(),
        ]);
        Cache::flush();

        $this->getJson('/api/v1/search/suggestions?q=private')
            ->assertOk()
            ->assertJsonPath('data.suggestions.0', 'private-term');
    }

    /** @return Collection<int, LaravelRoute> */
    private function apiRoutes(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route): bool => str_starts_with($route->uri(), 'api/v1/'))
            ->values();
    }
}
