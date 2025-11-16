<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Enums\NarratorGrade;
use App\Http\Requests\Admin\Hadith\StoreGradingLevelRequest;
use App\Http\Requests\Admin\Hadith\UpdateGradingLevelRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Grading Level Controller
 * Manages Hadith and Narrator grading levels (authenticity classifications)
 * Note: Uses Enums instead of database records for grading levels
 */
class GradingLevelController extends AdminController
{
    /**
     * Supported locales
     */
    protected array $locales;

    public function __construct()
    {
        parent::__construct();
        $this->locales = array_keys(config('app.supported_locales', ['ar', 'en']));
    }

    /**
     * Display a listing of grading levels
     * Returns both Hadith and Narrator grades
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view grading-levels');

        $type = $request->get('type', 'hadith'); // 'hadith' or 'narrator'

        // Get grades based on type
        if ($type === 'narrator') {
            $grades = $this->getNarratorGrades();
        } else {
            $grades = $this->getHadithGrades();
        }

        if ($this->expectsJson($request)) {
            return $this->successResponse([
                'type' => $type,
                'grades' => $grades,
            ], 'تم جلب مستويات التصنيف بنجاح');
        }

        return view('admin.hadith.grading-levels.index', compact('grades', 'type'));
    }

    /**
     * Show the form for creating a new grading level
     * Note: Since we use Enums, this is informational only
     */
    public function create(): View
    {
        $this->authorizeAdmin('create grading-levels');

        $locales = $this->locales;

        return view('admin.hadith.grading-levels.create', compact('locales'));
    }

    /**
     * Store a newly created grading level
     * Note: This is a placeholder - actual grades are defined in Enums
     */
    public function store(StoreGradingLevelRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create grading-levels');

        // Since grading levels are Enums, we can't create new ones via UI
        // This endpoint can be used for future expansion if grades become database-driven

        if ($this->expectsJson(request())) {
            return $this->errorResponse(
                'إنشاء مستويات تصنيف جديدة غير مدعوم حالياً. المستويات محددة في النظام.',
                422
            );
        }

        return back()->withErrors([
            'error' => 'إنشاء مستويات تصنيف جديدة غير مدعوم حالياً. المستويات محددة في النظام.'
        ]);
    }

    /**
     * Display the specified grading level
     */
    public function show(string $type, string $grade): View|JsonResponse
    {
        $this->authorizeAdmin('view grading-levels');

        $gradeData = $this->getGradeDetails($type, $grade);

        if (!$gradeData) {
            abort(404, 'مستوى التصنيف غير موجود');
        }

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                $gradeData,
                'تم جلب مستوى التصنيف بنجاح'
            );
        }

        return view('admin.hadith.grading-levels.show', compact('gradeData', 'type'));
    }

    /**
     * Show the form for editing the grading level
     * Note: Since we use Enums, this is informational only
     */
    public function edit(string $type, string $grade): View
    {
        $this->authorizeAdmin('edit grading-levels');

        $gradeData = $this->getGradeDetails($type, $grade);

        if (!$gradeData) {
            abort(404, 'مستوى التصنيف غير موجود');
        }

        $locales = $this->locales;

        return view('admin.hadith.grading-levels.edit', compact('gradeData', 'type', 'locales'));
    }

    /**
     * Update the specified grading level
     * Note: This is a placeholder - actual grades are defined in Enums
     */
    public function update(UpdateGradingLevelRequest $request, string $type, string $grade): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit grading-levels');

        // Since grading levels are Enums, we can't update them via UI
        // This endpoint can be used for future expansion if grades become database-driven

        if ($this->expectsJson(request())) {
            return $this->errorResponse(
                'تحديث مستويات التصنيف غير مدعوم حالياً. المستويات محددة في النظام.',
                422
            );
        }

        return back()->withErrors([
            'error' => 'تحديث مستويات التصنيف غير مدعوم حالياً. المستويات محددة في النظام.'
        ]);
    }

    /**
     * Remove the specified grading level
     * Note: This is a placeholder - actual grades are defined in Enums
     */
    public function destroy(string $type, string $grade): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete grading-levels');

        // Since grading levels are Enums, we can't delete them
        if ($this->expectsJson(request())) {
            return $this->errorResponse(
                'حذف مستويات التصنيف غير مدعوم. المستويات محددة في النظام.',
                422
            );
        }

        return back()->withErrors([
            'error' => 'حذف مستويات التصنيف غير مدعوم. المستويات محددة في النظام.'
        ]);
    }

    /**
     * Get statistics for a grading level
     */
    public function statistics(string $type, string $grade): JsonResponse
    {
        $this->authorizeAdmin('view grading-levels');

        $stats = [];

        if ($type === 'hadith') {
            $gradeEnum = HadithGrade::tryFrom($grade);
            if ($gradeEnum) {
                $stats = [
                    'grade' => $grade,
                    'label_ar' => $gradeEnum->labelArabic(),
                    'label_en' => $gradeEnum->label(),
                    'hadith_count' => \App\Domain\Hadith\Models\Hadith::where('grade', $gradeEnum)->count(),
                    'active_count' => \App\Domain\Hadith\Models\Hadith::where('grade', $gradeEnum)->active()->count(),
                ];
            }
        } elseif ($type === 'narrator') {
            $gradeEnum = NarratorGrade::tryFrom($grade);
            if ($gradeEnum) {
                $stats = [
                    'grade' => $grade,
                    'label_ar' => $gradeEnum->labelArabic(),
                    'label_en' => $gradeEnum->label(),
                    'narrator_count' => \App\Domain\Hadith\Models\HadithNarrator::where('reliability_grade', $gradeEnum)->count(),
                    'active_count' => \App\Domain\Hadith\Models\HadithNarrator::where('reliability_grade', $gradeEnum)->active()->count(),
                ];
            }
        }

        if (empty($stats)) {
            return $this->errorResponse('مستوى التصنيف غير موجود', 404);
        }

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

    /**
     * Get all narrator grades with details
     */
    protected function getNarratorGrades(): array
    {
        $grades = [];

        foreach (NarratorGrade::cases() as $grade) {
            $grades[] = [
                'value' => $grade->value,
                'label_ar' => $grade->labelArabic(),
                'label_en' => $grade->label(),
            ];
        }

        return $grades;
    }

    /**
     * Get details for a specific grade
     */
    protected function getGradeDetails(string $type, string $grade): ?array
    {
        if ($type === 'hadith') {
            $gradeEnum = HadithGrade::tryFrom($grade);
            if ($gradeEnum) {
                return [
                    'type' => 'hadith',
                    'value' => $gradeEnum->value,
                    'label_ar' => $gradeEnum->labelArabic(),
                    'label_en' => $gradeEnum->label(),
                    'is_reliable' => $gradeEnum->isReliable(),
                ];
            }
        } elseif ($type === 'narrator') {
            $gradeEnum = NarratorGrade::tryFrom($grade);
            if ($gradeEnum) {
                return [
                    'type' => 'narrator',
                    'value' => $gradeEnum->value,
                    'label_ar' => $gradeEnum->labelArabic(),
                    'label_en' => $gradeEnum->label(),
                ];
            }
        }

        return null;
    }
}
