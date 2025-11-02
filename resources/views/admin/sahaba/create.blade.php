@extends('layouts.admin')

@section('title', 'إضافة صحابي جديد')

@section('content')
<div x-data="sahabiForm()" x-init="init()" class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">إضافة صحابي جديد</h1>
            <p class="text-gray-600 mt-1">أضف معلومات صحابي جديد إلى قاعدة البيانات</p>
        </div>
        <a href="/su/portal/v1/admin/sahaba" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
            <span>رجوع</span>
        </a>
    </div>

    <form @submit.prevent="submit()" class="space-y-6">
        <!-- Basic Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-brand-primary"></i>
                <span>المعلومات الأساسية</span>
            </h2>

            <!-- Language Tabs -->
            <div class="border-b border-gray-200">
                <div class="flex gap-2">
                    <template x-for="lang in languages" :key="lang.code">
                        <button
                            type="button"
                            @click="currentLang = lang.code"
                            :class="currentLang === lang.code ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-gray-600 hover:text-gray-900'"
                            class="px-4 py-2 font-semibold transition-colors"
                            x-text="lang.label"
                        ></button>
                    </template>
                </div>
            </div>

            <!-- Name (Multi-language) -->
            <template x-for="lang in languages" :key="'name-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الاسم (<span x-text="lang.label"></span>) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="form.name[lang.code]"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        :placeholder="`أدخل الاسم بـ${lang.label}`"
                    >
                    <p x-show="errors['name.' + lang.code]" class="text-red-500 text-sm mt-1" x-text="errors['name.' + lang.code]"></p>
                </div>
            </template>

            <!-- Full Name (Multi-language) -->
            <template x-for="lang in languages" :key="'full_name-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الاسم الكامل (<span x-text="lang.label"></span>)
                    </label>
                    <input
                        type="text"
                        x-model="form.full_name[lang.code]"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                    >
                </div>
            </template>

            <!-- Kunya (Multi-language) -->
            <template x-for="lang in languages" :key="'kunya-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الكنية (<span x-text="lang.label"></span>)
                    </label>
                    <input
                        type="text"
                        x-model="form.kunya[lang.code]"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        placeholder="مثال: أبو بكر"
                    >
                </div>
            </template>

            <!-- Laqab (Multi-language) -->
            <template x-for="lang in languages" :key="'laqab-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        اللقب (<span x-text="lang.label"></span>)
                    </label>
                    <input
                        type="text"
                        x-model="form.laqab[lang.code]"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        placeholder="مثال: الصديق"
                    >
                </div>
            </template>

            <!-- Slug -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Slug (للرابط) <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        x-model="form.slug"
                        required
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        placeholder="abu-bakr-al-siddiq"
                    >
                    <button type="button" @click="generateSlug()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        توليد تلقائي
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">سيتم استخدامه في الرابط (يجب أن يكون فريداً)</p>
            </div>

            <!-- Gender & Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الجنس <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.gender" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent">
                        <option value="">اختر الجنس</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الحالة <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent">
                        <option value="">اختر الحالة</option>
                        <option value="sahabi">صحابي</option>
                        <option value="tabii">تابعي</option>
                        <option value="tabi_tabiin">تابع التابعين</option>
                    </select>
                </div>
            </div>

            <!-- Tribe & Clan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">القبيلة</label>
                    <input type="text" x-model="form.tribe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="قريش">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">العشيرة</label>
                    <input type="text" x-model="form.clan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="بنو تيم">
                </div>
            </div>
        </div>

        <!-- Media Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="image" class="w-5 h-5 text-brand-primary"></i>
                <span>الصور والوسائط</span>
            </h2>

            <!-- Profile Image -->
            <x-media.picker-button
                name="profile_image"
                label="صورة الملف الشخصي"
                mode="single"
                accept="image/*"
                :required="false"
                help-text="يفضل استخدام صورة بحجم 800×800 بكسل" />

            <!-- Gallery Images -->
            <x-media.picker-button
                name="gallery_images"
                label="معرض الصور"
                mode="multiple"
                accept="image/*"
                :required="false"
                help-text="يمكنك اختيار عدة صور للمعرض" />
        </div>

        <!-- Biographical Information -->
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="book-open" class="w-5 h-5 text-brand-primary"></i>
                <span>السيرة الذاتية</span>
            </h2>

            <!-- Biography (Multi-language) -->
            <template x-for="lang in languages" :key="'biography-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        السيرة (<span x-text="lang.label"></span>)
                    </label>
                    <textarea
                        x-model="form.biography[lang.code]"
                        rows="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        placeholder="اكتب السيرة الذاتية للصحابي..."
                    ></textarea>
                </div>
            </template>

            <!-- Virtues (Multi-language) -->
            <template x-for="lang in languages" :key="'virtues-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الفضائل (<span x-text="lang.label"></span>)
                    </label>
                    <textarea
                        x-model="form.virtues[lang.code]"
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        placeholder="اذكر فضائل الصحابي..."
                    ></textarea>
                </div>
            </template>

            <!-- Famous Narrations (Multi-language) -->
            <template x-for="lang in languages" :key="'famous_narrations-' + lang.code">
                <div x-show="currentLang === lang.code">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الروايات المشهورة (<span x-text="lang.label"></span>)
                    </label>
                    <textarea
                        x-model="form.famous_narrations[lang.code]"
                        rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent"
                        placeholder="اذكر الروايات المشهورة عن الصحابي..."
                    ></textarea>
                </div>
            </template>
        </div>

        <!-- Dates & Places -->
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="calendar" class="w-5 h-5 text-brand-primary"></i>
                <span>التواريخ والأماكن</span>
            </h2>

            <!-- Birth Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الميلاد (هجري)</label>
                    <input type="text" x-model="form.birth_date_hijri" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="573 هـ">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الميلاد (ميلادي)</label>
                    <input type="date" x-model="form.birth_date_gregorian" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent">
                </div>
            </div>

            <!-- Death Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الوفاة (هجري)</label>
                    <input type="text" x-model="form.death_date_hijri" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="13 هـ">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الوفاة (ميلادي)</label>
                    <input type="date" x-model="form.death_date_gregorian" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent">
                </div>
            </div>

            <!-- Places -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">مكان الميلاد</label>
                    <input type="text" x-model="form.birth_place" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="مكة المكرمة">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">مكان الوفاة</label>
                    <input type="text" x-model="form.death_place" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="المدينة المنورة">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">مكان الدفن</label>
                    <input type="text" x-model="form.burial_place" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="البقيع">
                </div>
            </div>

            <!-- Age at Death -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">العمر عند الوفاة</label>
                <input type="number" x-model="form.age_at_death" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="63">
            </div>
        </div>

        <!-- Conversion Info -->
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="star" class="w-5 h-5 text-brand-primary"></i>
                <span>معلومات الإسلام</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">تاريخ الإسلام (هجري)</label>
                    <input type="text" x-model="form.conversion_date_hijri" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="قبل الهجرة">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ترتيب الإسلام</label>
                    <input type="number" x-model="form.conversion_order" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="1 (أول من أسلم)">
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="info" class="w-5 h-5 text-brand-primary"></i>
                <span>معلومات إضافية</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">عدد الأحاديث المروية</label>
                    <input type="number" x-model="form.hadith_count" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ترتيب الأهمية</label>
                    <input type="number" x-model="form.importance_rank" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-primary focus:border-transparent" placeholder="1">
                    <p class="text-xs text-gray-500 mt-1">رقم أقل = أهمية أعلى</p>
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.is_featured" class="rounded border-gray-300 text-brand-primary focus:ring-brand-primary">
                    <span class="text-sm font-semibold text-gray-700">صحابي مميز (يظهر في الصفحة الرئيسية)</span>
                </label>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="/su/portal/v1/admin/sahaba" class="px-6 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                إلغاء
            </a>
            <button type="submit" :disabled="loading" class="px-6 py-2 gradient-primary text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 disabled:opacity-50">
                <i data-lucide="save" class="w-5 h-5"></i>
                <span x-text="loading ? 'جاري الحفظ...' : 'حفظ الصحابي'"></span>
            </button>
        </div>
    </form>

    <!-- Toast Notification -->
    <div x-show="toast.show" x-transition class="fixed bottom-6 left-6 max-w-sm z-50">
        <div :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'" class="px-6 py-4 text-white rounded-lg shadow-lg flex items-center gap-3">
            <i :data-lucide="toast.type === 'success' ? 'check-circle' : 'x-circle'" class="w-5 h-5"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

<script>
function sahabiForm() {
    return {
        loading: false,
        currentLang: 'ar',
        languages: [
            { code: 'ar', label: 'العربية' },
            { code: 'en', label: 'English' },
            { code: 'fr', label: 'Français' },
            { code: 'tr', label: 'Türkçe' },
            { code: 'ur', label: 'اردو' }
        ],
        form: {
            slug: '',
            name: { ar: '', en: '', fr: '', tr: '', ur: '' },
            full_name: { ar: '', en: '', fr: '', tr: '', ur: '' },
            kunya: { ar: '', en: '', fr: '', tr: '', ur: '' },
            laqab: { ar: '', en: '', fr: '', tr: '', ur: '' },
            gender: '',
            status: '',
            biography: { ar: '', en: '', fr: '', tr: '', ur: '' },
            virtues: { ar: '', en: '', fr: '', tr: '', ur: '' },
            famous_narrations: { ar: '', en: '', fr: '', tr: '', ur: '' },
            birth_date_hijri: '',
            death_date_hijri: '',
            birth_date_gregorian: '',
            death_date_gregorian: '',
            age_at_death: null,
            birth_place: '',
            death_place: '',
            burial_place: '',
            tribe: '',
            clan: '',
            conversion_date_hijri: '',
            conversion_order: null,
            hadith_count: 0,
            importance_rank: 100,
            is_featured: false
        },
        errors: {},
        toast: {
            show: false,
            type: 'success',
            message: ''
        },

        init() {
            lucide.createIcons();
        },

        async submit() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch('/su/portal/v1/admin/sahaba', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showToast('success', 'تم إضافة الصحابي بنجاح');
                    setTimeout(() => {
                        window.location.href = '/su/portal/v1/admin/sahaba';
                    }, 1500);
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    }
                    this.showToast('error', data.message || 'فشل إضافة الصحابي');
                }
            } catch (error) {
                console.error('Error creating sahabi:', error);
                this.showToast('error', 'حدث خطأ في إضافة الصحابي');
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        generateSlug() {
            const name = this.form.name.en || this.form.name.ar || '';
            this.form.slug = name
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/--+/g, '-')
                .trim();
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => {
                this.toast.show = false;
            }, 5000);
        }
    }
}
</script>
@endsection
