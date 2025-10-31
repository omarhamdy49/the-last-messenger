<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * Image Compression Exception
 *
 * Thrown when image optimization or compression operations fail:
 * - Failed to compress JPEG
 * - Failed to optimize PNG
 * - Failed to create thumbnails
 * - Image processing library errors
 */
class ImageCompressionException extends ImageUploadException
{
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Create a new compression exception
     *
     * @param string $message Detailed compression error message
     * @param \Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_INTERNAL_SERVER_ERROR, $previous);
    }
}
