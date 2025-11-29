<?php

declare(strict_types=1);

namespace App\Domain\Shared\Application\Commands;

/**
 * Command Handler Interface
 *
 * Handlers process commands and execute business logic.
 *
 * @template TCommand of Command
 * @template TResult
 */
interface CommandHandler
{
    /**
     * Handle the command
     *
     * @param TCommand $command
     * @return TResult
     */
    public function handle(Command $command): mixed;
}
