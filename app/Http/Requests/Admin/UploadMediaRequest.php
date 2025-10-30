<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * Upload Media Request (Admin)
 * Validates media file uploads for admin panel
 */
class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has admin, super_admin, or editor role
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                File::types([
                    // Images
                    'jpg', 'jpeg', 'png', 'webp', 'gif',
                    // Videos
                    'mp4', 'webm', 'mov',
                    // Documents
                    'pdf', 'doc', 'docx',
                ])
                    ->max(102400), // 100MB in kilobytes
            ],
            'model_type' => [
                'required',
                'string',
                'in:article,media_program,sahabi',
            ],
            'model_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'collection' => [
                'required',
                'string',
                'in:featured_image,gallery,thumbnail,banner,profile_image',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file must be a valid file.',
            'file.mimes' => 'The file must be one of the following types: jpg, jpeg, png, webp, gif, mp4, webm, mov, pdf, doc, docx.',
            'file.max' => 'The file size must not exceed 100MB.',

            'model_type.required' => 'Model type is required.',
            'model_type.in' => 'Invalid model type. Must be one of: article, media_program, sahabi.',

            'model_id.required' => 'Model ID is required.',
            'model_id.integer' => 'Model ID must be a valid integer.',
            'model_id.min' => 'Model ID must be at least 1.',

            'collection.required' => 'Collection name is required.',
            'collection.in' => 'Invalid collection name. Must be one of: featured_image, gallery, thumbnail, banner, profile_image.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'file',
            'model_type' => 'model type',
            'model_id' => 'model ID',
            'collection' => 'collection',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You do not have permission to upload media. Admin, Super Admin, or Editor role is required.'
        );
    }
}
