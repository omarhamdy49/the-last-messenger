<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

/**
 * Reconciled counts for one import/sync step (constitution X: every skip and failure
 * is itemised, nothing is averaged away).
 *
 * Mutable by design: an importer accumulates into it while it streams batches.
 */
final class ImportReport
{
    public int $expected = 0;

    public int $written = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $skippedLocked = 0;

    public int $deleted = 0;

    /** @var list<array{key: string, reason: string}> */
    public array $failed = [];

    /** @var list<array{message: string, passed: bool}> */
    public array $gates = [];

    /** @var list<string> */
    public array $notes = [];

    public function __construct(public readonly string $step) {}

    /**
     * @param  array{written?: int, updated?: int, unchanged?: int, skipped_locked?: int, deleted?: int}  $counts
     */
    public function add(array $counts): void
    {
        $this->written += $counts['written'] ?? 0;
        $this->updated += $counts['updated'] ?? 0;
        $this->unchanged += $counts['unchanged'] ?? 0;
        $this->skippedLocked += $counts['skipped_locked'] ?? 0;
        $this->deleted += $counts['deleted'] ?? 0;
    }

    public function fail(string $key, string $reason): void
    {
        $this->failed[] = ['key' => $key, 'reason' => $reason];
    }

    public function note(string $note): void
    {
        $this->notes[] = $note;
    }

    /**
     * Record a parity check. A failed gate fails the step.
     */
    public function gate(bool $passed, string $message): bool
    {
        $this->gates[] = ['message' => $message, 'passed' => $passed];

        return $passed;
    }

    public function passed(): bool
    {
        if ($this->failed !== []) {
            return false;
        }

        foreach ($this->gates as $gate) {
            if (! $gate['passed']) {
                return false;
            }
        }

        return true;
    }

    public function merge(self $other): void
    {
        $this->expected += $other->expected;
        $this->written += $other->written;
        $this->updated += $other->updated;
        $this->unchanged += $other->unchanged;
        $this->skippedLocked += $other->skippedLocked;
        $this->deleted += $other->deleted;
        array_push($this->failed, ...$other->failed);
        array_push($this->gates, ...$other->gates);
        array_push($this->notes, ...$other->notes);
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    public function countsTable(): array
    {
        return [
            ['expected', $this->expected],
            ['written (new)', $this->written],
            ['updated', $this->updated],
            ['unchanged', $this->unchanged],
            ['skipped (locked)', $this->skippedLocked],
            ['deleted', $this->deleted],
            ['failed', count($this->failed)],
        ];
    }

    /**
     * @return array{step: string, expected: int, written: int, updated: int, unchanged: int, skipped_locked: int, deleted: int, failed: list<array{key: string, reason: string}>, gates: list<array{message: string, passed: bool}>, notes: list<string>, passed: bool}
     */
    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'expected' => $this->expected,
            'written' => $this->written,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'skipped_locked' => $this->skippedLocked,
            'deleted' => $this->deleted,
            'failed' => $this->failed,
            'gates' => $this->gates,
            'notes' => $this->notes,
            'passed' => $this->passed(),
        ];
    }
}
