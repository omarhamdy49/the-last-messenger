<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\CreateReciterData;
use App\Domain\Quran\DTOs\UpdateReciterData;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use App\Domain\Shared\Services\PublicImageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
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
     */
    public function __construct(
        protected ReciterRepositoryInterface $reciterRepository,
        protected PublicImageService $images,
    ) {}

    /**
     * Get paginated reciters
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->reciterRepository->paginate($perPage);
    }

    /**
     * Get paginated reciters with optional search/country/style filters (admin list).
     *
     * @param  array{search?: string, country?: string, style?: string}  $filters
     */
    public function list(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->reciterRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * Find a single reciter by ID. Returns null when not found.
     */
    public function find(int $id): ?Reciter
    {
        return $this->reciterRepository->getById($id);
    }

    /**
     * Find a single reciter by ID or throw ModelNotFoundException.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Reciter
    {
        $reciter = $this->reciterRepository->getById($id);

        if ($reciter === null) {
            throw (new ModelNotFoundException)->setModel(Reciter::class, $id);
        }

        return $reciter;
    }

    /**
     * Get aggregate stats for the admin dashboard card.
     *
     * @return array{total: int, featured: int, countries: int, total_recitations: int}
     */
    public function getStats(): array
    {
        $stats = $this->reciterRepository->getStats();

        return array_merge($stats, [
            'total_recitations' => 0,
        ]);
    }

    /**
     * Get a sorted, distinct list of non-null country codes for filter dropdowns.
     *
     * @return Collection<int, string>
     */
    public function getDistinctCountries(): Collection
    {
        return $this->reciterRepository->getDistinctCountries();
    }

    /**
     * Create new reciter with image upload
     */
    public function create(CreateReciterData $data): Reciter
    {
        $slug = empty($data->slug)
            ? $this->generateSlug($data->name['en'] ?? $data->name['ar'])
            : $data->slug;

        $source = $data->profileImage ?? $data->profileImageId;
        $profileImagePath = $source !== null
            ? ($this->uploadProfileImage($source) ?: null)
            : $data->profileImageUrl;

        return $this->reciterRepository->create([
            'name' => $data->name,
            'description' => $data->description,
            'slug' => $slug,
            'profile_id' => $data->profileId,
            'riwayah' => $data->riwayah,
            'country' => $data->country,
            'style' => $data->style,
            'profile_image' => $profileImagePath,
            'is_featured' => $data->isFeatured,
            'display_order' => $data->displayOrder,
        ]);
    }

    /**
     * Update reciter
     */
    public function update(Reciter $reciter, UpdateReciterData $data): Reciter
    {
        $payload = [
            'name' => $data->name,
            'description' => $data->description,
            'country' => $data->country,
            'style' => $data->style,
            'is_featured' => $data->isFeatured,
        ];

        if ($data->profileId !== null) {
            $payload['profile_id'] = $data->profileId;
        }
        if ($data->riwayah !== null) {
            $payload['riwayah'] = $data->riwayah;
        }

        if ($data->displayOrder !== null) {
            $payload['display_order'] = $data->displayOrder;
        }

        // Update slug if name changed
        if ($reciter->name !== $data->name) {
            $payload['slug'] = $data->slug ?? $this->generateSlug($data->name['en'] ?? $data->name['ar']);
        } elseif ($data->slug !== null) {
            $payload['slug'] = $data->slug;
        }

        $image = $this->resolveImage($data->profileImage ?? $data->profileImageId, $data->profileImageUrl, $data->removeProfileImage, $reciter->profile_image);
        if ($image !== false) {
            $payload['profile_image'] = $image;
        }

        $this->reciterRepository->update($reciter, $payload);

        return $reciter->fresh();
    }

    /**
     * Applies a <x-admin.image-upload> submission to a stored image column — shared by
     * recordings and reciter profiles. Precedence: a fresh upload or media-library pick,
     * then a changed external URL, then an explicit removal; anything else keeps the
     * current image. A replaced local file is deleted only when nothing else uses it.
     *
     * @return string|false|null the new column value, or false to keep the current one
     */
    public function resolveImage(UploadedFile|int|null $source, ?string $url, bool $remove, ?string $current): string|false|null
    {
        $new = match (true) {
            $source !== null => $this->uploadProfileImage($source) ?: false,
            $url !== null && $url !== $current => $url,
            $remove && $url === null => null,
            default => false,
        };

        if ($new !== false && $current) {
            $this->deleteUnsharedImage($current);
        }

        return $new;
    }

    /**
     * Delete reciter and cleanup image
     */
    public function delete(Reciter $reciter): bool
    {
        // Delete profile image if exists
        if ($reciter->profile_image) {
            $this->deleteUnsharedImage($reciter->profile_image);
        }

        return $this->reciterRepository->delete($reciter);
    }

    /**
     * Bulk delete reciters and cleanup images
     *
     * @param  array<int>  $ids
     * @return int Number of deleted reciters
     */
    public function bulkDelete(array $ids): int
    {
        // Fetch only the columns needed for image cleanup — no select *
        $reciters = $this->reciterRepository->getByIds($ids);

        foreach ($reciters as $reciter) {
            if ($reciter->profile_image) {
                $this->deleteUnsharedImage($reciter->profile_image);
            }
        }

        return $this->reciterRepository->bulkDelete($ids);
    }

    /**
     * Get reciters by country
     */
    public function getByCountry(string $country): Collection
    {
        return $this->reciterRepository->getByCountry($country);
    }

    /**
     * Get reciters by recitation style
     */
    public function getByStyle(string $style): Collection
    {
        return $this->reciterRepository->getByStyle($style);
    }

    /**
     * Get featured reciters
     */
    public function getFeatured(): Collection
    {
        return $this->reciterRepository->getFeatured();
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Reciter $reciter): Reciter
    {
        $this->reciterRepository->updateFeatured($reciter, ! $reciter->is_featured);

        return $reciter->fresh();
    }

    /**
     * Reorder reciters
     *
     * @param  array  $ids  Ordered array of IDs
     */
    public function reorder(array $ids): bool
    {
        return $this->reciterRepository->reorder($ids);
    }

    /**
     * Count recitations for reciter
     */
    public function countRecitations(Reciter $reciter): int
    {
        return $this->reciterRepository->countRecitations($reciter);
    }

    /**
     * Sibling recordings (including this one) of the recording's profile.
     */
    public function profileRecordings(Reciter $reciter): Collection
    {
        return $reciter->profile_id ? $this->reciterRepository->getProfileRecordings($reciter->profile_id) : collect([$reciter]);
    }

    /**
     * Search reciters by name
     */
    public function search(string $query): Collection
    {
        return $this->reciterRepository->search($query);
    }

    /**
     * Upload profile image
     *
     * @return string Path to uploaded file
     */
    private function uploadProfileImage(UploadedFile|int $source): string
    {
        return $this->images->store($source, 'reciters/profiles', 'reciters') ?? '';
    }

    /**
     * Grouping copies a recording's image path onto its profile (and sibling
     * recordings), so a path can back several rows. Only the last user deletes it.
     */
    private function deleteUnsharedImage(string $path): void
    {
        $uses = Reciter::where('profile_image', $path)->count() + ReciterProfile::where('profile_image', $path)->count();
        if ($uses <= 1) {
            $this->deleteProfileImage($path);
        }
    }

    /**
     * Delete profile image
     */
    private function deleteProfileImage(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            // Removes the derived card/hero copies alongside the original.
            $this->images->delete($path);

            return true;
        }

        return false;
    }

    /**
     * Generate unique slug
     */
    private function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $count = 1;

        // Ensure uniqueness by delegating the lookup to the repository
        while ($this->reciterRepository->getBySlug($slug) !== null) {
            $slug = $base.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
