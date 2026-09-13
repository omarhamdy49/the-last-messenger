{{--
    Article Share Modal Component

    Displays social sharing modal with:
    - Twitter, Facebook, WhatsApp sharing
    - Copy link functionality

    Requires articleReader Alpine.js component context
--}}

{{-- Share Modal --}}
<div x-show="showShare" x-cloak @click.self="showShare = false" @keydown.escape.window="showShare = false"
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm no-print"
     x-transition.opacity>
    <div class="card-premium p-7 max-w-md w-full shadow-2xl" @click.stop x-transition.scale.origin.center>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('articles.public.share_article') }}</h3>
            <button @click="showShare = false" aria-label="{{ __('articles.public.close') }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-[#0f3460]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <button @click="shareTwitter()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-[#1DA1F2] text-white hover:scale-105 transition-transform">
                <i data-lucide="twitter" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.share_twitter') }}</span>
            </button>
            <button @click="shareFacebook()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-[#1877F2] text-white hover:scale-105 transition-transform">
                <i data-lucide="facebook" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.share_facebook') }}</span>
            </button>
            <button @click="shareWhatsapp()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-[#25D366] text-white hover:scale-105 transition-transform">
                <i data-lucide="message-circle" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.share_whatsapp') }}</span>
            </button>
            <button @click="copyLink()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:scale-105 transition-transform">
                <i data-lucide="link" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.copy_link') }}</span>
            </button>
        </div>
    </div>
</div>
