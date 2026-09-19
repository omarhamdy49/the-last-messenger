<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Models\CommandRun;
use App\Domain\Shared\Services\ArtisanCommandService;
use App\Jobs\RunArtisanCommandJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Settings → Artisan commands tab.
 *
 * The load-bearing parts are the whitelist boundary (this must never become a
 * shell), the flag/argument mapping, and the queued path that keeps long
 * imports off the request timeout.
 */
final class ArtisanCommandsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_cannot_open_settings(): void
    {
        $this->get('/su/portal/v1/admin/settings')->assertRedirect();
    }

    public function test_admin_sees_the_commands_tab(): void
    {
        $response = $this->actingAs($this->createAdminUser())->get('/su/portal/v1/admin/settings');

        $response->assertOk();
        $response->assertSee('hadith:import');
        $response->assertSee('cache:clear');
    }

    public function test_editor_cannot_execute_a_command(): void
    {
        $editor = $this->createAdminUser([], 'editor');

        $this->actingAs($editor)
            ->postJson('/su/portal/v1/admin/settings/commands/run', ['command' => 'cache:clear'])
            ->assertForbidden();

        $this->assertDatabaseCount('command_runs', 0);
    }

    public function test_a_command_outside_the_whitelist_is_rejected(): void
    {
        // Deliberately an inert command: this posts a real name at a real
        // endpoint, so a regression in the whitelist must not cost us the
        // schema. `migrate:fresh` used to be the name here — it drops every
        // table, and RefreshDatabase migrates once per process, so a single
        // slip would have poisoned every test that ran after this one.
        $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', ['command' => 'route:list'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'command_not_allowed');

        $this->assertDatabaseCount('command_runs', 0);
    }

    public function test_a_schema_destroying_command_cannot_be_whitelisted(): void
    {
        // The whitelist is a config file, so it is one careless line away from
        // exposing a "wipe the database" button. Asserted against the service
        // rather than over HTTP on purpose: if the guard ever breaks, this test
        // fails instead of executing migrate:fresh against the test database.
        config()->set('artisan-commands.groups.testing', [
            'icon' => 'flask-conical',
            'commands' => ['migrate:fresh' => [], 'db:wipe' => []],
        ]);

        $service = app(ArtisanCommandService::class);

        foreach (['migrate:fresh', 'db:wipe'] as $command) {
            $this->assertNull($service->find($command), "{$command} must never be runnable.");
        }

        $listed = collect($service->catalog())
            ->flatMap(fn (array $group): array => array_column($group['commands'], 'name'))
            ->all();

        $this->assertEmpty(array_intersect($listed, ['migrate:fresh', 'db:wipe']));
    }

    public function test_a_missing_required_argument_is_rejected_before_execution(): void
    {
        $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', [
                'command' => 'quran:reciter:fetch',
                'arguments' => ['resource' => ''],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'argument_required');
    }

    public function test_a_quick_command_runs_inline_and_records_its_output(): void
    {
        // `migrate:status`, not `cache:clear` — every cache-clearing command is queued now
        // (see the next test for why), so cache:clear is no longer an inline command.
        $response = $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', ['command' => 'migrate:status']);

        $response->assertOk()->assertJsonPath('data.run.status', CommandRun::STATUS_COMPLETED);

        $run = CommandRun::firstOrFail();
        $this->assertSame(0, $run->exit_code);
        $this->assertNotEmpty($run->output);
        $this->assertNotNull($run->finished_at);
    }

    /**
     * Every command that drops the config cache MUST be queued.
     *
     * ArtisanCommandService::start() runs non-queued commands inline, in the web request, as
     * www-data. php-fpm cannot be relied on to read shared/.env directly — a hand-edit
     * silently resets it to 600 deployer:deployer — in which case
     * bootstrap/cache/config.php is the only configuration source it has. Clearing that
     * from inside a web request removed APP_KEY and the DB credentials from every
     * subsequent request and took the whole site down until someone SSHed in.
     *
     * Queue workers run as the deploy user, which can read .env, so the same command is
     * safe there. This test is the guard against someone making them inline again for
     * responsiveness.
     */
    public function test_cache_clearing_commands_are_queued_never_run_in_the_request(): void
    {
        $config = config('artisan-commands.groups.cache.commands', []);

        $this->assertNotEmpty($config, 'the cache command group vanished');

        foreach ($config as $command => $settings) {
            $this->assertTrue(
                (bool) ($settings['queued'] ?? false),
                "[{$command}] must be queued — running it inline as www-data can take the site down."
            );
        }
    }

    public function test_a_long_command_is_queued_rather_than_run_in_the_request(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', [
                'command' => 'hadith:import',
                'options' => ['collection' => 'forty', 'dry-run' => true],
            ]);

        $response->assertOk()->assertJsonPath('data.run.status', CommandRun::STATUS_QUEUED);

        $run = CommandRun::firstOrFail();
        Queue::assertPushed(RunArtisanCommandJob::class, fn (RunArtisanCommandJob $job): bool => $job->runId === $run->id);

        // Flags are present-or-absent, values are forwarded, and --force is
        // forced on so the command cannot block on a console prompt.
        $this->assertEquals([
            '--collection' => 'forty',
            '--dry-run' => true,
            '--force' => true,
        ], $run->input);
    }

    public function test_an_unchecked_flag_is_not_forwarded(): void
    {
        Queue::fake();

        $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', [
                'command' => 'hadith:import',
                'options' => ['collection' => '', 'dry-run' => false],
            ])
            ->assertOk();

        // An unchecked box must not become "--dry-run=0" — Symfony would read
        // that as the flag being set, and a real import would run.
        $this->assertSame(['--force' => true], CommandRun::firstOrFail()->input);
    }

    public function test_progress_is_derived_from_the_command_output(): void
    {
        $service = app(ArtisanCommandService::class);

        $run = CommandRun::create([
            'command' => 'about',
            'input' => ['--only' => 'environment'],
            'status' => CommandRun::STATUS_QUEUED,
            'is_queued' => true,
        ]);

        $service->execute($run->refresh());
        $run->refresh();

        $this->assertSame(CommandRun::STATUS_COMPLETED, $run->status);
        $this->assertTrue($run->isTerminal());
        $this->assertStringContainsString('Laravel Version', (string) $run->output);
    }

    public function test_a_command_printing_n_of_total_drives_the_progress_bar(): void
    {
        // Progress is read from output no command was changed to produce —
        // any "n/total" (Symfony's ProgressBar, or a hand-rolled step counter)
        // has to move the bar.
        Artisan::command('test:progress', function (): int {
            $this->line('Fetching chapters 0/3');
            $this->line('[2/3] importing');

            return 0;
        })->purpose('Progress fixture');

        config()->set('artisan-commands.groups.testing', [
            'icon' => 'flask-conical',
            'commands' => ['test:progress' => ['queued' => true]],
        ]);

        $run = CommandRun::create([
            'command' => 'test:progress',
            'status' => CommandRun::STATUS_QUEUED,
            'is_queued' => true,
        ]);

        app(ArtisanCommandService::class)->execute($run);
        $run->refresh();

        $this->assertSame(CommandRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(2, $run->progress_current);
        $this->assertSame(3, $run->progress_total);
        $this->assertSame(66, $run->progressPercent());
    }

    public function test_a_failing_command_is_recorded_as_failed(): void
    {
        Artisan::command('test:boom', fn (): int => 1)->purpose('Failure fixture');

        config()->set('artisan-commands.groups.testing', [
            'icon' => 'flask-conical',
            'commands' => ['test:boom' => []],
        ]);

        $run = CommandRun::create(['command' => 'test:boom', 'status' => CommandRun::STATUS_QUEUED]);

        app(ArtisanCommandService::class)->execute($run);
        $run->refresh();

        $this->assertSame(CommandRun::STATUS_FAILED, $run->status);
        $this->assertSame(1, $run->exit_code);
        $this->assertNotNull($run->finished_at);
    }

    public function test_the_retention_commands_are_manual_only(): void
    {
        // The prunes and the QF sync were taken off the scheduler on purpose: they
        // delete or rewrite rows, so they run when someone decides to, from the settings tab.
        $scheduled = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) $event->command)
            ->all();

        foreach (['quran:ai-prune', 'media:prune-view-events', 'quran:reciter:prune-images', 'quran:qf:sync'] as $command) {
            $this->assertEmpty(
                array_filter($scheduled, fn (string $c): bool => str_contains($c, $command)),
                "{$command} must not be scheduled.",
            );
            $this->assertNotNull(app(ArtisanCommandService::class)->find($command));
        }
    }

    public function test_the_status_endpoint_exposes_output_for_polling(): void
    {
        $run = CommandRun::create([
            'command' => 'cache:clear',
            'status' => CommandRun::STATUS_RUNNING,
            'progress_current' => 3,
            'progress_total' => 9,
            'output' => 'working',
        ]);

        $this->actingAs($this->createAdminUser())
            ->getJson("/su/portal/v1/admin/settings/commands/runs/{$run->id}")
            ->assertOk()
            ->assertJsonPath('data.percent', 33)
            ->assertJsonPath('data.is_terminal', false)
            ->assertJsonPath('data.output', 'working');
    }
}
