<?php

declare(strict_types=1);

namespace App\Domain\Shared\Application\DTOs;

/**
 * Generic Result DTO
 *
 * Represents the result of an operation with success/failure status.
 *
 * @template T
 */
final readonly class Result
{
    /**
     * @param T|null $data
     * @param array<string> $errors
     */
    private function __construct(
        public bool $success,
        public mixed $data,
        public string $message,
        public array $errors = []
    ) {}

    /**
     * Create successful result with data
     *
     * @template TData
     * @param TData $data
     * @return Result<TData>
     */
    public static function success(mixed $data = null, string $message = 'تمت العملية بنجاح'): self
    {
        return new self(
            success: true,
            data: $data,
            message: $message
        );
    }

    /**
     * Create failure result
     *
     * @param array<string> $errors
     * @return Result<null>
     */
    public static function failure(string $message, array $errors = []): self
    {
        return new self(
            success: false,
            data: null,
            message: $message,
            errors: $errors
        );
    }

    /**
     * Check if operation was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if operation failed
     */
    public function isFailure(): bool
    {
        return ! $this->success;
    }

    /**
     * Get errors if any
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }
}
