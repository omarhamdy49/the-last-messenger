<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithImportRun;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Hadith\Models\HadithText;
use App\Domain\Hadith\Services\ArabicTextNormalizer;
use App\Domain\Hadith\Services\HadithAdminService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\UpdateHadithRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class HadithController extends Controller
{
    public function __construct(
        private readonly HadithAdminService $service,
        private readonly ArabicTextNormalizer $normalizer,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $collectionId = $request->integer('collection_id');
        $bookId = $request->integer('book_id');
        $grade = $request->string('grade')->toString();
        $status = $request->string('status')->toString();
        $perPage = in_array($request->integer('per_page'), [15, 30, 50], true)
            ? $request->integer('per_page')
            : 30;
        $previewLocales = array_values(array_unique([app()->getLocale(), 'ar', 'en']));

        $hadiths = Hadith::query()
            ->with([
                'collection:id,code,name',
                'book:id,collection_id,book_number,title',
                'chapter:id,book_id,chapter_number,title',
                'texts' => fn ($query) => $query->whereIn('locale', $previewLocales),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('hadith_number', 'like', "%{$search}%")
                        ->when(ctype_digit($search), fn (Builder $query) => $query
                            ->orWhere('sunnah_urn', (int) $search)
                            ->orWhere('sunnah_urn_en', (int) $search))
                        ->orWhereHas('texts', function (Builder $texts) use ($search): void {
                            if (DB::connection()->getDriverName() === 'mysql') {
                                $texts->whereFullText('search_body', $this->normalizer->normalize($search));
                            } else {
                                $texts->where('body', 'like', "%{$search}%");
                            }
                        });
                });
            })
            ->when($collectionId > 0, fn (Builder $query) => $query->where('collection_id', $collectionId))
            ->when($bookId > 0, fn (Builder $query) => $query->where('book_id', $bookId))
            ->when(in_array($grade, HadithGrade::values(), true), fn (Builder $query) => $query->where('display_grade', $grade))
            ->when($status === 'published', fn (Builder $query) => $query->where('is_published', true))
            ->when($status === 'draft', fn (Builder $query) => $query->where('is_published', false))
            ->orderBy('collection_id')
            ->orderBy('book_id')
            ->orderByRaw('COALESCE(in_book_number, 2147483647)')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $collections = HadithCollection::query()->orderBy('display_order')->get(['id', 'code', 'name']);
        $books = HadithBook::query()
            ->when($collectionId > 0, fn (Builder $query) => $query->where('collection_id', $collectionId))
            ->orderBy('collection_id')->orderBy('sort_order')
            ->get(['id', 'collection_id', 'book_number', 'title']);

        $stats = [
            'total' => Hadith::query()->count(),
            'published' => Hadith::query()->where('is_published', true)->count(),
            'ungraded' => Hadith::query()->where('display_grade', HadithGrade::UNKNOWN)->count(),
            'translations' => HadithText::query()->count(),
        ];

        $imports = HadithImportRun::query()->latest()->limit(5)->get();

        return view('admin.hadith.index', compact('hadiths', 'collections', 'books', 'stats', 'imports'));
    }

    public function edit(Hadith $hadith): View
    {
        $hadith->load(['collection', 'book', 'chapter', 'texts', 'gradings.scholar']);

        return view('admin.hadith.edit', [
            'hadith' => $hadith,
            'texts' => $hadith->texts->keyBy('locale'),
            'scholars' => HadithScholar::query()->orderBy('slug')->get(['id', 'slug', 'name']),
            'grades' => HadithGrade::cases(),
            'scopes' => GradeScope::cases(),
            'locales' => ['ar', 'en', 'fr', 'tr', 'ur'],
        ]);
    }

    public function update(UpdateHadithRequest $request, Hadith $hadith): RedirectResponse
    {
        $before = $hadith->toArray();
        $updated = $this->service->updateHadith($hadith, $request->validated());
        $this->logger->logUpdate($updated, $before, $updated->toArray(), $request->user('admin'));

        return redirect()->route('admin.hadith.edit', $updated)->with('success', __('hadith.admin.hadith_updated'));
    }
}
