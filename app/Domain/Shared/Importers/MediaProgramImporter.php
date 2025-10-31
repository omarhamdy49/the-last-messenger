<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importers;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaCategory;
use App\Domain\Media\Enums\MediaProgramStatus;
use Illuminate\Support\Str;

/**
 * Media Program Importer
 *
 * Imports video programs from CSV/Excel files
 */
class MediaProgramImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'title_fr' => 'nullable|string|max:255',
            'title_tr' => 'nullable|string|max:255',
            'title_ur' => 'nullable|string|max:255',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'description_fr' => 'nullable|string',
            'description_tr' => 'nullable|string',
            'description_ur' => 'nullable|string',
            'category_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:upcoming,ongoing,completed',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'title_ar' => 'title_ar',
            'title_en' => 'title_en',
            'title_fr' => 'title_fr',
            'title_tr' => 'title_tr',
            'title_ur' => 'title_ur',
            'description_ar' => 'description_ar',
            'description_en' => 'description_en',
            'description_fr' => 'description_fr',
            'description_tr' => 'description_tr',
            'description_ur' => 'description_ur',
            'category_name' => 'category_name',
            'status' => 'status',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $slug = Str::slug($row['title_en'] ?? $row['title_ar']);

        return MediaProgram::where('slug', $slug)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): MediaProgram
    {
        // Find or create category
        $categoryId = null;
        if (! empty($row['category_name'])) {
            $category = MediaCategory::where('name->en', $row['category_name'])
                ->orWhere('name->ar', $row['category_name'])
                ->first();

            if (! $category) {
                $category = MediaCategory::create([
                    'name' => [
                        'ar' => $row['category_name'],
                        'en' => $row['category_name'],
                        'fr' => $row['category_name'],
                        'tr' => $row['category_name'],
                        'ur' => $row['category_name'],
                    ],
                    'slug' => Str::slug($row['category_name']),
                ]);
            }

            $categoryId = $category->id;
        }

        $title = $this->buildMultiLanguageField($row, 'title');
        $description = $this->buildMultiLanguageField($row, 'description');

        return MediaProgram::create([
            'media_category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'slug' => Str::slug($title['en'] ?: $title['ar']),
            'status' => MediaProgramStatus::tryFrom($row['status'] ?? 'upcoming') ?? MediaProgramStatus::UPCOMING,
        ]);
    }
}
