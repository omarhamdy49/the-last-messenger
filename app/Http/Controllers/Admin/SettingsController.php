<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\ArtisanCommandService;
use App\Http\Controllers\Controller;
use App\Models\CommandRun;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin settings hub. The Commands tab launches whitelisted Artisan commands;
 * long ones are queued and reported through a CommandRun row so the browser
 * never holds an open request for the duration of an import.
 */
class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ArtisanCommandService $commands) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'catalog' => $this->commands->catalog(),
            'runs' => $this->presentRuns(),
            'pollInterval' => (int) config('artisan-commands.poll_interval', 2000),
        ]);
    }

    /**
     * Launch a command. Queued commands answer immediately with a run to poll.
     *
     * POST /settings/commands/run
     */
    public function runCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:150'],
            'arguments' => ['sometimes', 'array'],
            'options' => ['sometimes', 'array'],
        ]);

        $meta = $this->commands->find($validated['command']);

        if ($meta === null) {
            return $this->errorResponse(__('admin.cmd_not_allowed'), 403, 'command_not_allowed');
        }

        // Required arguments are declared by the command's own signature, so
        // the check reads them rather than duplicating a list here.
        foreach ($meta['arguments'] as $argument) {
            $value = $validated['arguments'][$argument['name']] ?? null;

            if ($argument['required'] && ($value === null || $value === '')) {
                return $this->errorResponse(
                    __('admin.cmd_argument_required', ['name' => $argument['name']]),
                    422,
                    'argument_required',
                );
            }
        }

        try {
            $run = $this->commands->start($meta, $validated, $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return $this->errorResponse(__('admin.cmd_start_failed'), 500, 'command_start_failed');
        }

        Log::info('admin.artisan_command.started', [
            'command' => $meta['name'],
            'run' => $run->id,
            'user' => $request->user()?->id,
            'queued' => $meta['queued'],
        ]);

        return $this->successResponse([
            'run' => $this->presentRun($run),
            'status_url' => route('admin.settings.commands.status', ['run' => $run->id]),
        ], __('admin.cmd_started'));
    }

    /**
     * GET /settings/commands/runs/{run}
     */
    public function runStatus(CommandRun $run): JsonResponse
    {
        return $this->successResponse($this->presentRun($run, withOutput: true));
    }

    /**
     * GET /settings/commands/runs — recent history for the tab.
     */
    public function runHistory(): JsonResponse
    {
        return $this->successResponse($this->presentRuns());
    }

    /**
     * The last runs, in the exact shape the tab renders — so the server-side
     * seed and the polled refresh can never drift apart.
     *
     * @return array<int, array<string, mixed>>
     */
    private function presentRuns(): array
    {
        return CommandRun::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (CommandRun $run): array => $this->presentRun($run))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRun(CommandRun $run, bool $withOutput = false): array
    {
        $payload = [
            'id' => $run->id,
            'command' => $run->command,
            'status' => $run->status,
            'is_queued' => $run->is_queued,
            'is_terminal' => $run->isTerminal(),
            'percent' => $run->progressPercent(),
            'current' => $run->progress_current,
            'total' => $run->progress_total,
            'message' => $run->message,
            'error' => $run->error,
            'exit_code' => $run->exit_code,
            'duration' => $run->durationSeconds(),
            'user' => $run->user?->name,
            'started_at' => $run->started_at?->toDateTimeString(),
            'finished_at' => $run->finished_at?->toDateTimeString(),
            'finished_human' => $run->finished_at?->diffForHumans(),
        ];

        if ($withOutput) {
            $payload['output'] = $run->output;
        }

        return $payload;
    }
}
