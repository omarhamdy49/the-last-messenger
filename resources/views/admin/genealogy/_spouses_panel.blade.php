{{-- Spouse relationship curation: which wives/spouses appear in the public
     tree, in what order, and whether they're hidden — see relationships
     table's display_order/hide_from_tree columns. --}}
<div class="rounded-2xl border p-6"
     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <h2 class="text-xl font-bold mb-2 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
        <i data-lucide="heart" class="w-5 h-5 text-accent"></i>
        العلاقات الزوجية
    </h2>
    <p class="text-xs mb-6" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
        تتحكم هذه القائمة في ظهور كل زوج/زوجة في الشجرة العامة وترتيبه — الترتيب الأصغر يظهر أولاً، ويمكن إخفاء أي علاقة دون حذفها.
    </p>

    @if($spouseRelationships->isEmpty())
        <p class="text-sm mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد علاقات زوجية مسجّلة لهذا الشخص بعد.</p>
    @else
        <div class="space-y-3 mb-6">
            @foreach($spouseRelationships as $relationship)
                @php
                    $other = $relationship->person_id === $person->id ? $relationship->relatedPerson : $relationship->person;
                @endphp
                @if($other)
                    <div class="flex flex-wrap items-center gap-3 p-4 rounded-xl border"
                         :class="darkMode ? 'border-gray-800 bg-gray-800/40' : 'border-gray-200 bg-gray-50'">
                        <div class="flex-1 min-w-[140px]">
                            <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $other->getTranslation('name', 'ar') }}</p>
                            <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">#{{ $other->id }}</p>
                        </div>

                        <form action="{{ route('admin.genealogy.relationships.update', $relationship->id) }}" method="POST" class="flex flex-wrap items-center gap-3">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="return_to" value="{{ $person->id }}">

                            <div>
                                <label class="block text-xs mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الترتيب</label>
                                <input type="number" name="display_order" value="{{ $relationship->display_order }}" min="0"
                                       class="w-24 px-3 py-2 rounded-lg border text-sm"
                                       :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>

                            <label class="flex items-center gap-2 cursor-pointer text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                <input type="hidden" name="hide_from_tree" value="0">
                                <input type="checkbox" name="hide_from_tree" value="1"
                                       {{ $relationship->hide_from_tree ? 'checked' : '' }}
                                       class="w-4 h-4 rounded text-red-500 border-gray-300 focus:ring-red-500">
                                إخفاء
                            </label>

                            <button type="submit"
                                    class="px-3 py-2 rounded-lg text-sm font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                                حفظ
                            </button>
                        </form>

                        <form action="{{ route('admin.genealogy.relationships.destroy', $relationship->id) }}" method="POST"
                              onsubmit="return confirm('هل تريد حذف هذه العلاقة الزوجية؟');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_to" value="{{ $person->id }}">
                            <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-all">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.genealogy.relationships.store', $person->id) }}" method="POST" class="flex flex-wrap items-end gap-3 pt-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        @csrf
        <div class="flex-1 min-w-[180px]">
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">إضافة زوج/زوجة (معرّف الشخص ID)</label>
            <input type="number" name="spouse_id" value="{{ old('spouse_id') }}" min="1"
                   class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                   placeholder="أدخل معرّف الشخص من قاعدة البيانات">
            @error('spouse_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
        </div>
        <button type="submit"
                class="px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            إضافة
        </button>
    </form>
</div>
