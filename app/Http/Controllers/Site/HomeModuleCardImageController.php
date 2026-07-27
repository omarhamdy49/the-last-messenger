<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class HomeModuleCardImageController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function __invoke(int $media): BinaryFileResponse
    {
        return $this->mediaService->streamActiveHomeCardImage($media);
    }
}
