<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;
use Illuminate\Http\Response;

/**
 * Resource Not Found Exception
 * Thrown when a requested resource cannot be found
 */
class ResourceNotFoundException extends Exception
{
    protected $code = Response::HTTP_NOT_FOUND;

    /**
     * Create a new exception instance
     *
     * @param string $resource
     * @param int|string|null $identifier
     */
    public function __construct(string $resource, int|string|null $identifier = null)
    {
        $message = $identifier
            ? "Resource '{$resource}' with identifier '{$identifier}' not found."
            : "Resource '{$resource}' not found.";

        parent::__construct($message);
    }
}

