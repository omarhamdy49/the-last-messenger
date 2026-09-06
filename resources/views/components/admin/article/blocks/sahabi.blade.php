{{--
    Sahabi Block Component
    Companion profile block with name and kunya
--}}

<div x-show="block.type === 'sahabi'" class="p-4">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
             :class="darkMode ? 'bg-amber-900/50' : 'bg-amber-100'">
            <i data-lucide="user" class="w-6 h-6 text-amber-600"></i>
        </div>
        <div>
            <p class="font-bold text-base" :class="darkMode ? 'text-amber-200' : 'text-amber-900'" x-text="block.name"></p>
            <p class="text-sm" :class="darkMode ? 'text-amber-400' : 'text-amber-600'" x-text="block.kunya" x-show="block.kunya"></p>
        </div>
    </div>
</div>
