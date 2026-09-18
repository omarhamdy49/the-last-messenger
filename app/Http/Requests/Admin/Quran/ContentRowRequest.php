<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;

/**
 * A translation, tafsir or chapter-info row. The row family comes from the route
 * default `rows` (translations | tafsirs | chapter-infos); the target (verse key or
 * chapter) is only accepted on create.
 */
final class ContentRowRequest extends QuranAdminRequest
{
    use SanitizesRichText;

    public function rows(): string
    {
        return (string) $this->route('rows');
    }

    private function creating(): bool
    {
        return $this->route('row') === null;
    }

    protected function prepareForValidation(): void
    {
        $text = $this->input('text');
        if (! is_string($text)) {
            return;
        }

        $this->merge(['text' => $this->rows() === 'translations'
            // Translations keep only inline emphasis and QF footnote markers (<sup foot_note="…">).
            ? preg_replace_callback('/<sup\b([^>]*)>/i', static function (array $m): string {
                preg_match('/\bfoot_note\s*=\s*"?(\d+)"?/i', $m[1], $note);

                return isset($note[1]) ? '<sup foot_note="'.$note[1].'">' : '<sup>';
            }, strip_tags($text, '<sup><i><b><em><strong><br>'))
            : $this->sanitizeRichTextValue($text)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'text' => ['required', 'string', 'max:1000000'],
            'is_locked' => ['boolean'],
        ];

        if ($this->rows() === 'chapter-infos') {
            return $rules + [
                'chapter_id' => $this->creating() ? ['required', 'integer', 'exists:quran_chapters,id'] : ['prohibited'],
                'short_text' => ['nullable', 'string', 'max:2000'],
                'source' => ['nullable', 'string', 'max:255'],
            ];
        }

        $rules['verse_key'] = $this->creating() ? ['required', 'string', 'max:10', 'exists:quran_verses,verse_key'] : ['prohibited'];

        if ($this->rows() === 'translations') {
            $rules += [
                'foot_notes' => ['nullable', 'array', 'max:50'],
                'foot_notes.*.id' => ['nullable', 'string', 'max:20'],
                'foot_notes.*.text' => ['nullable', 'string', 'max:5000'],
            ];
        }

        return $rules;
    }
}
