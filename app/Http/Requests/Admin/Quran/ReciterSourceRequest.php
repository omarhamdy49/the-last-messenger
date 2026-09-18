<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\ResourceKind;
use Illuminate\Validation\Rule;

/**
 * Reciter source actions: `lookup` previews a source id; `fetch` picks the steps to run.
 */
final class ReciterSourceRequest extends QuranAdminRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $route = (string) $this->route()?->getName();
        $source = [
            'external_source' => ['required', Rule::enum(ExternalSource::class)],
            'external_ref_id' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
        ];

        // Linking reads the kind from the reciter in the URL; a lookup has no reciter yet.
        if (str_ends_with($route, '.link-source')) {
            return $source;
        }
        if (str_ends_with($route, '.lookup')) {
            return $source + ['kind' => ['required', Rule::in([ResourceKind::RECITATION->value, ResourceKind::CHAPTER_RECITER->value])]];
        }

        return [
            'data' => ['boolean'],
            'audio' => ['boolean'],
            'timestamps' => ['boolean'],
            'download' => ['boolean'],
        ];
    }
}
