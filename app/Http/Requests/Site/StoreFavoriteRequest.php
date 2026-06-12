<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Domain\Shared\Enums\FavorableType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a bookmark add/remove request.
 *
 * Content is referenced by a short type slug (whitelisted via FavorableType) +
 * a numeric id — the raw model class is never accepted from the client. Used
 * for both store (POST) and destroy (DELETE).
 */
final class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The `auth` middleware rejects guests. Ownership is enforced in the
        // service/repository by scoping every write to the acting user's id.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(FavorableType::values())],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * The validated, whitelisted content type as an enum.
     */
    public function favorableType(): FavorableType
    {
        return FavorableType::from($this->validated('type'));
    }

    public function contentId(): int
    {
        return (int) $this->validated('id');
    }
}
