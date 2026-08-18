<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Privilege boundaries inside the admin portal.
 *
 * The whole portal used to sit behind one gate — `role:super_admin|admin|editor,admin` —
 * and `permission:` middleware appeared ZERO times in every route file. Only three
 * sub-groups narrowed it, so an `editor` (who the seeder grants nothing but content
 * permissions) could read all platform analytics, read every admin's actions and IP
 * addresses, revert any content, add or remove site languages, bulk export or import any
 * module, and — worst — call POST /audit/prune to destroy the audit trail.
 *
 * These tests assert the boundary behaviourally. Inspecting the middleware list cannot do
 * it: the outer group's `...|editor` string is present on every route regardless of what
 * inner gate also applies.
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every surface an editor must NOT reach. Checked in one test rather than via a data
     * provider so a regression reports the whole list at once instead of the first failure.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const FORBIDDEN_FOR_EDITORS = [
        'destroy the audit trail' => ['post', 'su/portal/v1/admin/audit/prune'],
        'read the audit log' => ['get', 'su/portal/v1/admin/audit'],
        'read platform analytics' => ['get', 'su/portal/v1/admin/analytics'],
        'read analytics stats' => ['get', 'su/portal/v1/admin/analytics/stats'],
        'list content versions' => ['get', 'su/portal/v1/admin/versions/stats'],
        'list schedules' => ['get', 'su/portal/v1/admin/scheduler'],
        'read activity logs' => ['get', 'su/portal/v1/admin/activity-logs'],
        'list site languages' => ['get', 'su/portal/v1/admin/settings/languages'],
        'export any module' => ['post', 'su/portal/v1/admin/export'],
        'flush dashboard caches' => ['post', 'su/portal/v1/admin/dashboard/cache/clear'],
    ];

    public function test_an_editor_is_refused_every_privileged_surface(): void
    {
        $editor = $this->createAdminUser(role: 'editor');
        $leaks = [];

        foreach (self::FORBIDDEN_FOR_EDITORS as $what => [$method, $uri]) {
            $status = $this->actingAs($editor, 'admin')
                ->{$method.'Json'}('/'.$uri)
                ->getStatusCode();

            // 403 is the intent. 404 is accepted only because a few of these bind a model
            // and Laravel 404s before authorising — still not reachable by an editor.
            if (! in_array($status, [403, 404], true)) {
                $leaks[] = sprintf('%s (%s %s) -> %d', $what, strtoupper($method), $uri, $status);
            }
        }

        $this->assertSame([], $leaks, "an editor reached privileged surfaces:\n  ".implode("\n  ", $leaks));
    }

    /**
     * The mirror image: gating must not lock out the roles that legitimately need these.
     */
    public function test_an_admin_still_reaches_the_gated_surfaces(): void
    {
        $admin = $this->createAdminUser(role: 'admin');

        foreach ([
            'su/portal/v1/admin/audit',
            'su/portal/v1/admin/analytics',
            'su/portal/v1/admin/versions/stats',
            'su/portal/v1/admin/scheduler',
            'su/portal/v1/admin/activity-logs',
            'su/portal/v1/admin/settings/languages',
        ] as $uri) {
            $status = $this->actingAs($admin, 'admin')->get('/'.$uri)->getStatusCode();

            $this->assertLessThan(
                400,
                $status,
                "[{$uri}] returned {$status} for an admin — the gate is too tight."
            );
        }
    }

    /**
     * Editors must keep the content surfaces the seeder actually grants them.
     */
    public function test_an_editor_keeps_its_content_permissions(): void
    {
        $editor = $this->createAdminUser(role: 'editor');

        foreach ([
            'su/portal/v1/admin/articles',
            'su/portal/v1/admin/dashboard',
        ] as $uri) {
            $status = $this->actingAs($editor, 'admin')->get('/'.$uri)->getStatusCode();

            $this->assertLessThan(
                400,
                $status,
                "[{$uri}] returned {$status} — editors must keep their content access."
            );
        }
    }
}
