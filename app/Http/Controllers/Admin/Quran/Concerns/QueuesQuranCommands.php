<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran\Concerns;

use App\Domain\Quran\Services\QuranAdminCommands;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Queue a whitelisted Quran command from an admin button and answer with the run id.
 */
trait QueuesQuranCommands
{
    /**
     * @param  array<string, mixed>  $options
     */
    protected function queueCommand(Request $request, string $command, array $options = []): RedirectResponse|JsonResponse
    {
        $run = app(QuranAdminCommands::class)->queue($command, $options, $request->user()?->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => ['run_id' => $run->id, 'command' => $command]], 202);
        }

        return back()->with('success', __('quran.manage.command_queued', ['command' => $command, 'run' => $run->id]));
    }
}
