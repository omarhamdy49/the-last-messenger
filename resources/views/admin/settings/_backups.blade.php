{{--
    Database archives — super_admin only (the controller gates $maySeeBackups, and
    the download/delete routes gate again; neither relies on the other).

    Creating one is not a button here on purpose: `db:dump` already sits in the
    Commands panel above, where it gets a queued run with progress polling and a
    CommandRun audit row. This panel only lists what that produced.
--}}
<div class="rounded-2xl border p-6 mt-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div class="min-w-0">
            <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="database-backup" class="w-5 h-5 text-accent"></i>
                النسخ الاحتياطي لقاعدة البيانات
            </h2>
            <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                أنشئ نسخة من لوحة الأوامر بالأمر <code class="px-1 rounded" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">db:dump</code>،
                ثم نزّلها من هنا. يُحتفظ بآخر خمس نسخ وتُحذف الأقدم تلقائيًّا.
            </p>
        </div>

        @if(count($backups) > 0)
            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold tabular-nums shrink-0"
                  :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                {{ count($backups) }} نسخة · {{ $backupsTotal }}
            </span>
        @endif
    </div>

    {{-- Said once, plainly, where the action is. A dump carries every password
         hash and API token in the system. --}}
    <div class="rounded-xl border p-3 mb-5 flex items-start gap-2.5 text-xs"
         :class="darkMode ? 'border-amber-500/30 bg-amber-500/5 text-amber-300' : 'border-amber-300 bg-amber-50 text-amber-800'">
        <i data-lucide="shield-alert" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <p class="leading-relaxed">
            الملف يحتوي كل بيانات الموقع، ومنها كلمات المرور المُعمّاة ومفاتيح الدخول.
            نزّله إلى جهاز موثوق فقط، ولا تشاركه، واحذفه من الخادم بعد الانتهاء.
        </p>
    </div>

    @if(count($backups) === 0)
        <div class="rounded-xl border border-dashed py-10 px-6 text-center"
             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <i data-lucide="database" class="w-6 h-6" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            </div>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                لا توجد نسخ بعد — شغّل الأمر <span class="font-semibold">db:dump</span> من لوحة الأوامر أعلاه.
            </p>
        </div>
    @else
        <div class="overflow-x-auto custom-scrollbar rounded-xl border"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <table class="w-full text-sm">
                <thead>
                    <tr :class="darkMode ? 'bg-gray-800/50 text-gray-400' : 'bg-gray-50 text-gray-600'">
                        <th class="text-start font-semibold px-4 py-3 text-xs">الملف</th>
                        <th class="text-start font-semibold px-4 py-3 text-xs whitespace-nowrap">التاريخ</th>
                        <th class="text-start font-semibold px-4 py-3 text-xs whitespace-nowrap">الحجم</th>
                        <th class="text-start font-semibold px-4 py-3 text-xs">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                        <tr class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <td class="px-4 py-3 font-mono text-xs" dir="ltr">{{ $backup['name'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap tabular-nums"
                                :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $backup['created_at']->format('Y/m/d — H:i') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap tabular-nums font-semibold"
                                :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ app(\App\Domain\Shared\Services\DatabaseDumpService::class)->humanSize($backup['size']) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.settings.backups.download', $backup['name']) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] inline-flex items-center gap-1.5 transition-transform hover:scale-[1.03] focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        تنزيل
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.settings.backups.destroy', $backup['name']) }}"
                                          x-data
                                          @submit="if (! confirm('حذف النسخة {{ $backup['name'] }} نهائيًّا؟')) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-500 hover:bg-red-500/10 transition-colors inline-flex items-center gap-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
