<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use RuntimeException;

/**
 * Raised when a yt-dlp invocation fails or returns data we cannot use.
 */
final class YouTubeException extends RuntimeException {}
