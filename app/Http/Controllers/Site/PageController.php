<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\Services\StaticPageService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    public function __construct(
        private readonly StaticPageService $staticPageService,
    ) {}

    /**
     * Render a published static page (about / privacy / terms / …) by slug.
     * Unknown, inactive, or unpublished slugs 404.
     */
    public function show(string $slug): View
    {
        $page = $this->staticPageService->getBySlug($slug);

        abort_if($page === null, 404);

        return view('public.pages.show', ['page' => $page]);
    }
}
