<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * Image Storage Exception
 *
 * Thrown when file system operations fail:
 * - Failed to save file to disk
 * - Disk full errors
 * - Permission denied errors
 * - Failed to delete old images
 */
class ImageStorageException extends ImageUploadException
{
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Create a new storage exception
     *
     * @param  string  $message  Detailed storage error message
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_INTERNAL_SERVER_ERROR, $previous);
    }
}
