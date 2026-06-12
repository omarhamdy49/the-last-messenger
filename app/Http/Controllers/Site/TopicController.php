<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Topics\Services\TopicService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Public Topics pages.
 *
 *  - index():  the topics listing (cards with real per-topic verse/hadith/article counts)
 *  - show():   the cross-module aggregation page for one theme (tabs: verses / hadiths / media)
 *
 * Mirrors the Site\SeerahController conventions: cached payloads of plain arrays,
 * Throwable-guarded, controller delegates to exactly one service.
 */
final class TopicController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService,
    ) {}

    /**
     * Topics listing page.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $topics = Cache::remember(
            "public_topics_index_{$locale}",
            1800,
            function () use ($locale): array {
                try {
                    return $this->topicService->listTopics($locale);
                } catch (Throwable $e) {
                    report($e);

                    return [];
                }
            },
        );

        return view('public.topics.index', [
            'topics' => $topics,
            'totalTopics' => count($topics),
        ]);
    }

    /**
     * Topic aggregation page — real verses + hadiths + articles for one theme.
     */
    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        $topic = Cache::remember(
            "public_topic_{$slug}_{$locale}",
            1800,
            function () use ($slug, $locale): ?array {
                try {
                    return $this->topicService->getTopicBySlug($slug, $locale);
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            },
        );

        if ($topic === null) {
            abort(404);
        }

        return view('public.topics.show', [
            'topic' => $topic,
        ]);
    }
}
