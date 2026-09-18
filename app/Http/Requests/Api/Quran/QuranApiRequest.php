<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Quran;

use App\Domain\Quran\DTOs\VerseApiParams;
use App\Domain\Quran\Services\QuranApiService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Query parameters shared by the QF-shaped Quran API: verse params, axis filters,
 * pagination and search. Unknown csv ids or fields are a 422, not silently ignored.
 */
final class QuranApiRequest extends FormRequest
{
    /** QF axis query keys => quran_verses columns. */
    private const AXES = [
        'chapter_number' => 'chapter_id', 'juz_number' => 'juz_number', 'hizb_number' => 'hizb_number',
        'rub_el_hizb_number' => 'rub_el_hizb_number', 'manzil_number' => 'manzil_number',
        'ruku_number' => 'ruku_number', 'page_number' => 'page_number', 'verse_key' => 'verse_key',
    ];

    public function authorize(): bool
    {
        return true; // api.client + api.scope:quran guard the routes
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2,3}(-[a-z0-9]+)?$/i'],
            'words' => ['nullable', 'in:true,false,1,0'],
            'translations' => ['nullable', 'string', 'max:200', 'regex:/^\d+(,\d+)*$/'],
            'tafsirs' => ['nullable', 'string', 'max:200', 'regex:/^\d+(,\d+)*$/'],
            'audio' => ['nullable', 'integer', 'min:1'],
            'fields' => ['nullable', 'string', 'max:500'],
            'word_fields' => ['nullable', 'string', 'max:500'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'mushaf' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'string', 'max:10', 'regex:/^\d{1,4}(:\d{1,3})?$/'],
            'to' => ['nullable', 'string', 'max:10', 'regex:/^\d{1,4}(:\d{1,3})?$/'],
            'chapter_number' => ['nullable', 'integer', 'between:1,114'],
            'juz_number' => ['nullable', 'integer', 'between:1,30'],
            'hizb_number' => ['nullable', 'integer', 'between:1,60'],
            'rub_el_hizb_number' => ['nullable', 'integer', 'between:1,240'],
            'manzil_number' => ['nullable', 'integer', 'between:1,7'],
            'ruku_number' => ['nullable', 'integer', 'between:1,558'],
            'page_number' => ['nullable', 'integer', 'between:1,1000'],
            'verse_key' => ['nullable', 'string', 'regex:/^\d{1,3}:\d{1,3}$/'],
            'foot_notes' => ['nullable', 'in:true,false,1,0'],
            'resource_id' => ['nullable', 'integer', 'min:1'],
            'include_resources' => ['nullable', 'in:true,false,1,0'],
            'segments' => ['nullable', 'in:true,false,1,0'],
            'word' => ['nullable', 'integer', 'min:1'],
            'word_from' => ['nullable', 'integer', 'min:1'],
            'word_to' => ['nullable', 'integer', 'min:1'],
            'timestamp' => ['nullable', 'integer', 'min:0'],
            'mode' => ['nullable', 'in:quick,advanced'],
            'query' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
            'translation_ids' => ['nullable', 'string', 'max:200', 'regex:/^\d+(,\d+)*$/'],
            'exact_matches_only' => ['nullable', 'in:true,false,1,0'],
            'highlight' => ['nullable', 'in:true,false,1,0'],
            'navigationalResultsNumber' => ['nullable', 'integer', 'min:0', 'max:20'],
            'versesResultsNumber' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            foreach (['fields' => QuranApiService::VERSE_FIELDS, 'word_fields' => QuranApiService::WORD_FIELDS] as $key => $allowed) {
                $unknown = array_diff($this->csv($key), $allowed);
                if ($unknown !== []) {
                    $v->errors()->add($key, 'Unknown '.$key.': '.implode(', ', $unknown));
                }
            }
        });
    }

    public function params(): VerseApiParams
    {
        return new VerseApiParams(
            language: $this->filled('language') ? strtolower((string) $this->query('language')) : null,
            words: $this->boolean('words'),
            translationIds: array_map('intval', $this->csv('translations')),
            tafsirIds: array_map('intval', $this->csv('tafsirs')),
            audioId: $this->filled('audio') ? $this->integer('audio') : null,
            fields: $this->csv('fields'),
            wordFields: $this->csv('word_fields'),
            page: max(1, $this->integer('page', 1)),
            perPage: $this->integer('per_page', 10),
        );
    }

    /**
     * The QF axis filter in quran_verses columns (only keys that were sent).
     *
     * @return array<string, int|string>
     */
    public function axisFilter(): array
    {
        $filter = [];
        foreach (self::AXES as $param => $column) {
            if ($this->filled($param)) {
                $filter[$column] = $param === 'verse_key' ? (string) $this->query($param) : $this->integer($param);
            }
        }

        return $filter;
    }

    /**
     * @return list<string>
     */
    public function csv(string $key): array
    {
        $value = trim((string) $this->query($key, ''));

        return $value === '' ? [] : array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
    }
}
