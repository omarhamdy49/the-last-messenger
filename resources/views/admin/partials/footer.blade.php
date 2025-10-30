<footer class="relative z-10 mt-8 transition-colors duration-300"
        :class="darkMode ? 'bg-gray-900/95 backdrop-blur-lg border-t border-gray-800' : 'bg-white border-t border-gray-200'">
    <div class="px-6 py-4">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <!-- Copyright -->
            <div class="text-center md:text-right mb-4 md:mb-0">
                <p class="text-sm transition-colors"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    © {{ date('Y') }} <span class="gradient-text font-bold">آخر الرسل</span> - جميع الحقوق محفوظة
                </p>
            </div>

            <!-- Links -->
            <div class="flex items-center space-x-6 space-x-reverse">
                <a href="#" class="text-sm transition-colors hover:text-brand-accent"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">سياسة الخصوصية</a>
                <a href="#" class="text-sm transition-colors hover:text-brand-accent"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">شروط الاستخدام</a>
                <a href="#" class="text-sm transition-colors hover:text-brand-accent"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المساعدة</a>
            </div>

            <!-- Version -->
            <div class="text-center md:text-left mt-4 md:mt-0">
                <span class="text-xs transition-colors"
                      :class="darkMode ? 'text-gray-500' : 'text-gray-500'">الإصدار 1.0.0</span>
            </div>
        </div>
    </div>
</footer>
