<?php

declare(strict_types=1);

namespace App\Domain\Content\DTOs;

/**
 * Create Contact Message DTO
 *
 * Carries the validated public contact-form payload across the layer
 * boundary so services and repositories never receive raw arrays.
 */
final readonly class CreateContactMessageData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $subject,
        public string $body,
    ) {}

    /**
     * Build from a validated request payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            name: (string) $validated['name'],
            email: (string) $validated['email'],
            subject: (string) $validated['subject'],
            body: (string) $validated['body'],
        );
    }
}
