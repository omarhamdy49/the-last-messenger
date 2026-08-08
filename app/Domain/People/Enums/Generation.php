<?php

declare(strict_types=1);

namespace App\Domain\People\Enums;

/**
 * The طبقة a person belongs to — the standard generational layers of the early
 * community, in order.
 *
 * Slug-backed like IslamicRole and Gender, so the stored value does not depend
 * on the display locale.
 *
 * Deliberately NOT cast on the Muslim model. `generation` stays a plain string
 * column so a value arriving from an import that predates this enum still reads
 * back instead of throwing; callers resolve a label with tryFrom() and fall
 * back to the raw string.
 */
enum Generation: string
{
    case Prophet = 'prophet';
    case Companion = 'companion';
    case Successor = 'successor';
    case SuccessorOfSuccessors = 'successor_of_successors';
    case Later = 'later';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Prophet => $locale === 'ar' ? 'النبي ﷺ' : 'The Prophet ﷺ',
            self::Companion => $locale === 'ar' ? 'صحابي' : 'Companion',
            self::Successor => $locale === 'ar' ? 'تابعي' : 'Successor (Tabi\'i)',
            self::SuccessorOfSuccessors => $locale === 'ar' ? 'تابع التابعين' : 'Successor of the Successors',
            self::Later => $locale === 'ar' ? 'من بعدهم' : 'Later generations',
        };
    }

    /** @return array<string, string> */
    public static function options(string $locale = 'ar'): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label($locale);
        }

        return $options;
    }

    /**
     * Label for a stored value, falling back to the value itself so a legacy or
     * imported string is shown rather than swallowed.
     */
    public static function labelFor(?string $value, string $locale = 'ar'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value)?->label($locale) ?? $value;
    }
}
