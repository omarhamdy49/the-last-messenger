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

        // Find the content model
        $contentClass = $request->input('interactable_type');
        $content = $contentClass::findOrFail($request->input('interactable_id'));

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
    public function getUserEngagement(int $userId): JsonResponse
    {
        $user = \App\Models\User::findOrFail($userId);
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
        $limit = (int) $request->query('limit', 50);

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
        $days = (int) $request->query('days', 30);

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
        // Map short names to full class names
        $typeMap = [
            'Article' => 'App\\Domain\\Blog\\Models\\Article',
            'Hadith' => 'App\\Domain\\Hadith\\Models\\Hadith',
            'MediaProgram' => 'App\\Domain\\Media\\Models\\MediaProgram',
            'MediaEpisode' => 'App\\Domain\\Media\\Models\\MediaEpisode',
            'SeerahEvent' => 'App\\Domain\\Seerah\\Models\\SeerahEvent',
            'Sahabi' => 'App\\Domain\\Sahaba\\Models\\Sahabi',
            'QuranVerse' => 'App\\Domain\\Quran\\Models\\QuranVerse',
            'QuranSurah' => 'App\\Domain\\Quran\\Models\\QuranSurah',
        ];

        if (! isset($typeMap[$type])) {
            return $this->errorResponse('Invalid content type', 400);
        }

        $contentClass = $typeMap[$type];
        $content = $contentClass::findOrFail($id);

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
        $limit = (int) $request->query('limit', 10);

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
        $limit = (int) $request->query('limit', 10);

        $topContent = $this->interactionService->getMostEngagedContent($limit);

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
}
