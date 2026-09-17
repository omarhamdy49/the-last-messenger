<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Services\ReciterProfileMergeService;
use App\Domain\Quran\Services\ReciterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\ReciterProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ReciterProfileController extends Controller
{
    private const FILTERS = ['search', 'status', 'featured', 'country', 'recordings', 'sort'];

    public function __construct(private readonly ReciterService $reciters) {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $filters = array_filter($request->only(self::FILTERS), 'filled');
        $profiles = ReciterProfile::query()
            ->with([
                'translations:id,profile_id,locale,name',
                'recordings' => fn ($q) => $q->without('sources')->select('id', 'profile_id', 'style', 'riwayah', 'is_active')->orderBy('display_order')->orderBy('id'),
            ])
            ->withCount('recordings')
            ->adminFilter($filters, $locale)
            ->paginate(24)
            ->withQueryString();

        $stats = DB::table('quran_reciter_profiles')->selectRaw(
            'COUNT(*) AS total, COALESCE(SUM(is_active), 0) AS active, COALESCE(SUM(is_featured), 0) AS featured,
             (SELECT COUNT(*) FROM (SELECT profile_id FROM quran_reciters WHERE profile_id IS NOT NULL GROUP BY profile_id HAVING COUNT(*) > 1) m) AS multiple'
        )->first();

        return view('admin.reciters.profiles', [
            'profiles' => $profiles,
            'filters' => $filters,
            'stats' => $stats,
            'countries' => ReciterProfile::whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country'),
        ]);
    }

    public function create(): View
    {
        return view('admin.reciters.profile-edit', ['profile' => new ReciterProfile(['is_active' => true, 'display_order' => 0])]);
    }

    public function store(ReciterProfileRequest $request): RedirectResponse
    {
        $payload = $request->payload();
        $payload['attributes']['profile_image'] = $this->image($request, null) ?: null;
        $profile = DB::transaction(function () use ($payload): ReciterProfile {
            // Admin-created people get their own identity namespace so provider grouping never claims them.
            $profile = ReciterProfile::create($payload['attributes'] + ['identity_key' => 'admin:'.Str::ulid()]);
            foreach ($payload['translations'] as $locale => $values) {
                $profile->translations()->create(['locale' => $locale] + $values);
            }

            return $profile;
        });

        return redirect()->route('admin.reciter-profiles.edit', $profile->id)->with('success', __('quran.profiles_admin.created'));
    }

    public function edit(int $profile): View
    {
        $model = ReciterProfile::with([
            'translations',
            'recordings' => fn ($q) => $q->without('sources')->with('translations:id,reciter_id,locale,name')
                ->withCount(['sources', 'recitations as surahs_with_audio' => fn ($r) => $r->select(DB::raw('count(distinct surah_id)'))])
                ->orderBy('display_order')->orderBy('id'),
        ])->findOrFail($profile);

        $unassigned = Reciter::without('sources')->whereNull('profile_id')->recitationsOnly()
            ->with('translations:id,reciter_id,locale,name')->select('id', 'slug', 'style', 'riwayah')
            ->orderBy('id')->limit(300)->get(); // ponytail: plain select; switch to a search endpoint if unassigned recordings grow past this.

        $mergeCandidates = ReciterProfile::whereKeyNot($model->id)->with('translations:id,profile_id,locale,name')
            ->withCount('recordings')->select('id', 'slug')->orderBy('id')->get();

        return view('admin.reciters.profile-edit', ['profile' => $model, 'unassigned' => $unassigned, 'mergeCandidates' => $mergeCandidates]);
    }

    public function update(ReciterProfileRequest $request, int $profile): RedirectResponse
    {
        $model = ReciterProfile::findOrFail($profile);
        $payload = $request->payload();
        $image = $this->image($request, $model->profile_image);
        if ($image !== false) {
            $payload['attributes']['profile_image'] = $image;
        }
        DB::transaction(function () use ($model, $payload): void {
            foreach ($payload['translations'] as $locale => $values) {
                $model->translations()->updateOrCreate(['locale' => $locale], $values);
            }
            $model->update($payload['attributes']);
        });

        return back()->with('success', __('quran.profiles_admin.updated'));
    }

    public function toggle(Request $request, int $profile, string $field): JsonResponse|RedirectResponse
    {
        $model = ReciterProfile::findOrFail($profile);
        $column = 'is_'.$field;
        $model->update([$column => ! $model->{$column}]);
        $message = __('quran.profiles_admin.toggled');

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message, 'data' => [$column => $model->{$column}]])
            : back()->with('success', $message);
    }

    public function destroy(Request $request, int $profile): JsonResponse|RedirectResponse
    {
        $model = ReciterProfile::withCount('recordings')->findOrFail($profile);
        if ($model->recordings_count > 0) {
            $message = __('quran.profiles_admin.delete_blocked', ['count' => $model->recordings_count]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }
        $model->delete();
        $message = __('quran.profiles_admin.deleted');

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : redirect()->route('admin.reciter-profiles.index')->with('success', $message);
    }

    /** Fold another reciter into this one; its recordings become this reciter's recordings. */
    public function merge(Request $request, int $profile, ReciterProfileMergeService $merger): RedirectResponse
    {
        $target = ReciterProfile::findOrFail($profile);
        $data = $request->validate(['source_profile_id' => ['required', 'integer', Rule::exists('quran_reciter_profiles', 'id')->whereNot('id', $target->id)]]);
        $source = ReciterProfile::with('translations')->findOrFail($data['source_profile_id']);
        $name = $source->displayName(app()->getLocale());
        $moved = $merger->merge($target, $source);

        return back()->with('success', __('quran.profiles_admin.merged', ['name' => $name, 'count' => $moved]));
    }

    public function attach(Request $request, int $profile): RedirectResponse
    {
        $model = ReciterProfile::findOrFail($profile);
        $data = $request->validate(['recording_id' => ['required', 'integer',
            Rule::exists('quran_reciters', 'id')->whereNull('profile_id')->where('kind', 'recitation')]]);
        $recording = Reciter::without('sources')->findOrFail($data['recording_id']);

        DB::transaction(function () use ($model, $recording): void {
            $recording->update(['profile_id' => $model->id]);
            if (! $model->default_recording_id && $recording->is_active) {
                $model->update(['default_recording_id' => $recording->id]);
            }
        });

        return back()->with('success', __('quran.profiles_admin.attached'));
    }

    public function detach(int $profile, int $recording): RedirectResponse
    {
        $model = ReciterProfile::findOrFail($profile);
        $item = $model->recordings()->without('sources')->findOrFail($recording);
        if ($model->default_recording_id === $item->id) {
            return back()->with('error', __('quran.profiles_admin.detach_default_blocked'));
        }
        $item->update(['profile_id' => null]);

        return back()->with('success', __('quran.profiles_admin.detached'));
    }

    public function setDefault(int $profile, int $recording): RedirectResponse
    {
        $model = ReciterProfile::findOrFail($profile);
        $item = $model->recordings()->without('sources')->recitationsOnly()->active()->findOrFail($recording);
        $model->update(['default_recording_id' => $item->id]);

        return back()->with('success', __('quran.profiles_admin.default_set'));
    }

    /** @return string|false|null see ReciterService::resolveImage() */
    private function image(ReciterProfileRequest $request, ?string $current): string|false|null
    {
        return $this->reciters->resolveImage(
            $request->file('profile_image_file') ?? ($request->integer('profile_image_id') ?: null),
            $request->validated('profile_image_url'),
            $request->boolean('remove_profile_image'),
            $current,
        );
    }
}
