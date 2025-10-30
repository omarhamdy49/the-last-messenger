<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reciter Service
 * Business logic for Quran reciter management
 */
class ReciterService
{
    /**
     * Constructor
     *
     * @param ReciterRepositoryInterface $reciterRepository
     */
    public function __construct(
        protected ReciterRepositoryInterface $reciterRepository
    ) {}

    /**
     * Get paginated reciters
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->reciterRepository->paginate($perPage);
    }

    /**
     * Create new reciter with image upload
     *
     * @param array $data
     * @return Reciter
     */
    public function create(array $data): Reciter
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']['en'] ?? $data['name']['ar']);
        }

        // Handle profile image upload
        if (isset($data['profile_image']) && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['profile_image'] = $this->uploadProfileImage($data['profile_image']);
        }

        return $this->reciterRepository->create($data);
    }

    /**
     * Update reciter
     *
     * @param Reciter $reciter
     * @param array $data
     * @return Reciter
     */
    public function update(Reciter $reciter, array $data): Reciter
    {
        // Update slug if name changed
        if (isset($data['name']) && $reciter->name !== $data['name']) {
            $data['slug'] = $this->generateSlug($data['name']['en'] ?? $data['name']['ar']);
        }

        // Handle profile image upload
        if (isset($data['profile_image']) && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete old image
            if ($reciter->profile_image) {
                $this->deleteProfileImage($reciter->profile_image);
            }

            $data['profile_image'] = $this->uploadProfileImage($data['profile_image']);
        }

        $this->reciterRepository->update($reciter, $data);

        return $reciter->fresh();
    }

    /**
     * Delete reciter and cleanup image
     *
     * @param Reciter $reciter
     * @return bool
     */
    public function delete(Reciter $reciter): bool
    {
        // Delete profile image if exists
        if ($reciter->profile_image) {
            $this->deleteProfileImage($reciter->profile_image);
        }

        return $this->reciterRepository->delete($reciter);
    }

    /**
     * Bulk delete reciters and cleanup images
     *
     * @param array $ids
     * @return int Number of deleted reciters
     */
    public function bulkDelete(array $ids): int
    {
        // Get reciters before deletion to cleanup images
        $reciters = Reciter::whereIn('id', $ids)->get();

        // Delete all profile images
        foreach ($reciters as $reciter) {
            if ($reciter->profile_image) {
                $this->deleteProfileImage($reciter->profile_image);
            }
        }

        return $this->reciterRepository->bulkDelete($ids);
    }

    /**
     * Get reciters by country
     *
     * @param string $country
     * @return Collection
     */
    public function getByCountry(string $country): Collection
    {
        return $this->reciterRepository->getByCountry($country);
    }

    /**
     * Get reciters by recitation style
     *
     * @param string $style
     * @return Collection
     */
    public function getByStyle(string $style): Collection
    {
        return $this->reciterRepository->getByStyle($style);
    }

    /**
     * Get featured reciters
     *
     * @return Collection
     */
    public function getFeatured(): Collection
    {
        return $this->reciterRepository->getFeatured();
    }

    /**
     * Toggle featured status
     *
     * @param Reciter $reciter
     * @return Reciter
     */
    public function toggleFeatured(Reciter $reciter): Reciter
    {
        $this->reciterRepository->updateFeatured($reciter, !$reciter->is_featured);

        return $reciter->fresh();
    }

    /**
     * Reorder reciters
     *
     * @param array $ids Ordered array of IDs
     * @return bool
     */
    public function reorder(array $ids): bool
    {
        return $this->reciterRepository->reorder($ids);
    }

    /**
     * Count recitations for reciter
     *
     * @param Reciter $reciter
     * @return int
     */
    public function countRecitations(Reciter $reciter): int
    {
        return $this->reciterRepository->countRecitations($reciter);
    }

    /**
     * Search reciters by name
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection
    {
        return $this->reciterRepository->search($query);
    }

    /**
     * Upload profile image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string Path to uploaded file
     */
    private function uploadProfileImage(\Illuminate\Http\UploadedFile $file): string
    {
        return $file->store('reciters/profiles', 'public');
    }

    /**
     * Delete profile image
     *
     * @param string $path
     * @return bool
     */
    private function deleteProfileImage(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Generate unique slug
     *
     * @param string $title
     * @return string
     */
    private function generateSlug(string $title): string
    {
        $slug = Str::slug($title);
        $count = 1;

        // Ensure uniqueness
        while (Reciter::where('slug', $slug)->exists()) {
            $slug = Str::slug($title) . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
