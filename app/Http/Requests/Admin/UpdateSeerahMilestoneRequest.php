<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Update Seerah Milestone Request (Admin)
 */
class UpdateSeerahMilestoneRequest extends StoreSeerahMilestoneRequest
{
    public function rules(): array
    {
        $routeParameter = $this->route('seerah_milestone') ?? $this->route('id');
        $milestoneId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

        return [
            ...parent::rules(),
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('seerah_milestones', 'slug')->ignore($milestoneId)],
            'order' => ['sometimes', 'required', 'integer', 'min:0'],
            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['required_with:title', 'string', 'max:500'],
        ];
    }
}
