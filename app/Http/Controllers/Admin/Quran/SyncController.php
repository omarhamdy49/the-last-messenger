<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Import\QuranFoundation\Sync\ContentSyncService;
use App\Domain\Quran\Services\QuranAdminCommands;
use App\Domain\Quran\Services\ResourceService;
use App\Http\Controllers\Admin\Quran\Concerns\QueuesQuranCommands;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quran Foundation sync dashboard: sync states, recent runs, per-resource freshness,
 * and buttons that queue a bootstrap, an incremental sync or an asset download.
 */
final class SyncController extends Controller
{
    use QueuesQuranCommands;

    private const FONT_SETS = ['v1', 'v2', 'hafs', 'indopak'];

    public function __construct(
        private readonly QuranAdminCommands $commands,
        private readonly ResourceService $resources,
        private readonly ContentSyncService $sync,
    ) {}

    public function index(): View
    {
        return view('admin.quran.sync.index', [
            'states' => $this->commands->states(),
            'runs' => $this->commands->recentRuns(20),
            'resources' => $this->resources->syncEnabled(),
            'filter' => $this->sync->canonicalFilter(),
            'fontSets' => self::FONT_SETS,
        ]);
    }

    public function run(int $run): View
    {
        return view('admin.quran.sync.run', ['run' => $this->commands->run($run) ?? abort(404)]);
    }

    public function bootstrap(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['resources' => ['nullable', 'string', 'max:1000', 'regex:/^[a-z_]+:[0-9,]+(;[a-z_]+:[0-9,]+)*$/']]);

        return $this->queueCommand($request, 'quran:qf:sync', array_filter([
            'bootstrap' => true,
            'resources' => $validated['resources'] ?? null,
        ]));
    }

    public function incremental(Request $request): RedirectResponse|JsonResponse
    {
        return $this->queueCommand($request, 'quran:qf:sync');
    }

    public function assets(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'images' => ['boolean'],
            'fonts' => ['nullable', 'array'],
            'fonts.*' => ['in:'.implode(',', self::FONT_SETS)],
        ]);

        $options = array_filter([
            'images' => (bool) ($validated['images'] ?? false),
            'fonts' => implode(',', $validated['fonts'] ?? []),
        ]);

        if ($options === []) {
            return back()->with('error', __('quran.manage.choose_assets'));
        }

        return $this->queueCommand($request, 'quran:qf:download-assets', $options);
    }
}
