<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use App\Domain\Hadith\Enums\Grade;

final class SunnahGradeMapper
{
    /** @return array{0: Grade|null, 1: string|null, 2: int} */
    public function map(SunnahHadithData $hadith): array
    {
        $rawClaims = $hadith->gradesEn !== [] ? $hadith->gradesEn : $hadith->gradesAr;
        $rawGrade = $rawClaims === [] ? null : implode('; ', $rawClaims);
        $mapped = [];
        $unmapped = 0;

        foreach (array_merge($hadith->gradesEn, $hadith->gradesAr) as $claim) {
            $grades = $this->gradesInClaim($claim);
            if ($grades === []) {
                $unmapped++;
            }
            foreach ($grades as $grade) {
                $mapped[$grade->value] = $grade;
            }
        }

        return [count($mapped) === 1 ? array_values($mapped)[0] : null, $rawGrade, $unmapped];
    }

    /** @return list<Grade> */
    private function gradesInClaim(string $claim): array
    {
        $folded = mb_strtolower($claim, 'UTF-8');
        $matches = [];
        $patterns = [
            Grade::Mawdu->value => '/\bmawdu[\x{2018}\x{2019}\x{0027}]?|fabricated|موضوع/u',
            Grade::Daif->value => '/\bda[\x{2018}\x{2019}\x{0027}`]?if\b|\bdaeef\b|\bweak\b|ضعيف/u',
            Grade::Hasan->value => '/\bhasan\b|حسن/u',
            Grade::Sahih->value => '/\bsahih\b|صحيح/u',
        ];

        foreach ($patterns as $value => $pattern) {
            if (preg_match($pattern, $folded) === 1) {
                $matches[] = Grade::from($value);
            }
        }

        return $matches;
    }
}
