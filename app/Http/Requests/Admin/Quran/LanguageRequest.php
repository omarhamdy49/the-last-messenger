<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use Illuminate\Validation\Rule;

final class LanguageRequest extends QuranAdminRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('iso_code'))) {
            $this->merge(['iso_code' => strtolower(trim($this->input('iso_code')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('language');

        return [
            'name' => ['required', 'string', 'max:60'],
            'native_name' => ['nullable', 'string', 'max:60'],
            'iso_code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', Rule::unique('quran_languages', 'iso_code')->ignore($id)],
            'direction' => ['required', 'in:ltr,rtl'],
            'is_active' => ['boolean'],
        ];
    }
}
