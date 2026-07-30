<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\DTOs\HadithDetail;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;

/**
 * Every public read of a single hadith goes through here, so the reference lines
 * and the publication gate are applied in exactly one place.
 *
 * Returns DTOs, never models: a Blade view or an API resource holding a model
 * can lazy-load a relation and turn one page into fifty queries.
 */
final class HadithQueryService
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadiths,
        private readonly ReferenceBuilder $references,
        private readonly HadithVisibility $visibility,
    ) {}

    /**
     * The canonical lookup behind `/hadith/{collection}:{number}`.
     *
     * `$hadithNumber` is a string because the source uses `1a`.
     */
    public function findByCollectionAndNumber(
        string $collectionCode,
        string $hadithNumber,
        ?string $locale = null,
    ): ?HadithDetail {
        $hadith = $this->hadiths->findCanonical($collectionCode, $hadithNumber);

        if ($hadith === null || ! $this->visibility->allowsHadith($hadith)) {
            return null;
        }

        return HadithDetail::fromModel(
            $hadith,
            $this->references->build($hadith),
            $locale ?? app()->getLocale(),
        );
    }

    /**
     * Resolve an external sunnah.com permalink. Around 35% of records carry no
     * urn, so a null here is an ordinary miss, not a failure.
     */
    public function findByUrn(int $urn, ?string $locale = null): ?HadithDetail
    {
        $hadith = $this->hadiths->findByUrn($urn);

        if ($hadith === null || ! $this->visibility->allowsHadith($hadith)) {
            return null;
        }

        return HadithDetail::fromModel(
            $hadith,
            $this->references->build($hadith),
            $locale ?? app()->getLocale(),
        );
    }

    /**
     * One random published hadith, optionally of a given grade — the home page's
     * daily card and the API's random endpoint.
     */
    public function randomPublished(?string $locale = null, ?HadithGrade $grade = null): ?HadithDetail
    {
        $hadith = $this->hadiths->randomPublished($grade);

        if ($hadith === null) {
            return null;
        }

        return HadithDetail::fromModel(
            $hadith,
            $this->references->build($hadith),
            $locale ?? app()->getLocale(),
        );
    }
}
