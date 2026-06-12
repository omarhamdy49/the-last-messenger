<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Services\FavoriteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreFavoriteRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * Authenticated visitor bookmarks (favorites).
 *
 * Every action is scoped to the acting user inside the service/repository
 * (user_id predicate), which is the IDOR boundary: a user can only ever list,
 * add to, or remove from their own bookmarks. Guests are rejected by the
 * `auth` middleware before reaching here.
 */
final class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favorites,
    ) {}

    /**
     * List the user's bookmarks, filterable by content type, paginated.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $locale = App::getLocale();

        $type = $this->resolveType((string) $request->query('type', 'all'));

        $perPage = (int) $request->query('per_page', 12);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : 12;

        $counts = $this->safeCounts($user);

        try {
            $paginator = $this->favorites->paginateForUser($user, $type, $perPage, $locale);
        } catch (Throwable $e) {
            report($e);
            $paginator = $this->favorites->paginateForUser($user, $type, $perPage, $locale);
        }

        $filterPills = $this->buildFilterPills($request, $type, $counts);

        return view('public.favorites.index', [
            'paginator' => $paginator,
            'bookmarks' => $paginator->getCollection(),
            'counts' => $counts,
            'activeType' => $type?->value ?? 'all',
            'filterPills' => $filterPills,
        ]);
    }

    /**
     * Add a bookmark for the authenticated user (idempotent).
     */
    public function store(StoreFavoriteRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        try {
            $created = $this->favorites->add($user, $request->favorableType(), $request->contentId());
        } catch (EntityNotFoundException) {
            return $this->respond($request, false, __('profile.bookmark_content_missing'), 404, false);
        } catch (Throwable $e) {
            report($e);

            return $this->respond($request, false, __('profile.bookmark_failed'), 500, false);
        }

        return $this->respond(
            $request,
            true,
            $created ? __('profile.bookmark_added') : __('profile.bookmark_exists'),
            $created ? 201 : 200,
            true,
        );
    }

    /**
     * Remove a bookmark for the authenticated user.
     */
    public function destroy(StoreFavoriteRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        try {
            $removed = $this->favorites->remove($user, $request->favorableType(), $request->contentId());
        } catch (Throwable $e) {
            report($e);

            return $this->respond($request, false, __('profile.bookmark_failed'), 500, true);
        }

        return $this->respond(
            $request,
            true,
            $removed ? __('profile.bookmark_removed') : __('profile.bookmark_not_found'),
            200,
            false,
        );
    }

    /**
     * Build a JSON response for the Alpine bookmark button, or fall back to a
     * redirect-with-toast for non-XHR form submissions.
     */
    private function respond(
        Request $request,
        bool $success,
        string $message,
        int $status,
        bool $favorited,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'favorited' => $favorited,
                'message' => $message,
            ], $status);
        }

        return back()->with($success ? 'toast' : 'error', $message);
    }

    /**
     * Map the `type` query slug to an enum, or null for "all".
     */
    private function resolveType(string $raw): ?FavorableType
    {
        if ($raw === '' || $raw === 'all') {
            return null;
        }

        return FavorableType::tryFrom($raw);
    }

    /**
     * @return array<string, int>
     */
    private function safeCounts($user): array
    {
        try {
            return $this->favorites->countsForUser($user);
        } catch (Throwable $e) {
            report($e);

            $counts = ['all' => 0];
            foreach (FavorableType::cases() as $type) {
                $counts[$type->value] = 0;
            }

            return $counts;
        }
    }

    /**
     * Build content-type filter pills with counts and query-preserving URLs.
     *
     * @param  array<string, int>  $counts
     * @return list<array<string, mixed>>
     */
    private function buildFilterPills(Request $request, ?FavorableType $active, array $counts): array
    {
        $make = fn (string $value): string => $request->fullUrlWithQuery([
            'type' => $value,
            'page' => 1,
        ]);

        $pills = [[
            'value' => 'all',
            'label' => __('profile.filter_all'),
            'icon' => 'layout-grid',
            'count' => $counts['all'] ?? 0,
            'url' => $make('all'),
        ]];

        foreach (FavorableType::cases() as $type) {
            $pills[] = [
                'value' => $type->value,
                'label' => __($type->labelKey()),
                'icon' => $type->icon(),
                'count' => $counts[$type->value] ?? 0,
                'url' => $make($type->value),
            ];
        }

        return $pills;
    }
}
