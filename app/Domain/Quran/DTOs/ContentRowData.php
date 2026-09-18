<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * One editor-written row of a translation, tafsir or chapter-info resource.
 * `target` is a verse key ("2:255") for translations/tafsirs, a chapter number for
 * chapter infos.
 */
final readonly class ContentRowData
{
    /**
     * @param  array<string, string>|null  $footNotes
     */
    public function __construct(
        public string|int $target,
        public string $text,
        public ?array $footNotes = null,
        public ?string $shortText = null,
        public ?string $source = null,
        public bool $isLocked = true,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $footNotes = null;
        if (isset($validated['foot_notes']) && is_array($validated['foot_notes'])) {
            $footNotes = [];
            foreach ($validated['foot_notes'] as $note) {
                if (is_array($note) && ($note['id'] ?? '') !== '' && ($note['text'] ?? '') !== '') {
                    $footNotes[(string) $note['id']] = (string) $note['text'];
                }
            }
            $footNotes = $footNotes === [] ? null : $footNotes;
        }

        return new self(
            target: $validated['verse_key'] ?? (int) ($validated['chapter_id'] ?? 0),
            text: (string) $validated['text'],
            footNotes: $footNotes,
            shortText: isset($validated['short_text']) && $validated['short_text'] !== '' ? (string) $validated['short_text'] : null,
            source: isset($validated['source']) && $validated['source'] !== '' ? (string) $validated['source'] : null,
            isLocked: (bool) ($validated['is_locked'] ?? true),
        );
    }
}
