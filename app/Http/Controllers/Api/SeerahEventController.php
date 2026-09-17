<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Resources\SeerahEventResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

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
     * Get events chronologically, optionally filtered by type, milestone or era
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $filters = $request->validate([
            'type' => ['sometimes', 'string', 'max:100'],
            'milestone' => ['sometimes', 'string', 'max:255'],
            'era' => ['sometimes', Rule::enum(EventEra::class)],
        ]);
        $events = $this->timelineEventService->paginatePublicFiltered(
            $filters + ['sort' => 'asc'],
            app()->getLocale(),
            $perPage,
        );

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
        } catch (ResourceNotFoundException) {
            return $this->notFoundResponse('Event not found');
        } catch (\Throwable $e) {
            // Only a domain 404 becomes a 404; everything else is an internal
            // fault and must not surface its message to the client.
            report($e);

            return $this->serverErrorResponse();
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
