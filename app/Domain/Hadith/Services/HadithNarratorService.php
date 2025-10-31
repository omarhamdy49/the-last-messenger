<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Enums\NarratorGrade;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Repositories\HadithNarratorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Hadith Narrator Service
 * Handles business logic for hadith narrators
 */
class HadithNarratorService
{
    /**
     * Constructor
     */
    public function __construct(
        protected HadithNarratorRepositoryInterface $repository
    ) {}

    /**
     * Get paginated narrators with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($perPage, $filters);
    }

    /**
     * Create new narrator
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HadithNarrator
    {
        return $this->repository->create($data);
    }

    /**
     * Update narrator
     *
     * @param  array<string, mixed>  $data
     */
    public function update(HadithNarrator $narrator, array $data): HadithNarrator
    {
        return $this->repository->updateNarrator($narrator, $data);
    }

    /**
     * Delete narrator
     */
    public function delete(HadithNarrator $narrator): bool
    {
        return $this->repository->deleteNarrator($narrator);
    }

    /**
     * Bulk delete narrators
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Get narrator with details
     */
    public function getWithDetails(int $id): ?HadithNarrator
    {
        return $this->repository->getById($id);
    }

    /**
     * Search narrators
     */
    public function search(string $query): Collection
    {
        return $this->repository->search($query);
    }

    /**
     * Get narrators by grade
     */
    public function getByGrade(NarratorGrade|string $grade): Collection
    {
        if (is_string($grade)) {
            $grade = NarratorGrade::from($grade);
        }

        return $this->repository->getByGrade($grade);
    }

    /**
     * Get narrators by era
     */
    public function getByEra(string $era): Collection
    {
        return $this->repository->getByEra($era);
    }

    /**
     * Get narrators by country
     */
    public function getByCountry(string $country): Collection
    {
        return $this->repository->getByCountry($country);
    }

    /**
     * Get teachers of a narrator
     */
    public function getTeachers(int $narratorId): Collection
    {
        return $this->repository->getTeachers($narratorId);
    }

    /**
     * Get students of a narrator
     */
    public function getStudents(int $narratorId): Collection
    {
        return $this->repository->getStudents($narratorId);
    }

    /**
     * Get hadith count for a narrator
     */
    public function getHadithCount(int $narratorId): int
    {
        return $this->repository->getHadithCount($narratorId);
    }

    /**
     * Get narrators count by grade
     *
     * @return array<string, int>
     */
    public function countByGrade(): array
    {
        return $this->repository->countByGrade();
    }

    /**
     * Get narrators count by era
     *
     * @return array<string, int>
     */
    public function countByEra(): array
    {
        return $this->repository->countByEra();
    }

    /**
     * Get active narrators
     */
    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    /**
     * Get trustworthy narrators
     */
    public function getTrustworthy(): Collection
    {
        return $this->repository->getTrustworthy();
    }

    /**
     * Get all reliability grades
     *
     * @return array<string, string>
     */
    public function getReliabilityGrades(): array
    {
        $grades = [];
        foreach (NarratorGrade::cases() as $grade) {
            $grades[$grade->value] = $grade->label();
        }
        return $grades;
    }

    /**
     * Get all eras
     *
     * @return array<string>
     */
    public function getEras(): array
    {
        return [
            'Sahabah',
            'Tabiun',
            'Taba_al_Tabiun',
            'Taba_Taba_al_Tabiun',
            'Later_Scholars',
        ];
    }

    /**
     * Validate narrator data
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>  Validation errors (empty if valid)
     */
    public function validateNarratorData(array $data): array
    {
        $errors = [];

        // Validate required fields
        if (empty($data['name']['ar'])) {
            $errors['name.ar'] = 'Arabic name is required';
        }

        if (empty($data['name']['en'])) {
            $errors['name.en'] = 'English name is required';
        }

        if (empty($data['reliability_grade'])) {
            $errors['reliability_grade'] = 'Reliability grade is required';
        }

        if (empty($data['era'])) {
            $errors['era'] = 'Era is required';
        }

        // Validate grade enum
        if (!empty($data['reliability_grade'])) {
            try {
                NarratorGrade::from($data['reliability_grade']);
            } catch (\ValueError $e) {
                $errors['reliability_grade'] = 'Invalid reliability grade';
            }
        }

        // Validate era enum
        if (!empty($data['era']) && !in_array($data['era'], $this->getEras())) {
            $errors['era'] = 'Invalid era';
        }

        return $errors;
    }
}
