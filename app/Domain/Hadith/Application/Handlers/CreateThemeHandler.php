<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateThemeCommand;
use App\Domain\Hadith\Application\DTOs\ThemeDTO;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Handler for CreateThemeCommand
 *
 * @implements CommandHandler<CreateThemeCommand, ThemeDTO>
 */
final class CreateThemeHandler implements CommandHandler
{
    public function __construct(
        private readonly ThemeRepositoryInterface $themeRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param CreateThemeCommand $command
     * @return ThemeDTO
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): ThemeDTO
    {
        /** @var CreateThemeCommand $command */

        // Validate parent theme if provided
        if ($command->parentId !== null) {
            $parent = $this->themeRepository->find($command->parentId);
            if ($parent === null) {
                throw EntityNotFoundException::forEntity('HadithTheme', $command->parentId);
            }
        }

        // Check for duplicate slugs
        $slugAr = $command->slug['ar'] ?? null;
        if ($slugAr !== null) {
            $existing = $this->themeRepository->getBySlug($slugAr, 'ar');
            if ($existing !== null) {
                throw ValidationException::withErrors([
                    'slug_ar' => ["Theme with Arabic slug '{$slugAr}' already exists"],
                ]);
            }
        }

        $theme = HadithTheme::create([
            'name_ar' => $command->name['ar'] ?? '',
            'name_en' => $command->name['en'] ?? null,
            'slug_ar' => $command->slug['ar'] ?? '',
            'slug_en' => $command->slug['en'] ?? null,
            'description_ar' => $command->description['ar'] ?? null,
            'description_en' => $command->description['en'] ?? null,
            'parent_id' => $command->parentId,
            'sort_order' => $command->sortOrder,
        ]);

        return ThemeDTO::fromModel($theme);
    }
}
