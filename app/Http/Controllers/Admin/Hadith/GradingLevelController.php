<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Grading Level Controller
 * Manages Hadith grading levels (authenticity classifications)
 * Uses Enums instead of database records
 */
class GradingLevelController extends AdminController
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of grading levels
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view grading-levels');

        $grades = $this->getHadithGrades();
        $type = 'hadith';

        if ($this->expectsJson($request)) {
            return $this->successResponse([
                'type' => $type,
                'grades' => $grades,
            ], __('admin.grading_levels_fetched'));
        }

        return view('admin.hadith.grading-levels.index', compact('grades', 'type'));
    }

    /**
     * Display the specified grading level
     */
    public function show(Request $request, string $grade): View|JsonResponse
    {
        $this->authorizeAdmin('view grading-levels');

        $gradeEnum = HadithGrade::tryFrom($grade);
        if (! $gradeEnum) {
            abort(404, __('admin.grading_level_not_found'));
        }

        $gradeData = [
            'value' => $gradeEnum->value,
            'label_ar' => $gradeEnum->labelArabic(),
            'label_en' => $gradeEnum->label(),
            'is_reliable' => $gradeEnum->isReliable(),
        ];
        $type = 'hadith';

        if ($this->expectsJson($request)) {
            return $this->successResponse($gradeData, __('admin.grading_level_fetched'));
        }

        return view('admin.hadith.grading-levels.show', compact('gradeData', 'type'));
    }

    /**
     * Get statistics for a grading level
     */
    public function statistics(string $grade): JsonResponse
    {
        $this->authorizeAdmin('view grading-levels');

        $gradeEnum = HadithGrade::tryFrom($grade);
        if (! $gradeEnum) {
            return $this->errorResponse(__('admin.grading_level_not_found'), 404);
        }

        $stats = [
            'grade' => $grade,
            'label_ar' => $gradeEnum->labelArabic(),
            'label_en' => $gradeEnum->label(),
            'hadith_count' => $this->hadithRepository->countByGrading($gradeEnum->value),
        ];

        return $this->successResponse($stats, __('admin.grading_level_statistics_fetched'));
    }

    /**
     * Get all hadith grades with details
     */
    protected function getHadithGrades(): array
    {
        $grades = [];

        foreach (HadithGrade::cases() as $grade) {
            $grades[] = [
                'value' => $grade->value,
                'label_ar' => $grade->labelArabic(),
                'label_en' => $grade->label(),
                'is_reliable' => $grade->isReliable(),
            ];
        }

        return $grades;
    }
}
