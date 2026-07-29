<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateThemeCommand;
use App\Domain\Hadith\Application\DTOs\ThemeDTO;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
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
     * @param  CreateThemeCommand  $command
     *
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): ThemeDTO
    {
        /** @var CreateThemeCommand $command */
        $slug = $command->slug['en'] ?? $command->slug['ar'] ?? null;
        if ($slug !== null) {
            $existing = $this->themeRepository->getBySlug($slug);
            if ($existing !== null) {
                throw ValidationException::withErrors([
                    'slug' => ["Theme with slug '{$slug}' already exists"],
                ]);
            }
        }

        $theme = HadithTheme::create([
            'name' => $command->name,
            'slug' => $slug ?? '',
        ]);

        return ThemeDTO::fromModel($theme);
    }
}
