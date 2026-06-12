<?php

declare(strict_types=1);

namespace App\Services\Dtos;

/**
 * SEO Validation Result Data Transfer Object
 *
 * Contains validation results, errors, warnings, and SEO score for content.
 * Provides scoring logic and detailed feedback for SEO improvements.
 */
class SeoValidationResult
{
    /**
     * Create new SEO Validation Result instance
     *
     * @param  bool  $isValid  Overall validation status
     * @param  array<string>  $errors  Critical issues that must be fixed
     * @param  array<string>  $warnings  Recommendations for improvement
     * @param  array<string>  $successes  Good practices that are followed
     * @param  float  $seoScore  SEO score (0-100)
     * @param  array<string, mixed>  $metrics  Detailed metrics
     */
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public array $successes = [],
        public float $seoScore = 0.0,
        public array $metrics = []
    ) {}

    /**
     * Calculate SEO score based on validation results
     *
     * Score calculation:
     * - Start with 100 points
     * - Each error: -10 points
     * - Each warning: -5 points
     * - Minimum score: 0
     *
     * @return float Score between 0 and 100
     */
    public function calculateScore(): float
    {
        $score = 100.0;

        // Deduct points for errors
        $score -= count($this->errors) * 10;

        // Deduct points for warnings
        $score -= count($this->warnings) * 5;

        // Ensure score is between 0 and 100
        return max(0.0, min(100.0, $score));
    }

    /**
     * Recalculate and update SEO score
     */
    public function updateScore(): self
    {
        $this->seoScore = $this->calculateScore();

        return $this;
    }

    /**
     * Get score grade (A, B, C, D, F)
     */
    public function getGrade(): string
    {
        return match (true) {
            $this->seoScore >= 90 => 'A',
            $this->seoScore >= 80 => 'B',
            $this->seoScore >= 70 => 'C',
            $this->seoScore >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Get score color for UI display
     *
     * @return string Color name (green, yellow, orange, red)
     */
    public function getScoreColor(): string
    {
        return match (true) {
            $this->seoScore >= 80 => 'green',
            $this->seoScore >= 60 => 'yellow',
            $this->seoScore >= 40 => 'orange',
            default => 'red',
        };
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if there are any successes
     */
    public function hasSuccesses(): bool
    {
        return count($this->successes) > 0;
    }

    /**
     * Get total issue count (errors + warnings)
     */
    public function getIssueCount(): int
    {
        return count($this->errors) + count($this->warnings);
    }

    /**
     * Get summary text
     */
    public function getSummary(): string
    {
        if ($this->isValid && $this->seoScore >= 80) {
            return 'Excellent SEO optimization!';
        }

        if ($this->isValid) {
            return 'Good SEO, but there are some improvements possible.';
        }

        if ($this->hasErrors()) {
            return sprintf('Found %d critical issue%s that need attention.', count($this->errors), count($this->errors) === 1 ? '' : 's');
        }

        return 'SEO optimization needs improvement.';
    }

    /**
     * Add error
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->isValid = false;

        return $this;
    }

    /**
     * Add warning
     */
    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    /**
     * Add success
     */
    public function addSuccess(string $success): self
    {
        $this->successes[] = $success;

        return $this;
    }

    /**
     * Set metric value
     */
    public function setMetric(string $key, mixed $value): self
    {
        $this->metrics[$key] = $value;

        return $this;
    }

    /**
     * Get metric value
     */
    public function getMetric(string $key, mixed $default = null): mixed
    {
        return $this->metrics[$key] ?? $default;
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'seo_score' => $this->seoScore,
            'grade' => $this->getGrade(),
            'score_color' => $this->getScoreColor(),
            'summary' => $this->getSummary(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'successes' => $this->successes,
            'metrics' => $this->metrics,
            'issue_count' => $this->getIssueCount(),
        ];
    }

    /**
     * Create a passing validation result
     */
    public static function pass(): self
    {
        return new self(
            isValid: true,
            errors: [],
            warnings: [],
            successes: ['All SEO requirements met'],
            seoScore: 100.0
        );
    }

    /**
     * Create a failing validation result
     *
     * @param  array<string>  $errors
     */
    public static function fail(array $errors): self
    {
        $result = new self(
            isValid: false,
            errors: $errors,
            warnings: [],
            successes: [],
            seoScore: 0.0
        );

        return $result->updateScore();
    }
}
