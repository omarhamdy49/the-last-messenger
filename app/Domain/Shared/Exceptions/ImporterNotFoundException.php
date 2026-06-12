<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Importer Not Found Exception
 *
 * Thrown when no importer is registered for a given module name.
 * Maps to a 422 response via the DomainException renderer in bootstrap/app.php.
 */
final class ImporterNotFoundException extends DomainException
{
    /**
     * Create for a specific module name
     */
    public static function forModule(string $module): static
    {
        return new self(
            message: "لا يوجد مستورد للوحدة: {$module} | No importer found for module: {$module}",
            context: ['module' => $module],
            code: 422
        );
    }
}
