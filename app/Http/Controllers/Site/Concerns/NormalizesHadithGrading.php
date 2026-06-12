<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site\Concerns;

/**
 * The hadith `grading_status` column stores free-text scholarly verdicts in both
 * Arabic and English (e.g. "Hasan Sahih", "صحيح", "Da'if Maqtu'", "Muttafaqun 'alayh").
 * The public UI needs a single colour-coded class per hadith, so we classify each raw
 * verdict into one of four buckets and expose a badge variant + display label.
 */
trait NormalizesHadithGrading
{
    /**
     * @return array{class: string, variant: string, label: string, raw: ?string}
     */
    private function gradingDescriptor(?string $raw): array
    {
        $class = $this->classifyGrading($raw);

        return [
            'class' => $class,
            'variant' => $this->gradingVariant($class),
            'label' => $this->gradingLabel($class, $raw),
            'raw' => $raw !== null && trim($raw) !== '' ? $raw : null,
        ];
    }

    /**
     * Classify a raw verdict into one of: sahih, hasan, daif, unknown.
     * Order matters: weak ("ضعيف"/"da'if") wins over a co-mentioned sahih because a
     * compound verdict that flags weakness should not be shown as fully authentic.
     */
    private function classifyGrading(?string $raw): string
    {
        if ($raw === null) {
            return 'unknown';
        }

        $value = mb_strtolower(trim($raw));
        if ($value === '') {
            return 'unknown';
        }

        // Weak markers (Arabic + transliterations) take precedence in compound verdicts.
        if ($this->containsAny($value, ['ضعيف', 'ضَعِيف', 'موضوع', 'مَوْضُوع', 'منكر', 'مُنكر', 'شاذ', 'لا أصل', 'لَا أصل'])
            || $this->containsAny($value, ["da'if", 'da`if', 'da if', 'da,if', 'da’if', 'daif', 'weak', 'maudu', 'mawdu', "mawdu'", 'munkar', 'shadh', 'rejected', 'fabricated'])) {
            return 'daif';
        }

        if ($this->containsAny($value, ['صحيح', 'صَحِيح', 'متفق', 'مُتَّفق', 'مُتَّفَق'])
            || $this->containsAny($value, ['sahih', 'sahīh', 'authentic', 'muttafaq', 'agreed upon', 'verified'])) {
            return 'sahih';
        }

        if ($this->containsAny($value, ['حسن'])
            || $this->containsAny($value, ['hasan', 'jayyid', 'جيد', 'جيّد', 'acceptable', 'good'])) {
            return 'hasan';
        }

        return 'unknown';
    }

    private function gradingVariant(string $class): string
    {
        return match ($class) {
            'sahih' => 'sahih',
            'hasan' => 'hasan',
            'daif' => 'daif',
            default => 'default',
        };
    }

    private function gradingLabel(string $class, ?string $raw): string
    {
        return match ($class) {
            'sahih' => __('hadith.public.grading_sahih'),
            'hasan' => __('hadith.public.grading_hasan'),
            'daif' => __('hadith.public.grading_daif'),
            default => __('hadith.public.grading_unknown'),
        };
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
