<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exceptions;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;

/**
 * Invalid Grading Exception
 *
 * Thrown when an invalid hadith grading is attempted.
 */
class InvalidGradingException extends BusinessRuleViolationException
{
    /**
     * Create for invalid grading status
     */
    public static function invalidStatus(string $status): static
    {
        $validStatuses = implode(', ', Hadith::getGradingStatuses());

        return static::forRule(
            ruleName: 'InvalidGradingStatus',
            message: "درجة الحديث '{$status}' غير صالحة. الدرجات المتاحة: {$validStatuses} | Invalid grading status '{$status}'. Valid statuses: {$validStatuses}",
            context: [
                'provided_status' => $status,
                'valid_statuses' => Hadith::getGradingStatuses(),
            ]
        );
    }

    /**
     * Create for conflicting gradings
     */
    public static function conflictingGradings(int $hadithId, array $gradings): static
    {
        return static::forRule(
            ruleName: 'ConflictingGradings',
            message: "توجد درجات متعارضة للحديث رقم {$hadithId} | Conflicting gradings exist for hadith {$hadithId}",
            context: [
                'hadith_id' => $hadithId,
                'gradings' => $gradings,
            ]
        );
    }

    /**
     * Create for missing scholar
     */
    public static function missingScholar(): static
    {
        return static::forRule(
            ruleName: 'MissingScholar',
            message: 'يجب تحديد العالم الذي قام بتصنيف الحديث | A scholar must be specified for the grading',
            context: []
        );
    }
}
