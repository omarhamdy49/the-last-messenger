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
        $perPage = $request->integer('per_page', 15);
        $events = $this->timelineEventService->getAllEvents($perPage);

        return $this->success(
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

            return $this->success(
                new SeerahEventResource($event)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get events by type
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $events = $this->timelineEventService->getEventsByType($type, $perPage);

        return $this->success(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get events by Hijri year
     */
    public function byHijriYear(Request $request, int $year): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $events = $this->timelineEventService->getEventsByHijriYear($year, $perPage);

        return $this->success(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get events by Gregorian year
     */
    public function byGregorianYear(Request $request, int $year): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $events = $this->timelineEventService->getEventsByGregorianYear($year, $perPage);

        return $this->success(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }

    /**
     * Get featured events
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $events = $this->timelineEventService->getFeaturedEvents($limit);

        return $this->success(
            SeerahEventResource::collection($events)
        );
    }

    /**
     * Get events with location (for map view)
     */
    public function map(): JsonResponse
    {
        $events = $this->timelineEventService->getEventsWithLocation();

        return $this->success(
            SeerahEventResource::collection($events)
        );
    }

    /**
     * Search events
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $perPage = $request->integer('per_page', 15);

        if (empty($query)) {
            return $this->error('Search query is required', 400);
        }

        $events = $this->timelineEventService->searchEvents($query, $perPage);

        return $this->success(
            SeerahEventResource::collection($events)->response()->getData(true)
        );
    }
}
