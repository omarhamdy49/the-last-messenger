<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

/**
 * Base Image Upload Exception
 *
 * Parent exception for all image upload related errors.
 * Provides a unified exception hierarchy for image processing operations.
 */
class ImageUploadException extends Exception
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * Create a new exception instance
     *
     * @param  string  $message  The error message
     * @param  int  $code  HTTP status code (default: 422)
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(string $message, int $code = Response::HTTP_UNPROCESSABLE_ENTITY, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
