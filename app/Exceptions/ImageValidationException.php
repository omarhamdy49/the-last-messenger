<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * Image Validation Exception
 *
 * Thrown when uploaded image fails validation checks:
 * - Invalid file type
 * - File size violations
 * - Invalid dimensions
 * - Corrupted image files
 */
class ImageValidationException extends ImageUploadException
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * Create a new validation exception
     *
     * @param  string  $message  Detailed validation error message
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY, $previous);
    }
}
