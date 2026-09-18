<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\Models\Verse;

final class UpdateVerseRequest extends QuranAdminRequest
{
    /** Script columns an editor may correct (glyph codes stay import-only). */
    public const SCRIPT_FIELDS = [
        'text_uthmani', 'text_uthmani_simple', 'text_uthmani_tajweed', 'text_imlaei', 'text_imlaei_simple',
        'text_indopak', 'text_indopak_nastaleeq', 'text_qpc_hafs', 'text_qpc_nastaleeq',
    ];

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (self::SCRIPT_FIELDS as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }
            $clean[$field] = $field === 'text_uthmani_tajweed' ? Verse::sanitizeTajweed($value) : strip_tags($value);
        }
        $this->merge($clean);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = array_fill_keys(self::SCRIPT_FIELDS, ['nullable', 'string', 'max:10000']);

        return $rules + [
            'sajdah_type' => ['nullable', 'in:recommended,obligatory'],
            'sajdah_number' => ['nullable', 'integer', 'min:1', 'max:15'],
            'is_locked' => ['sometimes', 'boolean'],
        ];
    }
}
