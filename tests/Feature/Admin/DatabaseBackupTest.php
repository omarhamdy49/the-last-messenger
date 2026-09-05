<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Services\DatabaseDumpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A database archive is every password hash, Sanctum token and email address in
 * one downloadable file — a full account-takeover payload. These tests pin the
 * things that make serving it acceptable: who may reach it, that a filename
 * cannot escape the archive directory, and that every download is recorded.
 */
class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    private function dumps(): DatabaseDumpService
    {
        return app(DatabaseDumpService::class);
    }

    /** Writes a fake archive with a valid name; content is irrelevant to routing. */
    private function fakeArchive(string $name = 'tlm-testing-2026-01-01-000000.sql.gz'): string
    {
        $path = $this->dumps()->directory().'/'.$name;
        file_put_contents($path, gzencode("-- Dump completed on 2026-01-01 00:00:00\n"));

        return $path;
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dumps()->directory().'/tlm-testing-*.sql.gz') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_super_admin_can_download_an_archive(): void
    {
        $this->fakeArchive();

        $this->actingAs($this->createAdminUser(role: 'super_admin'), 'admin')
            ->get(route('admin.settings.backups.download', 'tlm-testing-2026-01-01-000000.sql.gz'))
            ->assertOk()
            ->assertHeader('content-type', 'application/gzip');
    }

    /**
     * Deliberately stricter than the rest of the settings hub: a plain admin can
     * open Settings and run commands, but must not be able to walk off with the
     * whole database.
     */
    public function test_a_plain_admin_cannot_download_an_archive(): void
    {
        $this->fakeArchive();

        $this->actingAs($this->createAdminUser(role: 'admin'), 'admin')
            ->get(route('admin.settings.backups.download', 'tlm-testing-2026-01-01-000000.sql.gz'))
            ->assertForbidden();
    }

    public function test_an_editor_cannot_download_an_archive(): void
    {
        $this->fakeArchive();

        $this->actingAs($this->createAdminUser(role: 'editor'), 'admin')
            ->get(route('admin.settings.backups.download', 'tlm-testing-2026-01-01-000000.sql.gz'))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_download_an_archive(): void
    {
        $this->fakeArchive();

        $this->get(route('admin.settings.backups.download', 'tlm-testing-2026-01-01-000000.sql.gz'))
            ->assertRedirect();
    }

    /**
     * The filename is a route parameter, so it is attacker-controlled. Neither a
     * traversal sequence nor an arbitrary readable path may resolve.
     */
    public function test_a_traversal_filename_cannot_escape_the_archive_directory(): void
    {
        $admin = $this->createAdminUser(role: 'super_admin');

        foreach ([
            '..%2F..%2F..%2F.env',
            '....//....//.env',
            'tlm-testing-2026-01-01-000000.sql.gz/../../../.env',
        ] as $attempt) {
            $response = $this->actingAs($admin, 'admin')
                ->get('/su/portal/v1/admin/settings/backups/'.$attempt.'/download');

            $this->assertContains(
                $response->getStatusCode(),
                [403, 404],
                "traversal attempt was not rejected: {$attempt}"
            );
        }
    }

    public function test_a_filename_outside_the_naming_pattern_is_rejected(): void
    {
        // A real file, but not one we wrote — it must not be downloadable.
        $stray = $this->dumps()->directory().'/secrets.sql.gz';
        file_put_contents($stray, 'x');

        try {
            $this->actingAs($this->createAdminUser(role: 'super_admin'), 'admin')
                ->get(route('admin.settings.backups.download', 'secrets.sql.gz'))
                ->assertNotFound();
        } finally {
            @unlink($stray);
        }
    }

    public function test_a_download_is_recorded_in_the_admin_log(): void
    {
        $this->fakeArchive();
        $admin = $this->createAdminUser(role: 'super_admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.backups.download', 'tlm-testing-2026-01-01-000000.sql.gz'))
            ->assertOk();

        $this->assertDatabaseHas('admin_logs', [
            'user_id' => $admin->id,
            'action' => 'database.backup.downloaded',
        ]);
    }

    public function test_super_admin_can_delete_an_archive_and_it_is_logged(): void
    {
        $path = $this->fakeArchive();
        $admin = $this->createAdminUser(role: 'super_admin');

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.settings.backups.destroy', basename($path)))
            ->assertRedirect(route('admin.settings'));

        $this->assertFileDoesNotExist($path);
        $this->assertDatabaseHas('admin_logs', ['action' => 'database.backup.deleted']);
    }

    public function test_a_plain_admin_cannot_delete_an_archive(): void
    {
        $path = $this->fakeArchive();

        $this->actingAs($this->createAdminUser(role: 'admin'), 'admin')
            ->delete(route('admin.settings.backups.destroy', basename($path)))
            ->assertForbidden();

        $this->assertFileExists($path);
    }

    public function test_archives_are_stored_outside_the_public_disk(): void
    {
        // A dump reachable by URL guess would be catastrophic.
        $this->assertStringNotContainsString('app/public', $this->dumps()->directory());
        $this->assertStringContainsString('app/private', $this->dumps()->directory());
    }

    public function test_the_listing_ignores_files_it_did_not_write(): void
    {
        $this->fakeArchive();
        $stray = $this->dumps()->directory().'/not-ours.sql.gz';
        file_put_contents($stray, 'x');

        try {
            $names = array_column($this->dumps()->archives(), 'name');

            $this->assertContains('tlm-testing-2026-01-01-000000.sql.gz', $names);
            $this->assertNotContains('not-ours.sql.gz', $names);
        } finally {
            @unlink($stray);
        }
    }

    public function test_settings_page_hides_the_panel_from_a_plain_admin(): void
    {
        $this->fakeArchive();

        $html = $this->actingAs($this->createAdminUser(role: 'admin'), 'admin')
            ->get(route('admin.settings'))
            ->assertOk()
            ->getContent();

        // Not even the filename should leak to someone who cannot download it.
        $this->assertStringNotContainsString('tlm-testing-2026-01-01-000000.sql.gz', $html);
    }
}
