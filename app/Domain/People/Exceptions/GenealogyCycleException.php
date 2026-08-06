<?php

declare(strict_types=1);

namespace App\Domain\People\Exceptions;

use RuntimeException;

final class GenealogyCycleException extends RuntimeException
{
    public static function selfParent(): self
    {
        return new self(__('people.errors.self_parent'));
    }

    public static function ancestorCycle(string $name): self
    {
        return new self(__('people.errors.ancestor_cycle', ['name' => $name]));
    }
}
