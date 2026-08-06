<?php

declare(strict_types=1);

namespace App\Domain\People\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    public function label(string $locale = 'ar'): string
    {
        return match ($this) {
            self::Male => $locale === 'ar' ? 'ذكر' : 'Male',
            self::Female => $locale === 'ar' ? 'أنثى' : 'Female',
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
}
