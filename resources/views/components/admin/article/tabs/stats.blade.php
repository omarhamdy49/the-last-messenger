{{--
    Statistics Tab Component - Article Form
    Features: References and statistics management using repeatable fields
--}}

@props([
    'article' => null
])

<div x-show="activeTab === 'statistics'" x-transition class="space-y-6">
    <x-admin.repeatable-field
        name="references"
        :label="__('articles.fields.references', [], 'ar')"
        :fields="[
            ['name' => 'name', 'label' => 'اسم المرجع', 'type' => 'text', 'placeholder' => 'اسم المرجع'],
            ['name' => 'url', 'label' => 'رابط المرجع', 'type' => 'text', 'placeholder' => 'https://...'],
            ['name' => 'type', 'label' => 'نوع المرجع', 'type' => 'text', 'placeholder' => 'كتاب، مقال، موقع، إلخ']
        ]"
        :values="old('references', $article ? $article->references : [])"
        :addButtonText="__('articles.fields.add_reference', [], 'ar')"
        :emptyMessage="'لا توجد مراجع'"
        :help="'أضف مراجع ومصادر لإثراء محتوى المقال وتوثيق المعلومات. يمكن أن تكون كتباً، مقالات، أو مواقع إلكترونية.'"
    />

    <x-admin.repeatable-field
        name="statistics"
        :label="__('articles.fields.statistics', [], 'ar')"
        :fields="[
            ['name' => 'name', 'label' => 'اسم الإحصائية', 'type' => 'text', 'placeholder' => 'مثال: عدد الأحاديث المذكورة'],
            ['name' => 'value', 'label' => 'القيمة', 'type' => 'text', 'placeholder' => 'مثال: 25']
        ]"
        :values="old('statistics', $article ? $article->statistics : [])"
        :addButtonText="__('articles.fields.add_stat', [], 'ar')"
        :emptyMessage="'لا توجد إحصائيات'"
        :help="'أضف إحصائيات متعلقة بالمقال (عدد الآيات، الأحاديث، المراجع، إلخ). تُعرض هذه البيانات في صفحة المقال.'"
    />
</div>
