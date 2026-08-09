<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Enums\InteractionType;
use App\Domain\Shared\Services\InteractionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInteractionRequest;
use App\Http\Resources\UserEngagementMetricResource;
use App\Http\Resources\UserInteractionResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Interaction Controller
 * Handles API endpoints for user interaction tracking
 */
class InteractionController extends Controller
{
    use ApiResponse;

    public function __construct(protected InteractionService $interactionService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Record a new interaction
     *
     * POST /api/interactions
     * Body: {
     *   "interactable_type": "Article|Hadith|MediaProgram|etc",
     *   "interactable_id": 1,
     *   "interaction_type": "view|like|share|bookmark|etc",
     *   "metadata": {},
     *   "duration_seconds": 120
     * }
     */
    public function store(StoreInteractionRequest $request): JsonResponse
    {
        $user = $request->user();

        // Resolve through a closed allowlist and publication gate. This avoids
        // dynamic-class execution and prevents draft existence probing.
        $content = $this->visibleContent(
            (string) $request->validated('interactable_type'),
            (int) $request->validated('interactable_id'),
        );

        // Record interaction
        $interaction = $this->interactionService->recordInteraction(
            user: $user,
            content: $content,
            type: InteractionType::from($request->input('interaction_type')),
            metadata: $request->input('metadata'),
            durationSeconds: $request->input('duration_seconds')
        );

        return $this->createdResponse(
            new UserInteractionResource($interaction),
            'Interaction recorded successfully'
        );
    }

    /**
     * Get authenticated user's engagement metrics
     *
     * GET /api/engagement/me
     */
    public function getMyEngagement(Request $request): JsonResponse
    {
        $user = $request->user();
        $history = $this->interactionService->getUserEngagementHistory($user);

        return $this->successResponse($history, 'Engagement metrics retrieved successfully');
    }

    /**
     * Get user's engagement metrics (by ID)
     *
     * GET /api/engagement/users/{id}
     */
    public function getUserEngagement(Request $request, int $id): JsonResponse
    {
        if ((int) $request->user()->id !== $id) {
            return $this->forbiddenResponse('You may only view your own engagement data.');
        }

        $user = \App\Models\User::findOrFail($id);
        $history = $this->interactionService->getUserEngagementHistory($user);

        return $this->successResponse($history, 'User engagement retrieved successfully');
    }

    /**
     * Get user's recent activity
     *
     * GET /api/interactions/me?limit=50
     */
    public function getMyActivity(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min(max((int) $request->query('limit', 50), 1), 100);

        $interactions = $this->interactionService->getUserActivity($user, $limit);

        return $this->successResponse(
            UserInteractionResource::collection($interactions),
            'Activity retrieved successfully'
        );
    }

    /**
     * Get engagement trends for authenticated user
     *
     * GET /api/engagement/trends?days=30
     */
    public function getMyTrends(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = min(max((int) $request->query('days', 30), 1), 365);

        $trends = $this->interactionService->getEngagementTrends($user, $days);

        return $this->successResponse($trends, 'Engagement trends retrieved successfully');
    }

    /**
     * Get content engagement statistics
     *
     * GET /api/engagement/content/{type}/{id}
     * Example: /api/engagement/content/Article/123
     */
    public function getContentEngagement(string $type, int $id): JsonResponse
    {
        if (! isset(StoreInteractionRequest::CONTENT_TYPES[$type])) {
            return $this->errorResponse('Invalid content type', 400);
        }

        $content = $this->visibleContent($type, $id);

        $engagement = $this->interactionService->getContentEngagement($content);

        return $this->successResponse($engagement, 'Content engagement retrieved successfully');
    }

    /**
     * Get engagement leaderboard (most engaged users)
     *
     * GET /api/engagement/leaderboard?limit=10
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);

        $leaderboard = $this->interactionService->getMostEngagedUsers($limit);

        return $this->successResponse(
            UserEngagementMetricResource::collection($leaderboard),
            'Leaderboard retrieved successfully'
        );
    }

    /**
     * Get most engaged content
     *
     * GET /api/engagement/content/top?limit=10
     */
    public function getTopContent(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 10), 1), 100);

        $topContent = collect($this->interactionService->getMostEngagedContent($limit))
            ->map(function (array $item): ?array {
                $type = array_search($item['interactable_type'], StoreInteractionRequest::CONTENT_TYPES, true);

                if (! is_string($type)) {
                    return null;
                }

                try {
                    $this->visibleContent($type, (int) $item['interactable_id']);
                } catch (ModelNotFoundException) {
                    return null;
                }

                return [
                    'type' => $type,
                    'id' => (int) $item['interactable_id'],
                    'interaction_count' => (int) $item['interaction_count'],
                ];
            })
            ->filter()
            ->values();

        return $this->successResponse($topContent, 'Top content retrieved successfully');
    }

    /**
     * Get platform-wide engagement statistics
     *
     * GET /api/engagement/stats
     */
    public function getPlatformStats(): JsonResponse
    {
        $stats = $this->interactionService->getPlatformEngagementStats();

        return $this->successResponse($stats, 'Platform statistics retrieved successfully');
    }

    /**
     * Export user's engagement data
     *
     * GET /api/engagement/export
     */
    public function exportMyData(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->interactionService->exportUserEngagementData($user);

        return $this->successResponse($data, 'Engagement data exported successfully');
    }

    private function visibleContent(string $type, int $id): Model
    {
        $class = StoreInteractionRequest::CONTENT_TYPES[$type];
        $query = $class::query();

        match ($type) {
            'Article' => $query->where('status', \App\Models\Article::STATUS_PUBLISHED),
            'Hadith' => $query->publiclyVisible(),
            'MediaProgram' => $query->published(),
            'MediaEpisode' => $query->published()
                ->whereHas('program', fn ($program) => $program->published()),
            'SeerahEvent' => $query->published(),
            'Sahabi' => $query->published(),
            'QuranVerse', 'QuranSurah' => null,
        };

        return $query->findOrFail($id);
    }
}
