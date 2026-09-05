<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreHadithRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'collection_id' => ['required', 'integer', 'exists:hadith_collections,id'],
            'book_id' => ['nullable', 'integer', 'exists:hadith_books,id'],
            'chapter_id' => ['nullable', 'integer', 'exists:hadith_chapters,id'],

            // The public route is /hadith/{collection}:{reference_key}, so this
            // pair is the addressable identity and must stay unique per
            // collection — a duplicate silently shadows an existing hadith.
            'reference_key' => [
                'required', 'string', 'max:100',
                Rule::unique('hadiths', 'reference_key')
                    ->where(fn ($q) => $q->where('collection_id', $this->input('collection_id')))
                    ->ignore($this->route('hadith')),
            ],

            // NOT NULL with no database default — an omitted number is a 500, not a blank.
            'number' => ['required', 'string', 'max:32'],
            'in_book_number' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['required', 'integer', 'min:0'],

            // Nullable on purpose: SunnahGradeMapper refuses to guess an
            // ambiguous grade and keeps raw_grade as the evidence. The panel
            // must allow the same "not decided" state rather than force a pick.
            'grade' => ['nullable', new Enum(Grade::class)],
            'raw_grade' => ['nullable', 'string', 'max:255'],

            'text_ar' => ['required', 'string'],
            'text_en' => ['nullable', 'string'],
            'narrated_by' => ['nullable', 'string', 'max:255'],
            'canonical_reference' => ['nullable', 'string', 'max:255'],
            'in_book_reference' => ['nullable', 'string', 'max:255'],
            'translation_reference' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /** The book/chapter must belong to the collection, or the tree is incoherent. */
    public function after(): array
    {
        return [
            function ($validator): void {
                $bookId = $this->input('book_id');
                $chapterId = $this->input('chapter_id');
                $collectionId = $this->input('collection_id');

                if ($bookId && ! HadithBook::where('id', $bookId)
                    ->where('collection_id', $collectionId)->exists()) {
                    $validator->errors()->add('book_id', __('hadith_admin.book_not_in_collection'));
                }

                if ($chapterId && $bookId && ! HadithChapter::where('id', $chapterId)
                    ->where('book_id', $bookId)->exists()) {
                    $validator->errors()->add('chapter_id', __('hadith_admin.chapter_not_in_book'));
                }

                if ($chapterId && ! $bookId) {
                    $validator->errors()->add('chapter_id', __('hadith_admin.chapter_needs_book'));
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        // sort_order is NOT NULL with no default; an empty input must become 0.
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'sort_order' => $this->input('sort_order') === null || $this->input('sort_order') === ''
                ? 0
                : $this->input('sort_order'),
        ]);
    }
}
