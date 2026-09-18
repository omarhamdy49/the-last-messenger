<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site\Quran;

use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Services\NavigationService;
use App\Domain\Quran\Services\ReaderService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Juz / hizb / rubʿ / manzil / rukūʿ pages and mushaf pages.
 */
final class NavigationController extends Controller
{
    private const PER_PAGE = [10, 20, 50];

    public function __construct(
        private readonly ReaderService $reader,
        private readonly NavigationService $navigation,
    ) {}

    public function juz(Request $request, int $juz): View
    {
        return $this->section($request, SectionType::JUZ, $juz);
    }

    public function hizb(Request $request, int $hizb): View
    {
        return $this->section($request, SectionType::HIZB, $hizb);
    }

    public function rub(Request $request, int $rub): View
    {
        return $this->section($request, SectionType::RUB_EL_HIZB, $rub);
    }

    public function manzil(Request $request, int $manzil): View
    {
        return $this->section($request, SectionType::MANZIL, $manzil);
    }

    public function ruku(Request $request, int $ruku): View
    {
        return $this->section($request, SectionType::RUKU, $ruku);
    }

    public function page(int $page): View
    {
        abort_if($page < 1 || $page > 604, 404);
        $verses = $this->reader->pagePayload($page, app()->getLocale());
        abort_if($verses === [], 404);

        return view('public.quran.page', [
            'verses' => $verses,
            'pageNumber' => $page,
            'hasPrevPage' => $page > 1,
            'hasNextPage' => $page < 604,
            'chaptersOnPage' => array_values(array_unique(array_map(static fn (array $v): string => $v['surahNumber'].'|'.$v['surahName'], $verses))),
        ]);
    }

    private function section(Request $request, SectionType $type, int $number): View
    {
        abort_if($number < 1 || $number > $type->max(), 404);

        $perPage = in_array($request->integer('per_page', 20), self::PER_PAGE, true) ? $request->integer('per_page', 20) : 20;
        $page = max(1, $request->integer('page', 1));
        $payload = $this->reader->sectionPayload($type, $number, $page, $perPage, app()->getLocale());
        abort_if($payload['verses'] === [] && $page === 1, 404);

        return view('public.quran.section', $payload + [
            'type' => $type,
            'number' => $number,
            'section' => $this->navigation->section($type, $number),
            'chapterNames' => collect($this->reader->chapterList(app()->getLocale()))->pluck('nameAr', 'number')->all(),
            'currentPage' => $page,
            'perPage' => $perPage,
        ]);
    }
}
