<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * A verse text script. The case value is the quran_verses column; `qfPath()` is the
 * `{script}` segment of QF `/quran/verses/{script}`.
 */
enum Script: string
{
    case UTHMANI = 'text_uthmani';
    case UTHMANI_SIMPLE = 'text_uthmani_simple';
    case UTHMANI_TAJWEED = 'text_uthmani_tajweed';
    case IMLAEI = 'text_imlaei';
    case IMLAEI_SIMPLE = 'text_imlaei_simple';
    case INDOPAK = 'text_indopak';
    case INDOPAK_NASTALEEQ = 'text_indopak_nastaleeq';
    case QPC_HAFS = 'text_qpc_hafs';
    case QPC_NASTALEEQ = 'text_qpc_nastaleeq';
    case CODE_V1 = 'code_v1';
    case CODE_V2 = 'code_v2';

    public function qfPath(): string
    {
        return $this->isGlyph() ? $this->value : substr($this->value, 5);
    }

    /**
     * Accepts either the column form (`text_uthmani`) or the QF path form (`uthmani`).
     */
    public static function fromExternal(string $value): ?self
    {
        $value = strtolower(trim($value));

        return self::tryFrom($value) ?? self::tryFrom('text_'.$value) ?? match ($value) {
            'v1' => self::CODE_V1,
            'v2' => self::CODE_V2,
            default => null,
        };
    }

    /**
     * QCF glyph codes need the per-page mushaf font to render.
     */
    public function isGlyph(): bool
    {
        return $this === self::CODE_V1 || $this === self::CODE_V2;
    }

    /**
     * Text a reader can pick directly (glyph scripts are chosen through a mushaf).
     */
    public function isSelectable(): bool
    {
        return ! $this->isGlyph();
    }

    public function label(?string $locale = null): string
    {
        return (string) __('quran.scripts.'.$this->qfPath(), [], $locale);
    }

    public static function default(): self
    {
        return self::fromExternal((string) config('quran.defaults.script', 'text_uthmani')) ?? self::UTHMANI;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
