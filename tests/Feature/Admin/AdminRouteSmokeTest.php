<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Throwable;

/**
 * Every static (non-parameterised) admin GET route must render for a super admin.
 *
 * `tests/Feature/Api/ApiRouteSmokeTest.php` has done this for /api/v1 for a while; the
 * admin panel had no equivalent, which is why `GET admin/roles/create` sat dead in
 * production. The `roles` resource registered `roles/{role}` before `roles/create`, so the
 * static segment matched the parameter and dispatched to `RoleController::show(int $id)`
 * with the string 'create' — a TypeError 500. One test covers that whole class of bug.
 *
 * Scope is deliberate:
 *   - GET only. POST/PUT/DELETE need per-route payloads and would mutate state; the aim
 *     here is catching route shadowing and controller/view boot failures.
 *   - Static URIs only. 82 of the 212 admin GET routes take parameters spanning 42
 *     distinct binding types; seeding all of them is its own fixture project. Those are
 *     reported as skipped so the coverage gap stays visible rather than implied.
 */
class AdminRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_static_admin_get_route_renders_for_a_super_admin(): void
    {
        Cache::flush();

        $admin = $this->createAdminUser(role: 'super_admin');
        $this->actingAs($admin, 'admin');

        $all = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $r): bool => str_starts_with($r->uri(), 'su/portal/v1/admin'))
            ->filter(fn (LaravelRoute $r): bool => in_array('GET', $r->methods(), true));

        [$parameterised, $static] = $all->partition(
            fn (LaravelRoute $r): bool => str_contains($r->uri(), '{')
        );

        $failures = [];
        $checked = 0;

        foreach ($static as $route) {
            try {
                $response = $this->get('/'.$route->uri(), ['Accept-Language' => 'ar']);
                $checked++;

                if ($response->getStatusCode() >= 500) {
                    $failures[] = $this->describe($route, $response);
                }
            } catch (Throwable $e) {
                $checked++;
                $failures[] = sprintf(
                    'GET %s threw %s: %s',
                    $route->uri(),
                    $e::class,
                    $e->getMessage()
                );
            }
        }

        fwrite(STDOUT, sprintf(
            '%sAdmin route smoke: %d GET routes, %d static exercised, %d parameterised skipped%s',
            PHP_EOL,
            $all->count(),
            $checked,
            $parameterised->count(),
            PHP_EOL
        ));

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));

        // Guard the guard: if a refactor collapses the route table this test would
        // silently pass having checked nothing.
        $this->assertGreaterThan(
            100,
            $checked,
            'expected >100 static admin GET routes; the route table or this filter changed'
        );
    }

    /**
     * The role-creation page specifically — it was dead in production, and a
     * regression here is a one-line route-ordering mistake away.
     */
    public function test_role_create_page_renders(): void
    {
        $this->actingAs($this->createAdminUser(role: 'super_admin'), 'admin');

        $this->get(route('admin.roles.create'))->assertOk();
    }

    private function describe(LaravelRoute $route, TestResponse $response): string
    {
        $exception = $response->exceptions->last();

        $cause = $exception !== null
            ? $exception::class.': '.$exception->getMessage()
            : trim(strip_tags((string) $response->getContent()));

        return sprintf(
            'GET %s returned %d: %s',
            $route->uri(),
            $response->getStatusCode(),
            mb_substr($cause, 0, 300) ?: 'no message exposed'
        );
    }
}
