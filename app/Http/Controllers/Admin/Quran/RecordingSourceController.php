<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\Audio\RecordingSourceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class RecordingSourceController extends Controller
{
    public function index(int $reciter): View
    {
        $recording = Reciter::without('sources')->with(['translations:id,reciter_id,locale,name', 'profile.translations:id,profile_id,locale,name'])->findOrFail($reciter);
        $sources = $recording->sources()->withCount('recitations')->orderBy('priority')->orderBy('id')->get();

        return view('admin.reciters.sources', ['reciter' => $recording, 'sources' => $sources]);
    }

    public function store(Request $request, int $reciter): RedirectResponse
    {
        $recording = Reciter::findOrFail($reciter);
        $data = $this->validated($request);
        $source = $recording->sources()->firstOrNew(['provider' => $data['provider'], 'kind' => $data['kind'], 'source_key' => $data['source_key']]);
        $source->fill($data)->save();

        return back()->with('success', __('quran.sources.saved'));
    }

    public function update(Request $request, int $reciter, int $source): RedirectResponse
    {
        $recording = Reciter::findOrFail($reciter);
        $model = $recording->sources()->findOrFail($source);
        $data = $this->validated($request);
        $request->validate(['source_key' => [Rule::unique('quran_recording_sources')->ignore($source)->where(fn ($q) => $q->where('recording_id', $reciter)->where('provider', $data['provider'])->where('kind', $data['kind']))]]);
        // Changing a populated source's provider/layout would reinterpret existing assets.
        abort_if($model->recitations()->exists() && ($data['kind'] !== $model->kind || $data['provider'] !== $model->provider), 422, 'Add an alternate source to change provider or layout.');
        $model->update($data);

        return back()->with('success', __('quran.sources.saved'));
    }

    public function destroy(int $reciter, int $source): RedirectResponse
    {
        $model = Reciter::without('sources')->findOrFail($reciter)->sources()->withCount('recitations')->findOrFail($source);
        if ($model->recitations_count > 0) {
            return back()->with('error', __('quran.profiles_admin.source_delete_blocked', ['count' => $model->recitations_count]));
        }
        $model->delete();

        return back()->with('success', __('quran.profiles_admin.source_deleted'));
    }

    /** Probe the host and add every surah file this whole-surah source is missing. */
    public function fill(Request $request, int $reciter, int $source, RecordingSourceService $sources): RedirectResponse
    {
        $model = Reciter::without('sources')->findOrFail($reciter)->sources()->where('kind', 'surah')->findOrFail($source);
        $data = $request->validate(['naming' => ['required', Rule::in(['plain', 'padded'])], 'format' => ['required', Rule::in(['mp3', 'aac', 'opus'])]]);
        if (blank($model->base_url)) {
            return back()->with('error', __('quran.sources.fill_needs_base_url'));
        }
        $result = $sources->fillMissingSurahs($model, $data['naming'] === 'padded', $data['format']);

        return back()->with($result['created'] > 0 ? 'success' : 'error', __('quran.sources.fill_result', [
            'created' => $result['created'],
            'unavailable' => $result['unavailable'] === [] ? '—' : implode(', ', $result['unavailable']),
        ]));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(config('quran-audio.providers')))],
            'kind' => ['required', Rule::in(['surah', 'ayah'])],
            'source_key' => ['required', 'string', 'max:100'],
            'base_url' => ['nullable', 'url:http,https', 'max:500'],
            'external_reciter_id' => ['nullable', 'string', 'max:100'],
            'external_recording_id' => ['nullable', 'string', 'max:100'],
            'external_timing_id' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'metadata_json' => ['nullable', 'json', 'max:10000'],
        ]);
        $data['metadata'] = json_decode($data['metadata_json'] ?? '{}', true);
        unset($data['metadata_json']);
        if (isset($data['metadata']['verse_template'])) {
            $url = str_replace(['{surah}', '{ayah}'], ['001', '001'], $data['metadata']['verse_template']);
            abort_unless(filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true), 422, 'Invalid verse URL template.');
        }

        return $data;
    }
}
