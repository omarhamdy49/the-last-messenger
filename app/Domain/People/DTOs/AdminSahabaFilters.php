<?php

declare(strict_types=1);

namespace App\Domain\People\DTOs;

use Illuminate\Http\Request;

/**
 * The filter object the existing admin Sahaba index view reads.
 *
 * Its shape is kept from the old `SahabiListFilters` so the designed view can
 * be used unmodified. `tribe`, `isVerified` and `categories` no longer have a
 * source column — they are always null/empty, and the view's own guards hide
 * those controls (Owner Decisions 4 and 6).
 */
final readonly class AdminSahabaFilters
{
    public function __construct(
        public ?string $search = null,
        public ?string $gender = null,
        public ?bool $isFeatured = null,
        /**
         * Publication status: null = every record, true = published only,
         * false = drafts only. Separate from `isFeatured` because a record can
         * be featured and unpublished at once — which is exactly the state an
         * editor needs to be able to find.
         */
        public ?bool $isPublished = null,
        /** Records hidden from the genealogy tree. null = don't filter. */
        public ?bool $hiddenFromTree = null,
        public ?bool $isVerified = null,
        public ?string $tribe = null,
        /** @var array<int, string> */
        public array $categories = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $gender = $request->query('gender');
        $status = $request->query('status');

        return new self(
            search: $request->filled('search') ? trim((string) $request->query('search')) : null,
            gender: in_array($gender, ['male', 'female'], true) ? $gender : null,
            isFeatured: $request->has('is_featured') ? $request->boolean('is_featured') : null,
            // An unknown value is dropped rather than guessed at, so a stale
            // bookmark shows the whole list instead of an empty one.
            isPublished: match ($status) {
                'published' => true,
                'draft' => false,
                default => null,
            },
            hiddenFromTree: $request->has('hidden_from_tree') ? $request->boolean('hidden_from_tree') : null,
        );
    }

    public function hasAny(): bool
    {
        return $this->search !== null
            || $this->gender !== null
            || $this->isFeatured !== null
            || $this->isPublished !== null
            || $this->hiddenFromTree !== null;
    }

    /** The `status=` query value this filter round-trips to. */
    public function statusValue(): string
    {
        return match ($this->isPublished) {
            true => 'published',
            false => 'draft',
            null => '',
        };
    }

    /**
     * Whether the advanced panel should open on load. `tribe` and `isVerified`
     * are retired, so what remains there is gender and featured — and if either
     * is applied the panel has to be open, or the list is filtered by a control
     * the editor cannot see.
     */
    public function hasAdvanced(): bool
    {
        return $this->gender !== null
            || $this->isFeatured !== null
            || $this->isPublished !== null
            || $this->hiddenFromTree !== null;
    }

    public function activeCount(): int
    {
        return count(array_filter([
            $this->search !== null,
            $this->gender !== null,
            $this->isFeatured !== null,
            $this->isPublished !== null,
            $this->hiddenFromTree !== null,
        ]));
    }
}
