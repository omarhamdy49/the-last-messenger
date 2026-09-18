<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\ResourceData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfApiException;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Services\ResourceService;
use App\Domain\Quran\Services\StructureService;
use App\Http\Controllers\Admin\Quran\Concerns\QueuesQuranCommands;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\ResourceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * One catalogue screen for every resource kind (the kind comes from the URL):
 * translations, tafsirs, chapter infos, verse media, ayah recitations, chapter
 * reciters and word-by-word resources.
 */
final class ResourceController extends Controller
{
    use QueuesQuranCommands;

    public function __construct(
        private readonly ResourceService $resources,
        private readonly StructureService $structure,
    ) {}

    public function index(Request $request, string $kind): View
    {
        $resourceKind = $this->kindOf($kind);
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'language' => $request->string('language')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'source' => $request->string('source')->toString() ?: null,
        ];

        return view('admin.quran.resources.index', [
            'kind' => $resourceKind,
            'resources' => $this->resources->adminIndex($resourceKind, $filters, $request->integer('per_page', 30)),
            'languages' => $this->structure->languageOptions(),
        ]);
    }

    public function create(string $kind): View
    {
        return view('admin.quran.resources.form', [
            'kind' => $this->kindOf($kind),
            'resource' => null,
            'languages' => $this->structure->languageOptions(),
        ]);
    }

    public function store(ResourceRequest $request, string $kind): RedirectResponse|JsonResponse
    {
        $resourceKind = $this->kindOf($kind);
        $resource = $this->resources->create($resourceKind, $this->data($request));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $resource->id]], 201)
            : redirect()->route('admin.quran.resources.show', $resource->id)->with('success', __('quran.manage.saved'));
    }

    public function show(int $resource): View
    {
        $model = $this->resources->find($resource) ?? abort(404);

        return view('admin.quran.resources.show', [
            'resource' => $model,
            'kind' => $model->kind,
            'coverage' => $this->resources->coverage($model),
        ]);
    }

    public function edit(int $resource): View
    {
        $model = $this->resources->find($resource) ?? abort(404);

        return view('admin.quran.resources.form', [
            'kind' => $model->kind,
            'resource' => $model,
            'languages' => $this->structure->languageOptions(),
        ]);
    }

    public function update(ResourceRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $request->existing() ?? abort(404);
        $updated = $this->resources->update($resource, $this->data($request));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $updated->id]])
            : redirect()->route('admin.quran.resources.show', $resource)->with('success', __('quran.manage.saved'));
    }

    public function destroy(Request $request, int $resource): RedirectResponse|JsonResponse
    {
        $model = $this->resources->find($resource) ?? abort(404);
        $deleted = $this->resources->delete($resource);
        $message = $deleted ? __('quran.manage.deleted') : __('quran.manage.qf_resource_hidden');

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['deleted' => $deleted]])
            : redirect()->route('admin.quran.resources.index', $model->kind->routeSegment())->with('success', $message);
    }

    public function toggle(Request $request, int $resource, string $field): RedirectResponse|JsonResponse
    {
        $this->resources->find($resource) ?? abort(404);

        try {
            $updated = $this->resources->toggle($resource, $field);
        } catch (InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => __('quran.manage.sync_needs_qf')], 422)
                : back()->with('error', __('quran.manage.sync_needs_qf'));
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => [$field => (bool) $updated->getAttribute($field)]])
            : back()->with('success', __('quran.manage.saved'));
    }

    public function reorder(Request $request, string $kind): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['ids' => ['required', 'array', 'max:1000'], 'ids.*' => ['integer', 'min:1']]);
        $this->resources->reorder($this->kindOf($kind), array_map('intval', $validated['ids']));

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', __('quran.manage.saved'));
    }

    public function catalogue(string $kind): View
    {
        $resourceKind = $this->kindOf($kind);
        $rows = [];
        $error = null;

        try {
            $rows = $resourceKind->qfCataloguePath() === null ? [] : $this->resources->qfCatalogue($resourceKind);
        } catch (QfApiException $e) {
            report($e);
            $error = __('quran.manage.qf_unavailable');
        }

        return view('admin.quran.resources.catalogue', ['kind' => $resourceKind, 'rows' => $rows, 'error' => $error]);
    }

    public function importFromQf(Request $request, string $kind): RedirectResponse|JsonResponse
    {
        $resourceKind = $this->kindOf($kind);
        $validated = $request->validate(['qf_id' => ['required', 'integer', 'min:1']]);

        try {
            $resource = $this->resources->importFromQf($resourceKind, (int) $validated['qf_id']);
        } catch (QfApiException|RuntimeException $e) {
            report($e);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => __('quran.manage.qf_import_failed')], 422)
                : back()->with('error', __('quran.manage.qf_import_failed'));
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $resource->id]], 201)
            : redirect()->route('admin.quran.resources.show', $resource->id)->with('success', __('quran.manage.imported'));
    }

    /**
     * Pull this one resource from Quran Foundation now.
     */
    public function sync(Request $request, int $resource): RedirectResponse|JsonResponse
    {
        $model = $this->resources->find($resource) ?? abort(404);
        $command = $this->syncCommand($model);

        if ($command === null) {
            return back()->with('error', __('quran.manage.sync_not_available'));
        }

        return $this->queueCommand($request, $command[0], $command[1]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private function syncCommand(QuranResource $resource): ?array
    {
        if ($resource->qf_id === null) {
            return null;
        }

        return match ($resource->kind) {
            ResourceKind::CHAPTER_INFO => ['quran:qf:import-chapter-infos', ['resource' => (string) $resource->id]],
            ResourceKind::CHAPTER_RECITER => ['quran:qf:import-timestamps', ['reciter' => (string) $resource->id]],
            ResourceKind::VERSE_MEDIA, ResourceKind::RECITATION => null,
            default => ['quran:qf:sync', ['resources' => $resource->kind->syncGroup()?->value.':'.$resource->qf_id]],
        };
    }

    private function data(ResourceRequest $request): ResourceData
    {
        $image = $request->file('image')?->store('quran/resources', 'public');

        return ResourceData::fromValidated($request->validated(), $image ?: null);
    }

    private function kindOf(string $segment): ResourceKind
    {
        return ResourceKind::fromRouteSegment($segment) ?? abort(404);
    }
}
