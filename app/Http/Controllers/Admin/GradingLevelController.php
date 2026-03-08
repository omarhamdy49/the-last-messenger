<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Enums\HadithGrade;
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
            ], 'تم جلب مستويات التصنيف بنجاح');
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
        if (!$gradeEnum) {
            abort(404, 'مستوى التصنيف غير موجود');
        }

        $gradeData = [
            'value' => $gradeEnum->value,
            'label_ar' => $gradeEnum->labelArabic(),
            'label_en' => $gradeEnum->label(),
            'is_reliable' => $gradeEnum->isReliable(),
        ];
        $type = 'hadith';

        if ($this->expectsJson($request)) {
            return $this->successResponse($gradeData, 'تم جلب مستوى التصنيف بنجاح');
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
        if (!$gradeEnum) {
            return $this->errorResponse('مستوى التصنيف غير موجود', 404);
        }

        $stats = [
            'grade' => $grade,
            'label_ar' => $gradeEnum->labelArabic(),
            'label_en' => $gradeEnum->label(),
            'hadith_count' => \App\Domain\Hadith\Models\Hadith::where('grade', $gradeEnum)->count(),
        ];

        return $this->successResponse($stats, 'تم جلب إحصائيات مستوى التصنيف بنجاح');
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
