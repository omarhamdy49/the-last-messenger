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
        $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', ['command' => 'migrate:fresh'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'command_not_allowed');

        $this->assertDatabaseCount('command_runs', 0);
    }

    public function test_a_missing_required_argument_is_rejected_before_execution(): void
    {
        $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', [
                'command' => 'quran:import-tafsir',
                'arguments' => ['tafsirId' => ''],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'argument_required');
    }

    public function test_a_quick_command_runs_inline_and_records_its_output(): void
    {
        $response = $this->actingAs($this->createAdminUser())
            ->postJson('/su/portal/v1/admin/settings/commands/run', ['command' => 'cache:clear']);

        $response->assertOk()->assertJsonPath('data.run.status', CommandRun::STATUS_COMPLETED);

        $run = CommandRun::firstOrFail();
        $this->assertSame(0, $run->exit_code);
        $this->assertNotEmpty($run->output);
        $this->assertNotNull($run->finished_at);
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
        // Both prunes were taken off the scheduler on purpose: they delete rows,
        // so they run when someone decides to, from the settings tab.
        $scheduled = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) $event->command)
            ->all();

        foreach (['quran:ai-prune', 'media:prune-view-events'] as $command) {
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
