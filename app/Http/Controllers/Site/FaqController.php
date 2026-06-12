<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Content\Services\FaqService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class FaqController extends Controller
{
    public function __construct(
        private readonly FaqService $faqService,
    ) {}

    public function index(): View
    {
        // Group the (cached) flat list by category, preserving display order.
        $faqGroups = [];

        foreach ($this->faqService->active() as $faq) {
            $faqGroups[$faq['category'] ?? 'general'][] = $faq;
        }

        return view('public.faq.index', ['faqGroups' => $faqGroups]);
    }
}
