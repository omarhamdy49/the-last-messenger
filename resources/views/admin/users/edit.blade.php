@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.users.index') }}" class="hover:text-accent transition-colors">{{ __('admin.user_management') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-180"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text">تعديل مستخدم</h1>
    </div>
</div>
@endsection

@section('content')
<div
    x-data="userEditor({
        user: @js($user),
        allRoles: @js($allRoles),
        roleLabels: @js(__('admin.role_labels')),
        roleDescriptions: @js(__('admin.role_descriptions')),
        canGrantSuperAdmin: @js($canGrantSuperAdmin),
        updateUrl: @js(route('admin.users.update', $id)),
        indexUrl: @js(route('admin.users.index')),
        csrf: @js(csrf_token()),
    })"
    x-init="$nextTick(() => lucide.createIcons())"
>
    {{-- Identity header: restates the record as a person, and mirrors role
         changes live so the effect of a toggle is visible before saving. --}}
    <div class="relative overflow-hidden rounded-3xl border mb-6"
         :class="darkMode ? 'bg-gray-900/60 border-gray-800' : 'bg-white border-gray-200'">
        <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-l from-[#0056b3] to-[#218838] opacity-90"></div>

        <div class="relative px-6 pt-10 pb-6 sm:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                <div class="shrink-0">
                    <div class="w-24 h-24 rounded-2xl p-[3px] bg-gradient-to-br from-[#64CCC5] to-[#0056b3] shadow-xl">
                        <div class="w-full h-full rounded-[13px] flex items-center justify-center text-3xl font-black"
                             :class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-[#0056b3]'"
                             x-text="initial()"></div>
                    </div>
                </div>

                <div class="flex-1 min-w-0 sm:pb-1">
                    <h2 class="text-2xl font-bold truncate"
                        :class="darkMode ? 'text-white' : 'text-gray-900'"
                        x-text="form.name || 'مستخدم بلا اسم'"></h2>
                    <p class="text-sm truncate mt-0.5" dir="ltr"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                       x-text="form.email"></p>

                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <template x-for="role in form.roles" :key="role">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold text-white bg-gradient-to-l from-[#0056b3] to-[#218838]"
                                  x-text="label(role)"></span>
                        </template>
                        <template x-if="form.roles.length === 0">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                                  :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                {{ __('admin.no_roles_assigned') }}
                            </span>
                        </template>

                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                              :class="user.is_verified
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                : (darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600')">
                            <i :data-lucide="user.is_verified ? 'check' : 'x'" class="w-3 h-3"></i>
                            <span x-text="user.is_verified ? '{{ __('admin.verified') }}' : '{{ __('admin.unverified') }}'"></span>
                        </span>
                    </div>
                </div>

                <div class="text-xs sm:text-end shrink-0"
                     :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    <div>{{ __('admin.member_since') }}</div>
                    <div class="font-semibold mt-0.5"
                         :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                         x-text="memberSince()"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Error banner --}}
    <div x-show="errorMessage" x-cloak
         class="mb-6 rounded-2xl border px-5 py-4 flex items-start gap-3 border-red-300 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
        <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>
        <p class="text-sm font-medium" x-text="errorMessage"></p>
    </div>

    <form @submit.prevent="save()" novalidate>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-6">
                {{-- Account details --}}
                <section class="rounded-2xl border p-6"
                         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h3 class="text-lg font-bold mb-5 flex items-center gap-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="user" class="w-5 h-5 text-accent"></i>
                        {{ __('admin.account_details') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label for="user-name" class="block text-sm font-medium mb-2"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                الاسم <span class="text-red-500">*</span>
                            </label>
                            <input id="user-name" type="text" x-model="form.name" required
                                   class="w-full px-4 py-3 rounded-xl border transition-all focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   :class="fieldClass('name', darkMode)">
                            <p x-show="errors.name" x-cloak class="mt-1.5 text-sm text-red-500" x-text="firstError('name')"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="user-email" class="block text-sm font-medium mb-2"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                البريد الإلكتروني <span class="text-red-500">*</span>
                            </label>
                            <input id="user-email" type="email" x-model="form.email" dir="ltr" required
                                   class="w-full px-4 py-3 rounded-xl border transition-all focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   :class="fieldClass('email', darkMode)">
                            <p x-show="errors.email" x-cloak class="mt-1.5 text-sm text-red-500" x-text="firstError('email')"></p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="user-language" class="block text-sm font-medium mb-2"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.preferred_language') }}
                            </label>
                            <select id="user-language" x-model="form.language"
                                    class="w-full px-4 py-3 rounded-xl border transition-all focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                    :class="fieldClass('language', darkMode)">
                                @foreach(enabled_content_languages() as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.language" x-cloak class="mt-1.5 text-sm text-red-500" x-text="firstError('language')"></p>
                        </div>
                    </div>
                </section>

                {{-- Password --}}
                <section class="rounded-2xl border p-6"
                         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h3 class="text-lg font-bold mb-1 flex items-center gap-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="key-round" class="w-5 h-5 text-accent"></i>
                        {{ __('admin.change_password') }}
                    </h3>
                    <p class="text-sm mb-5" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ __('admin.password_leave_blank') }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="user-password" class="block text-sm font-medium mb-2"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                كلمة المرور الجديدة
                            </label>
                            <div class="relative">
                                <input id="user-password" :type="showPassword ? 'text' : 'password'"
                                       x-model="form.password" dir="ltr" autocomplete="new-password"
                                       class="w-full px-4 py-3 pe-12 rounded-xl border transition-all focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                       :class="fieldClass('password', darkMode)">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute end-3 top-1/2 -translate-y-1/2 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                        :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                                        :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                                    <i x-show="!showPassword" data-lucide="eye" class="w-5 h-5"></i>
                                    <i x-show="showPassword" x-cloak data-lucide="eye-off" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <p x-show="errors.password" x-cloak class="mt-1.5 text-sm text-red-500" x-text="firstError('password')"></p>
                        </div>

                        <div>
                            <label for="user-password-confirm" class="block text-sm font-medium mb-2"
                                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                تأكيد كلمة المرور
                            </label>
                            <input id="user-password-confirm" :type="showPassword ? 'text' : 'password'"
                                   x-model="form.password_confirmation" dir="ltr" autocomplete="new-password"
                                   class="w-full px-4 py-3 rounded-xl border transition-all focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                   :class="fieldClass('password', darkMode)">
                        </div>
                    </div>
                </section>
            </div>

            {{-- Roles --}}
            <div class="space-y-6">
                <section class="rounded-2xl border p-6"
                         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <h3 class="text-lg font-bold mb-5 flex items-center gap-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="shield" class="w-5 h-5 text-accent"></i>
                        {{ __('admin.assigned_roles') }}
                    </h3>

                    <div class="space-y-2.5">
                        <template x-for="role in allRoles" :key="role">
                            <label class="block rounded-xl border p-3.5 transition-all"
                                   :class="[
                                       isLocked(role)
                                           ? 'opacity-60 cursor-not-allowed ' + (darkMode ? 'border-gray-800' : 'border-gray-200')
                                           : 'cursor-pointer ' + (has(role)
                                               ? 'border-transparent ring-2 ring-[#0056b3] ' + (darkMode ? 'bg-[#0056b3]/10' : 'bg-[#0056b3]/5')
                                               : (darkMode ? 'border-gray-800 hover:border-gray-700' : 'border-gray-200 hover:border-gray-300'))
                                   ]">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" class="sr-only peer"
                                           :checked="has(role)"
                                           :disabled="isLocked(role)"
                                           @change="toggle(role)">
                                    <span class="mt-0.5 w-5 h-5 rounded-md border-2 shrink-0 flex items-center justify-center transition-all peer-focus-visible:ring-2 peer-focus-visible:ring-accent peer-focus-visible:ring-offset-2"
                                          :class="has(role)
                                            ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] border-transparent'
                                            : (darkMode ? 'border-gray-600' : 'border-gray-300')">
                                        <i x-show="has(role)" data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                                    </span>

                                    <span class="flex-1 min-w-0">
                                        <span class="flex items-center gap-1.5">
                                            <span class="font-semibold text-sm"
                                                  :class="darkMode ? 'text-gray-100' : 'text-gray-900'"
                                                  x-text="label(role)"></span>
                                            <i x-show="isLocked(role)" data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i>
                                        </span>
                                        <span class="block text-xs mt-1 leading-relaxed"
                                              :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                                              x-text="isLocked(role) ? '{{ __('admin.role_locked_super_admin') }}' : description(role)"></span>
                                    </span>
                                </div>
                            </label>
                        </template>
                    </div>

                    <p x-show="errors.roles" x-cloak class="mt-3 text-sm text-red-500" x-text="firstError('roles')"></p>
                </section>

                {{-- Actions --}}
                <div class="rounded-2xl border p-6 space-y-3"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                    <button type="submit" :disabled="saving"
                            class="w-full px-6 py-3 rounded-xl font-semibold text-white shadow-lg bg-gradient-to-l from-[#0056b3] to-[#218838] flex items-center justify-center gap-2 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed motion-safe:hover:scale-[1.02]">
                        <template x-if="!saving">
                            <span class="flex items-center gap-2">
                                <i data-lucide="save" class="w-5 h-5"></i>
                                {{ __('admin.save_changes') }}
                            </span>
                        </template>
                        <template x-if="saving">
                            <span class="flex items-center gap-2">
                                <span class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                {{ __('admin.saving') }}
                            </span>
                        </template>
                    </button>

                    <a href="{{ route('admin.users.index') }}"
                       class="w-full px-4 py-2.5 rounded-xl font-semibold text-center border flex items-center justify-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function userEditor(config) {
    return {
        user: config.user,
        allRoles: config.allRoles,
        roleLabels: config.roleLabels,
        roleDescriptions: config.roleDescriptions,
        canGrantSuperAdmin: config.canGrantSuperAdmin,
        showPassword: false,
        saving: false,
        errors: {},
        errorMessage: '',

        form: {
            name: config.user.name ?? '',
            email: config.user.email ?? '',
            language: window.enabledContentLocales.includes(config.user.language) ? config.user.language : @js(\App\Domain\Shared\Models\Language::getDefaultCode()),
            password: '',
            password_confirmation: '',
            roles: (config.user.roles ?? []).map(r => r.name),
        },

        label(role) {
            return this.roleLabels[role] ?? role;
        },

        description(role) {
            return this.roleDescriptions[role] ?? '';
        },

        has(role) {
            return this.form.roles.includes(role);
        },

        // A non-super-admin may not grant or revoke super_admin; the server
        // enforces this too, the lock just avoids a pointless 403.
        isLocked(role) {
            return role === 'super_admin' && !this.canGrantSuperAdmin;
        },

        toggle(role) {
            if (this.isLocked(role)) return;

            this.form.roles = this.has(role)
                ? this.form.roles.filter(r => r !== role)
                : [...this.form.roles, role];

            this.$nextTick(() => lucide.createIcons());
        },

        initial() {
            return (this.form.name || '؟').trim().charAt(0).toUpperCase();
        },

        memberSince() {
            if (!this.user.created_at) return '—';
            return new Date(this.user.created_at).toLocaleDateString('{{ app()->getLocale() }}', {
                year: 'numeric', month: 'long', day: 'numeric',
            });
        },

        // darkMode lives on the layout's Alpine scope, which methods cannot
        // reach through `this` — the template passes it in.
        fieldClass(field, darkMode) {
            const base = darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900';
            const border = this.errors[field]
                ? 'border-red-400'
                : (darkMode ? 'border-gray-700' : 'border-gray-300');

            return `${base} ${border}`;
        },

        firstError(field) {
            const value = this.errors[field];
            return Array.isArray(value) ? value[0] : value;
        },

        payload() {
            const data = {
                name: this.form.name,
                email: this.form.email,
                language: this.form.language,
                roles: this.form.roles,
            };

            if (this.form.password) {
                data.password = this.form.password;
                data.password_confirmation = this.form.password_confirmation;
            }

            return data;
        },

        async save() {
            this.saving = true;
            this.errors = {};
            this.errorMessage = '';

            try {
                // A real PUT: `_method` spoofing is ignored on a JSON body, so
                // posting here would 405 against the PUT-only route.
                const res = await fetch(config.updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    body: JSON.stringify(this.payload()),
                });

                if (res.ok) {
                    window.location.href = config.indexUrl;
                    return;
                }

                if (res.status === 422) {
                    const body = await res.json();
                    this.errors = body.errors ?? {};
                    this.errorMessage = body.message || '{{ __('admin.operation_failed') }}';
                } else if (res.status === 403) {
                    this.errorMessage = '{{ __('admin.role_locked_super_admin') }}';
                } else if (res.status === 419) {
                    this.errorMessage = 'انتهت الجلسة. يرجى تحديث الصفحة.';
                } else {
                    this.errorMessage = '{{ __('admin.operation_failed') }}';
                }
            } catch (e) {
                this.errorMessage = '{{ __('admin.load_failed') }}';
            } finally {
                this.saving = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },
    };
}

document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
