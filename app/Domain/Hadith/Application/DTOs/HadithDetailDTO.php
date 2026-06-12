<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\Hadith;
use JsonSerializable;

/**
 * Hadith Detail Data Transfer Object
 *
 * Full public-detail shape for a hadith: the base HadithDTO plus the isnad
 * (chain of narrators), scholarly commentaries, per-scholar gradings, and the
 * number of parallel narrations (other hadiths sharing the same text hash).
 *
 * All relation data is mapped from eager-loaded relations — callers must load
 * isnads.narrator, commentaries.scholar and gradings.scholar/gradingLevel
 * before constructing this DTO (see HadithRepository::getWithPublicDetail()).
 */
final readonly class HadithDetailDTO implements JsonSerializable
{
    /**
     * @param  array<int, array<string, mixed>>  $isnad
     * @param  array<int, array<string, mixed>>  $commentaries
     * @param  array<int, array<string, mixed>>  $scholarGradings
     */
    public function __construct(
        public HadithDTO $hadith,
        public array $isnad = [],
        public array $commentaries = [],
        public array $scholarGradings = [],
        public int $parallelsCount = 0,
    ) {}

    /**
     * Create from a Hadith model with public-detail relations eager loaded.
     */
    public static function fromModel(Hadith $hadith, string $locale = 'ar', int $parallelsCount = 0): self
    {
        return new self(
            hadith: HadithDTO::fromModel($hadith, $locale),
            isnad: self::mapIsnad($hadith, $locale),
            commentaries: self::mapCommentaries($hadith, $locale),
            scholarGradings: self::mapScholarGradings($hadith, $locale),
            parallelsCount: $parallelsCount,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function mapIsnad(Hadith $hadith, string $locale): array
    {
        return $hadith->isnads
            ->sortBy('position_in_chain')
            ->values()
            ->map(fn ($link) => [
                'id' => $link->narrator?->id,
                'position' => $link->position_in_chain,
                'transmissionMethod' => $link->transmission_method,
                'name' => self::localizedName($link->narrator?->name_ar, $link->narrator?->name_en, $locale),
                'kunya' => $link->narrator?->kunya,
                'reliability' => $link->narrator?->reliability_grade,
                'deathYearHijri' => $link->narrator?->death_year_hijri,
                'biography' => $link->narrator?->biography,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function mapCommentaries(Hadith $hadith, string $locale): array
    {
        return $hadith->commentaries
            ->map(fn ($commentary) => [
                'id' => $commentary->id,
                'author' => self::localizedName(
                    $commentary->scholar?->name_ar,
                    $commentary->scholar?->name_en,
                    $locale,
                ) ?? $commentary->author,
                'languageCode' => $commentary->language_code,
                'text' => $commentary->commentary_text,
                'verified' => (bool) $commentary->verified,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function mapScholarGradings(Hadith $hadith, string $locale): array
    {
        return $hadith->gradings
            ->map(fn ($grading) => [
                'id' => $grading->id,
                'scholar' => self::localizedName($grading->scholar?->name_ar, $grading->scholar?->name_en, $locale),
                'grade' => self::localizedName(
                    $grading->gradingLevel?->grade_ar,
                    $grading->gradingLevel?->grade_en,
                    $locale,
                ),
                'gradeEn' => $grading->gradingLevel?->grade_en,
                'rank' => $grading->gradingLevel?->rank,
                'notes' => $grading->grading_notes,
            ])
            ->filter(fn (array $grading) => $grading['grade'] !== null)
            ->values()
            ->all();
    }

    private static function localizedName(?string $arabic, ?string $english, string $locale): ?string
    {
        $name = $locale === 'ar' ? $arabic : ($english ?? $arabic);

        return $name !== null && trim($name) !== '' ? $name : ($arabic ?: null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hadith' => $this->hadith->toArray(),
            'isnad' => $this->isnad,
            'commentaries' => $this->commentaries,
            'scholar_gradings' => $this->scholarGradings,
            'parallels_count' => $this->parallelsCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
