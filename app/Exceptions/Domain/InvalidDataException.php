<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;
use Illuminate\Http\Response;

/**
 * Invalid Data Exception
 * Thrown when provided data fails business logic validation
 */
class InvalidDataException extends Exception
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param array<string, mixed> $errors
     */
    public function __construct(string $message = 'Invalid data provided', protected array $errors = [])
    {
        parent::__construct($message);
    }

    /**
     * Get validation errors
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

