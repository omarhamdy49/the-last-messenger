{{--
    Public Footer Component

    Usage:
    <x-public.footer />
--}}

<footer {{ $attributes->merge(['class' => 'bg-gray-950 text-gray-400 mt-16']) }}>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Main Footer Content --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">

            {{-- About Section --}}
            <div>
                <h3 class="text-xl font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent mb-4">
                    {{ __('site.name') }}
                </h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    {{ __('site.footer_description') }}
                </p>

                {{-- Newsletter Subscription --}}
                <div class="mt-6">
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="email"
                               name="email"
                               placeholder="{{ __('site.email_placeholder') }}"
                               required
                               class="flex-1 px-3 py-2 rounded-lg bg-gray-900 border border-gray-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#0056b3]">
                        <button type="submit"
                                class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg text-sm font-semibold hover:scale-105 transition-transform">
                            {{ __('site.subscribe') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="font-semibold text-white mb-4">{{ __('site.quick_links') }}</h4>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="{{ route('about') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="chevron-right"></i>
                            {{ __('site.about') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('contact') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="chevron-right"></i>
                            {{ __('site.contact') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('privacy') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="chevron-right"></i>
                            {{ __('site.privacy_policy') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('terms') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="chevron-right"></i>
                            {{ __('site.terms_of_service') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faq') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="chevron-right"></i>
                            {{ __('site.faq') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Modules --}}
            <div>
                <h4 class="font-semibold text-white mb-4">{{ __('site.modules') }}</h4>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="{{ route('seerah.index') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="book-open"></i>
                            {{ __('site.seerah') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hadith.index') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="scroll-text"></i>
                            {{ __('site.hadith') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('media.index') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="play-circle"></i>
                            {{ __('site.media') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('quran.index') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="book"></i>
                            {{ __('site.quran') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('genealogy.index') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="network"></i>
                            {{ __('site.genealogy') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sahaba.index') }}"
                           class="hover:text-[#64CCC5] transition flex items-center gap-2">
                            <i class="w-3 h-3" data-lucide="users"></i>
                            {{ __('site.sahaba') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Contact & Social --}}
            <div>
                <h4 class="font-semibold text-white mb-4">{{ __('site.follow_us') }}</h4>

                {{-- Social Media Links --}}
                <div class="flex gap-3 mb-6">
                    <a href="{{ config('social.facebook') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-[#0056b3] transition-all duration-300 hover:scale-110"
                       aria-label="Facebook">
                        <i class="w-5 h-5" data-lucide="facebook"></i>
                    </a>
                    <a href="{{ config('social.twitter') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-[#0056b3] transition-all duration-300 hover:scale-110"
                       aria-label="Twitter">
                        <i class="w-5 h-5" data-lucide="twitter"></i>
                    </a>
                    <a href="{{ config('social.youtube') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-[#0056b3] transition-all duration-300 hover:scale-110"
                       aria-label="YouTube">
                        <i class="w-5 h-5" data-lucide="youtube"></i>
                    </a>
                    <a href="{{ config('social.instagram') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-[#0056b3] transition-all duration-300 hover:scale-110"
                       aria-label="Instagram">
                        <i class="w-5 h-5" data-lucide="instagram"></i>
                    </a>
                </div>

                {{-- Contact Info --}}
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="w-4 h-4 text-[#64CCC5]" data-lucide="mail"></i>
                        <a href="mailto:{{ config('contact.email') }}"
                           class="hover:text-[#64CCC5] transition">
                            {{ config('contact.email') }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="w-4 h-4 text-[#64CCC5]" data-lucide="phone"></i>
                        <a href="tel:{{ config('contact.phone') }}"
                           class="hover:text-[#64CCC5] transition">
                            {{ config('contact.phone') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="pt-8 border-t border-gray-900 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-sm text-center md:text-start">
                &copy; {{ date('Y') }} {{ __('site.name') }}. {{ __('site.all_rights_reserved') }}
            </p>

            {{-- Language Selector --}}
            <div class="flex items-center gap-2">
                @foreach(['ar' => 'عربي', 'en' => 'EN', 'tr' => 'TR', 'fr' => 'FR', 'ur' => 'اردو'] as $locale => $label)
                <a href="{{ route('locale.switch', $locale) }}"
                   class="px-3 py-1 rounded text-xs font-medium transition {{ app()->getLocale() === $locale ? 'bg-[#0056b3] text-white' : 'text-gray-500 hover:text-white' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>

        {{-- Back to Top Button --}}
        <button @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="fixed bottom-8 end-8 w-12 h-12 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-full shadow-2xl hover:scale-110 transition-transform flex items-center justify-center z-40"
                x-show="window.pageYOffset > 300"
                x-transition
                aria-label="{{ __('site.back_to_top') }}">
            <i class="w-5 h-5" data-lucide="arrow-up"></i>
        </button>
    </div>
</footer>
