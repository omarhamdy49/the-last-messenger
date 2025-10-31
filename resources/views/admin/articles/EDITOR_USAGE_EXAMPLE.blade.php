{{--
    TinyMCE Editor Usage Examples
    This file demonstrates various ways to use the TinyMCE editor component
    in The Last Messenger admin dashboard.
--}}

{{-- Example 1: Single Language Editor (Basic) --}}
<div class="mb-8">
    <h3 class="text-lg font-bold mb-4">Example 1: Basic Single Language Editor</h3>

    <form action="/admin/articles" method="POST">
        @csrf

        <div class="mb-4">
            <label for="title" class="block text-sm font-semibold mb-2">Article Title</label>
            <input type="text" name="title" id="title" class="w-full px-4 py-2 rounded-lg border">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Article Content</label>
            <x-admin.editor
                name="content"
                language="ar"
                :required="true"
                placeholder="اكتب محتوى المقال هنا..."
            />
        </div>

        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg">
            Save Article
        </button>
    </form>
</div>

{{-- Example 2: Multi-Language Editor with Tabs --}}
<div class="mb-8" x-data="{ activeLanguage: 'ar' }">
    <h3 class="text-lg font-bold mb-4">Example 2: Multi-Language Editor with Tabs</h3>

    <form action="/admin/articles" method="POST" @submit="handleSubmit">
        @csrf

        {{-- Language Tabs --}}
        <div class="flex gap-2 mb-4 border-b pb-2">
            @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <button type="button"
                        @click="activeLanguage = '{{ $lang }}'"
                        :class="activeLanguage === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg transition-all">
                    {{ $label }}
                    @if($lang === 'ar')
                        <span class="text-red-400 ml-1">*</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Title Fields --}}
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Article Title</label>
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <input type="text"
                       name="title[{{ $lang }}]"
                       x-show="activeLanguage === '{{ $lang }}'"
                       x-cloak
                       {{ $lang === 'ar' ? 'required' : '' }}
                       class="w-full px-4 py-2 rounded-lg border"
                       dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
            @endforeach
        </div>

        {{-- Content Editors --}}
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Article Content</label>
            @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
                    <x-admin.editor
                        name="content[{{ $lang }}]"
                        language="{{ $lang }}"
                        :height="500"
                        :required="$lang === 'ar'"
                        placeholder="{{ $lang === 'ar' ? 'اكتب محتوى المقال هنا...' : 'Write article content here...' }}"
                    />
                </div>
            @endforeach
        </div>

        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg">
            Save Article
        </button>
    </form>
</div>

{{-- Example 3: Editor with Custom Height --}}
<div class="mb-8">
    <h3 class="text-lg font-bold mb-4">Example 3: Editor with Custom Height</h3>

    <form action="/admin/articles" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Short Description</label>
            <x-admin.editor
                name="description"
                language="ar"
                :height="300"
                placeholder="وصف قصير..."
            />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Full Content</label>
            <x-admin.editor
                name="content"
                language="ar"
                :height="600"
                :required="true"
                placeholder="المحتوى الكامل..."
            />
        </div>

        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg">
            Save Article
        </button>
    </form>
</div>

{{-- Example 4: Editor in Edit Form with Existing Content --}}
<div class="mb-8">
    <h3 class="text-lg font-bold mb-4">Example 4: Edit Form with Existing Content</h3>

    @php
        // Simulated article data
        $article = (object)[
            'id' => 1,
            'title' => ['ar' => 'عنوان المقال', 'en' => 'Article Title'],
            'content' => [
                'ar' => '<p>هذا محتوى المقال باللغة العربية</p>',
                'en' => '<p>This is the article content in English</p>'
            ]
        ];
    @endphp

    <form action="/admin/articles/{{ $article->id }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Article Content (Arabic)</label>
            <x-admin.editor
                name="content[ar]"
                :value="old('content.ar', $article->content['ar'])"
                language="ar"
                :required="true"
            />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Article Content (English)</label>
            <x-admin.editor
                name="content[en]"
                :value="old('content.en', $article->content['en'])"
                language="en"
            />
        </div>

        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg">
            Update Article
        </button>
    </form>
</div>

{{-- Example 5: Editor with Validation --}}
<div class="mb-8" x-data="articleWithValidation()">
    <h3 class="text-lg font-bold mb-4">Example 5: Editor with Client-Side Validation</h3>

    <form action="/admin/articles" method="POST" @submit="handleSubmit">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Article Content</label>
            <x-admin.editor
                name="content"
                language="ar"
                :required="true"
                placeholder="اكتب محتوى المقال (10 كلمات على الأقل)..."
            />
        </div>

        <div x-show="validationError" x-cloak class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <p x-text="validationError"></p>
        </div>

        <button type="submit"
                :disabled="isSubmitting"
                class="px-6 py-3 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg disabled:opacity-50">
            <span x-show="!isSubmitting">Save Article</span>
            <span x-show="isSubmitting">Saving...</span>
        </button>
    </form>
</div>

{{-- Example 6: Disabled Editor (Preview Mode) --}}
<div class="mb-8">
    <h3 class="text-lg font-bold mb-4">Example 6: Disabled Editor (Preview Mode)</h3>

    @php
        $publishedContent = '<p>This is a published article that cannot be edited.</p>';
    @endphp

    <x-admin.editor
        name="content"
        :value="$publishedContent"
        language="en"
        :disabled="true"
    />
</div>

{{-- Alpine.js Scripts --}}
@push('scripts')
<script>
// Example 2: Multi-language form submission handler
function handleSubmit(event) {
    // Extract content from TinyMCE editors
    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }

    return true;
}

// Example 5: Form with validation
function articleWithValidation() {
    return {
        validationError: '',
        isSubmitting: false,

        handleSubmit(event) {
            this.validationError = '';
            this.isSubmitting = true;

            // Extract content from TinyMCE
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();

                // Get editor instance (you'll need to know the actual ID)
                const editors = tinymce.editors;

                if (editors.length > 0) {
                    const editor = editors[0];
                    const content = editor.getContent({ format: 'text' }).trim();
                    const wordCount = editor.plugins.wordcount ?
                        editor.plugins.wordcount.body.getWordCount() : 0;

                    // Validation: minimum 10 words
                    if (wordCount < 10) {
                        this.validationError = `Content is too short. Current: ${wordCount} words. Minimum: 10 words.`;
                        this.isSubmitting = false;
                        event.preventDefault();
                        return false;
                    }

                    // Validation: not empty
                    if (!content) {
                        this.validationError = 'Content is required.';
                        this.isSubmitting = false;
                        event.preventDefault();
                        return false;
                    }
                }
            }

            return true; // Allow submission
        }
    };
}
</script>
@endpush

{{-- Styling --}}
@push('styles')
<style>
    /* Example styling for tabs */
    .tab-button.active {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    }

    /* Validation error styling */
    [x-cloak] {
        display: none !important;
    }
</style>
@endpush

{{--
    NOTES FOR DEVELOPERS:

    1. Always call tinymce.triggerSave() before form submission
    2. Use unique names for multiple editors
    3. Validate content server-side (Laravel validation)
    4. Handle loading states gracefully
    5. Test in all supported languages
    6. Ensure proper RTL support for Arabic/Urdu
    7. Remove editors before navigation to avoid memory leaks
    8. Use word count for validation, not character count
    9. Provide clear error messages to users
    10. Test dark/light mode switching

    For more information, see:
    - TINYMCE_INTEGRATION_GUIDE.md (full documentation)
    - TINYMCE_QUICK_REFERENCE.md (quick reference)
    - resources/views/components/admin/editor.blade.php (component source)
    - resources/js/admin/tinymce-config.js (configuration module)
--}}
