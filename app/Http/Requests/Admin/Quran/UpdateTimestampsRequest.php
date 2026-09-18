<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\DTOs\TimestampRowData;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\Verse;
use Illuminate\Validation\Validator;

/**
 * Verse timings for one chapter audio file, as JSON text or an uploaded JSON file:
 * `[{verse_key, from, to, segments?}]` in milliseconds. Every verse of the chapter
 * must appear exactly once, in order, with from < to.
 */
final class UpdateTimestampsRequest extends QuranAdminRequest
{
    /** @var list<TimestampRowData> */
    private array $parsed = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timestamps' => ['nullable', 'required_without:timestamps_file', 'string', 'max:5000000'],
            'timestamps_file' => ['nullable', 'file', 'max:20480', 'mimes:json,txt'],
        ];
    }

    /**
     * The verse keys of the route's chapter audio file, in order.
     *
     * @return list<string>
     */
    private function expectedKeys(): array
    {
        $chapter = ChapterAudioFile::query()
            ->where('resource_id', (int) $this->route('resource'))
            ->whereKey((int) $this->route('file'))
            ->value('chapter_id');

        return $chapter === null ? [] : Verse::query()->where('chapter_id', $chapter)->orderBy('verse_number')->pluck('verse_key')->all();
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $json = $this->file('timestamps_file') !== null
                ? (string) file_get_contents((string) $this->file('timestamps_file')->getRealPath())
                : (string) $this->input('timestamps');
            $items = json_decode($json, true);
            if (! is_array($items) || ! array_is_list($items)) {
                $v->errors()->add('timestamps', __('quran.manage.timestamps_invalid_json'));

                return;
            }

            $rows = [];
            foreach ($items as $i => $item) {
                if (! is_array($item) || ! is_string($item['verse_key'] ?? null) || ! is_numeric($item['from'] ?? null) || ! is_numeric($item['to'] ?? null)
                    || (int) $item['from'] >= (int) $item['to']) {
                    $v->errors()->add('timestamps', __('quran.manage.timestamps_invalid_row', ['row' => $i + 1]));

                    return;
                }
                $segments = isset($item['segments']) && is_array($item['segments']) ? array_values($item['segments']) : null;
                $rows[] = new TimestampRowData($item['verse_key'], (int) $item['from'], (int) $item['to'], $segments);
            }

            $keys = array_map(static fn (TimestampRowData $r): string => $r->verseKey, $rows);
            $expected = $this->expectedKeys();
            if ($keys !== $expected) {
                $missing = array_values(array_diff($expected, $keys));
                $v->errors()->add('timestamps', __('quran.manage.timestamps_incomplete', [
                    'missing' => implode(', ', array_slice($missing, 0, 10)) ?: '—',
                ]));

                return;
            }

            $this->parsed = $rows;
        });
    }

    /**
     * @return list<TimestampRowData>
     */
    public function rows(): array
    {
        return $this->parsed;
    }
}
