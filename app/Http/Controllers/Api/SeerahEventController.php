<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Seerah\Services\SeerahEventService;
use App\Http\Resources\SeerahEventResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SeerahEventController extends Controller
{
    use ApiResponse;

    /**
     * SeerahEventController constructor
     */
    public function __construct(
        private readonly SeerahEventService $timelineEventService
    ) {}

    /**
     * Get all events chronologically
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $events = $this->timelineEventService->getAllEvents($perPage);

        return $this->successResponse(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get a single event by slug
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $event = $this->timelineEventService->getEventBySlug($slug);

            return $this->successResponse(
                new SeerahEventResource($event)
            );
        } catch (\Throwable $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Get events by type
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $perPage = $this->perPage($request);
        $events = $this->timelineEventService->getEventsByType($type, $perPage);

        return $this->successResponse(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get events by Hijri year
     */
    public function byHijriYear(Request $request, int $year): JsonResponse
    {
        $perPage = $this->perPage($request);
        $events = $this->timelineEventService->getEventsByHijriYear($year, $perPage);

        return $this->successResponse(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get events by Gregorian year
     */
    public function byGregorianYear(Request $request, int $year): JsonResponse
    {
        $perPage = $this->perPage($request);
        $events = $this->timelineEventService->getEventsByGregorianYear($year, $perPage);

        return $this->successResponse(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get featured events
     */
    public function featured(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $limit = (int) ($validated['limit'] ?? 10);
        $events = $this->timelineEventService->getFeaturedEvents($limit);

        return $this->successResponse(
            SeerahEventResource::collection($events)
        );
    }

    /**
     * Get events with location (for map view)
     */
    public function map(): JsonResponse
    {
        $events = $this->timelineEventService->getEventsWithLocation();

        return $this->successResponse(
            SeerahEventResource::collection($events)
        );
    }

    /**
     * Search events
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $query = $validated['q'];
        $perPage = (int) ($validated['per_page'] ?? 15);

        $events = $this->timelineEventService->searchEvents($query, $perPage);

        return $this->successResponse(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    private function perPage(Request $request): int
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return (int) ($validated['per_page'] ?? 15);
    }
}
