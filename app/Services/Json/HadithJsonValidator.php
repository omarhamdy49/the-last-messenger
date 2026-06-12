<?php

declare(strict_types=1);

namespace App\Services\Json;

use Illuminate\Support\Facades\Log;

/**
 * HadithJsonValidator
 *
 * Responsible for detecting the structure type and validating
 * hadith JSON data across the four supported formats:
 * - collection_based   (1 file = 1 collection with many hadiths)
 * - single_hadith      (1 file = 1 hadith in multiple collections)
 * - single_hadith_flat (1 file = 1 hadith, flat Ahmad format with collection string)
 * - nested_hadith      (hadith.collection + hadith.matn structure)
 */
class HadithJsonValidator
{
    /**
     * Detect JSON structure type
     *
     * @param  array  $jsonData  The complete JSON data
     * @return string 'collection_based', 'single_hadith', 'single_hadith_flat', 'nested_hadith', or 'unknown'
     */
    public function detectStructureType(array $jsonData): string
    {
        if (isset($jsonData['hadith_text']) && isset($jsonData['collections']) && is_array($jsonData['collections'])) {
            return 'single_hadith';
        }

        if (isset($jsonData['hadith_text']) && isset($jsonData['collection']) && is_string($jsonData['collection'])) {
            return 'single_hadith_flat';
        }

        if (isset($jsonData['hadith']) && is_array($jsonData['hadith']) && isset($jsonData['hadith']['matn'])) {
            return 'nested_hadith';
        }

        if (isset($jsonData['metadata']) && isset($jsonData['chapters']) && isset($jsonData['hadiths'])) {
            return 'collection_based';
        }

        return 'unknown';
    }

    /**
     * Validate JSON data structure (auto-detects format)
     *
     * @param  array  $data  The JSON data to validate
     * @return bool True if valid, false otherwise
     */
    public function isValidData(array $data): bool
    {
        $type = $this->detectStructureType($data);

        return match ($type) {
            'single_hadith' => $this->validateSingleHadithFormat($data),
            'single_hadith_flat' => $this->validateSingleHadithFlatFormat($data),
            'nested_hadith' => $this->validateNestedHadithFormat($data),
            'collection_based' => $this->validateCollectionBasedFormat($data),
            default => $this->logUnknown(),
        };
    }

    /**
     * Validate single-hadith format
     */
    protected function validateSingleHadithFormat(array $data): bool
    {
        if (! isset($data['hadith_text']) || ! is_array($data['hadith_text'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing hadith_text', 'format' => 'single_hadith']);

            return false;
        }

        $hadithText = $data['hadith_text'];
        if (empty($hadithText['ar']) && empty($hadithText['en'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing hadith text in both languages', 'format' => 'single_hadith']);

            return false;
        }

        if (! isset($data['collections']) || ! is_array($data['collections']) || empty($data['collections'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing or empty collections', 'format' => 'single_hadith']);

            return false;
        }

        foreach ($data['collections'] as $index => $collection) {
            if (empty($collection['name_en']) && empty($collection['name_ar'])) {
                Log::warning('json.hadith.validation', ['error' => 'missing collection name', 'index' => $index]);

                return false;
            }
        }

        return true;
    }

    /**
     * Validate flat single-hadith format (Ahmad-style: collection string, flat sanad, object-pair themes)
     */
    protected function validateSingleHadithFlatFormat(array $data): bool
    {
        if (! isset($data['hadith_text']) || ! is_array($data['hadith_text'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing hadith_text', 'format' => 'single_hadith_flat']);

            return false;
        }

        $hadithText = $data['hadith_text'];
        if (empty($hadithText['ar']) && empty($hadithText['en'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing hadith text in both languages', 'format' => 'single_hadith_flat']);

            return false;
        }

        if (! isset($data['collection']) || ! is_string($data['collection'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing or non-string collection', 'format' => 'single_hadith_flat']);

            return false;
        }

        return true;
    }

    /**
     * Validate nested-hadith format (hadith.collection + hadith.matn)
     */
    protected function validateNestedHadithFormat(array $data): bool
    {
        $hadith = $data['hadith'] ?? null;
        if (! is_array($hadith)) {
            return false;
        }

        if (empty($hadith['matn'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing matn', 'format' => 'nested_hadith']);

            return false;
        }

        $matn = $hadith['matn'];
        if (empty($matn['arabic']['full_text'] ?? '') && empty($matn['english']['translation'] ?? '')) {
            Log::warning('json.hadith.validation', ['error' => 'missing text in both languages', 'format' => 'nested_hadith']);

            return false;
        }

        return true;
    }

    /**
     * Validate collection-based format
     */
    protected function validateCollectionBasedFormat(array $data): bool
    {
        if (! isset($data['metadata']) || ! is_array($data['metadata'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing metadata', 'format' => 'collection_based']);

            return false;
        }

        $metadata = $data['metadata'];

        if (! isset($metadata['id'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing metadata.id', 'format' => 'collection_based']);

            return false;
        }

        $hasTitle = ! empty($metadata['arabic']['title'] ?? '') || ! empty($metadata['english']['title'] ?? '');
        if (! $hasTitle) {
            Log::warning('json.hadith.validation', ['error' => 'missing title', 'format' => 'collection_based']);

            return false;
        }

        if (! isset($data['hadiths']) || ! is_array($data['hadiths'])) {
            Log::warning('json.hadith.validation', ['error' => 'missing hadiths array', 'format' => 'collection_based']);

            return false;
        }

        return true;
    }

    /**
     * Log unknown format and return false
     */
    private function logUnknown(): bool
    {
        Log::warning('json.hadith.validation', ['error' => 'unknown JSON structure type']);

        return false;
    }
}
