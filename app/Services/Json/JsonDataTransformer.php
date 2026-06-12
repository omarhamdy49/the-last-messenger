<?php

declare(strict_types=1);

namespace App\Services\Json;

/**
 * JsonDataTransformer
 *
 * Backward-compatible facade that delegates to the focused split classes:
 * - HadithJsonValidator  — structure detection and validation
 * - HadithJsonTransformer — data transformation and extraction
 *
 * New code should inject HadithJsonTransformer or HadithJsonValidator directly.
 *
 * @see HadithJsonTransformer
 * @see HadithJsonValidator
 */
class JsonDataTransformer
{
    private HadithJsonValidator $validator;

    private HadithJsonTransformer $transformer;

    public function __construct(
        ?HadithJsonValidator $validator = null,
        ?HadithJsonTransformer $transformer = null,
    ) {
        $this->validator = $validator ?? new HadithJsonValidator;
        $this->transformer = $transformer ?? new HadithJsonTransformer($this->validator);
    }

    // ============================================================================
    // DETECTION & VALIDATION — delegated to HadithJsonValidator
    // ============================================================================

    public function detectStructureType(array $jsonData): string
    {
        return $this->validator->detectStructureType($jsonData);
    }

    public function isValidData(array $data): bool
    {
        return $this->validator->isValidData($data);
    }

    // ============================================================================
    // TRANSFORMATION — delegated to HadithJsonTransformer
    // ============================================================================

    public function transform(array $jsonData): array
    {
        return $this->transformer->transform($jsonData);
    }

    public function getStatistics(array $jsonData): array
    {
        return $this->transformer->getStatistics($jsonData);
    }

    public function detectCollectionFromFilename(string $filePath): ?string
    {
        return $this->transformer->detectCollectionFromFilename($filePath);
    }

    public function sanitizeText(string $text): string
    {
        return $this->transformer->sanitizeText($text);
    }
}
