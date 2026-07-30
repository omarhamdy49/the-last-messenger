<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Hadith\Services\HadithQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class HadithController extends Controller
{
    public function __construct(
        private readonly HadithQueryService $hadithQueryService,
    ) {}

    /**
     * The canonical page: /hadith/{collection}:{number}
     *
     * `$number` is a string because the source uses forms like `1a`.
     */
    public function show(string $collection, string $number): View
    {
        $hadith = $this->hadithQueryService->findByCollectionAndNumber($collection, $number);

        // Unpublished and unknown are the same answer on purpose: an empty 200
        // would tell a reader the hadith exists but is being withheld.
        if ($hadith === null) {
            throw new NotFoundHttpException;
        }

        return view('public.hadith.show', ['hadith' => $hadith->toArray()]);
    }

    /**
     * Permalink lookup: /hadith/urn/{urn}. Roughly 35% of records carry no urn,
     * so a miss here is an ordinary 404, not an error condition.
     */
    public function urn(int $urn): View
    {
        $hadith = $this->hadithQueryService->findByUrn($urn);

        if ($hadith === null) {
            throw new NotFoundHttpException;
        }

        return view('public.hadith.show', ['hadith' => $hadith->toArray()]);
    }
}
