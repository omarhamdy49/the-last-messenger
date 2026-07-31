<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Hadith\Services\NarratorProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class HadithNarratorController extends Controller
{
    public function __construct(
        private readonly NarratorProfileService $profiles,
    ) {}

    public function show(string $slug): View
    {
        $profile = $this->profiles->find($slug);

        if ($profile === null) {
            throw new NotFoundHttpException;
        }

        return view('public.hadith.narrator', ['profile' => $profile]);
    }
}
