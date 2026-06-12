<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\StaticPage;
use App\Domain\Content\Services\StaticPageService;
use Illuminate\Database\Seeder;

/**
 * StaticPagesSeeder
 *
 * Seeds the about / privacy / terms pages with substantive Arabic content
 * (the site's default and fallback locale) plus English translations.
 * French/Turkish/Urdu rows are intentionally not created — the localized
 * content fallback serves Arabic until translators provide them.
 * Idempotent: pages are upserted by slug.
 */
class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(StaticPageService::class);

        foreach ($this->pages() as $page) {
            /** @var StaticPage $model */
            $model = StaticPage::query()->updateOrCreate(
                ['slug' => $page['slug']],
                ['is_active' => true, 'published_at' => now()]
            );

            foreach ($page['translations'] as $locale => $fields) {
                $model->translations()->updateOrCreate(
                    ['static_page_id' => $model->id, 'locale' => $locale],
                    $fields + ['is_ready' => true]
                );
            }

            $service->flush($page['slug']);
        }

        $this->command?->info('Static pages seeded: '.StaticPage::query()->count().' pages.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about',
                'translations' => [
                    'ar' => [
                        'title' => 'عن المشروع',
                        'seo_title' => 'عن المشروع — خاتَمُ المُرْسَلِين',
                        'seo_description' => 'تعرّف على مشروع خاتَمُ المُرْسَلِين: موسوعة معرفية إسلامية تجمع السيرة النبوية والحديث الشريف والقرآن الكريم بأسلوب عصري وتفاعلي.',
                        'body' => <<<'HTML'
<h2>رسالة المشروع</h2>
<p>«خاتَمُ المُرْسَلِين» مشروع معرفي غير ربحي يهدف إلى تقديم محتوى إسلامي موثوق عن النبي محمد ﷺ بأسلوب عصري وتفاعلي، يجمع بين أصالة المصادر ودقة التحقيق العلمي وسهولة الوصول للقارئ المعاصر بخمس لغات.</p>
<h2>ماذا نقدم؟</h2>
<p>تضم الموسوعة أقساماً متكاملة تغطي جوانب الرسالة المحمدية:</p>
<ul>
<li><strong>السيرة النبوية:</strong> جدول زمني تفاعلي يوثّق أحداث حياة النبي ﷺ من الميلاد إلى الوفاة، مع الغزوات والمواقع الجغرافية على خرائط تفاعلية.</li>
<li><strong>الحديث الشريف:</strong> آلاف الأحاديث من أمهات الكتب مع الأسانيد ودرجات التصحيح والشروح.</li>
<li><strong>القرآن الكريم:</strong> المصحف كاملاً مع التلاوة والتفسير والترجمات.</li>
<li><strong>الصحابة الكرام:</strong> تراجم موثقة لأعلام الصحابة رضوان الله عليهم.</li>
<li><strong>شجرة النسب:</strong> النسب النبوي الشريف وعائلة النبي ﷺ من الأجداد إلى الأحفاد.</li>
<li><strong>البرامج المرئية:</strong> وثائقيات وبرامج تعليمية في السيرة والقرآن والحديث.</li>
</ul>
<h2>منهجنا في التوثيق</h2>
<p>نعتمد في كل ما ننشره على المصادر الأصلية المعتمدة عند أهل العلم، ونحرص على عزو كل نص إلى مصدره، وبيان درجة الأحاديث وفق أحكام أئمة الحديث. يخضع المحتوى للمراجعة العلمية قبل النشر، ونرحب بتنبيهات القراء على أي خطأ عبر صفحة التواصل.</p>
<h2>تواصل معنا</h2>
<p>هذا المشروع في تطور مستمر، ومساهمتكم بالملاحظات والاقتراحات تعيننا على تجويده. يسعدنا تواصلكم عبر صفحة «تواصل معنا».</p>
HTML,
                    ],
                    'en' => [
                        'title' => 'About the Project',
                        'seo_title' => 'About the Project — The Last Messenger',
                        'seo_description' => 'Learn about The Last Messenger: an Islamic knowledge encyclopedia bringing together the Prophetic biography, Hadith, and Quran in a modern, interactive style.',
                        'body' => <<<'HTML'
<h2>Our Mission</h2>
<p>"The Last Messenger" is a non-profit knowledge project that presents trusted Islamic content about the Prophet Muhammad ﷺ in a modern, interactive style — combining authentic sources, scholarly verification, and accessibility for today's reader in five languages.</p>
<h2>What We Offer</h2>
<ul>
<li><strong>Prophetic Biography (Seerah):</strong> an interactive timeline documenting the events of the Prophet's ﷺ life, with battles and locations on interactive maps.</li>
<li><strong>Hadith:</strong> thousands of narrations from the major collections with chains of transmission, gradings, and commentary.</li>
<li><strong>The Quran:</strong> the complete Mushaf with recitation, tafsir, and translations.</li>
<li><strong>The Companions:</strong> verified biographies of the Prophet's companions.</li>
<li><strong>Genealogy:</strong> the Prophetic family tree from ancestors to grandchildren.</li>
<li><strong>Media Programs:</strong> documentaries and educational series on the Seerah, Quran, and Hadith.</li>
</ul>
<h2>Our Verification Method</h2>
<p>Everything we publish relies on the original sources recognized by scholars. Every text is attributed to its source, and hadith gradings follow the rulings of the imams of hadith. Content undergoes scholarly review before publication, and we welcome readers' corrections through the contact page.</p>
<h2>Contact Us</h2>
<p>This project is continuously evolving — your feedback and suggestions help us improve it. We would be glad to hear from you through the "Contact Us" page.</p>
HTML,
                    ],
                ],
            ],
            [
                'slug' => 'privacy',
                'translations' => [
                    'ar' => [
                        'title' => 'سياسة الخصوصية',
                        'seo_title' => 'سياسة الخصوصية — خاتَمُ المُرْسَلِين',
                        'seo_description' => 'سياسة الخصوصية لموقع خاتَمُ المُرْسَلِين: ما البيانات التي نجمعها وكيف نستخدمها ونحميها.',
                        'body' => <<<'HTML'
<h2>مقدمة</h2>
<p>نحترم خصوصية زوار موقع «خاتَمُ المُرْسَلِين» ونلتزم بحماية بياناتهم الشخصية. توضح هذه السياسة ما نجمعه من بيانات وكيف نستخدمها ونحميها.</p>
<h2>البيانات التي نجمعها</h2>
<ul>
<li><strong>بيانات الحساب:</strong> عند التسجيل نجمع الاسم والبريد الإلكتروني فقط، لتمكينك من حفظ المفضلة وتخصيص تجربتك.</li>
<li><strong>بيانات الاستخدام:</strong> صفحات الزيارة ومدة التصفح بصورة مجمّعة لتحسين المحتوى والأداء، دون ربطها بهويتك الشخصية.</li>
<li><strong>رسائل التواصل:</strong> عند مراسلتنا نحتفظ باسمك وبريدك ونص رسالتك للرد عليك.</li>
</ul>
<h2>كيف نستخدم البيانات</h2>
<p>نستخدم البيانات لتشغيل الموقع وتحسين خدماته والرد على استفساراتكم فقط. لا نبيع بياناتك ولا نشاركها مع أي طرف ثالث لأغراض تسويقية.</p>
<h2>ملفات تعريف الارتباط (Cookies)</h2>
<p>نستخدم ملفات تعريف ارتباط ضرورية لتذكر تفضيلاتك (اللغة، الوضع الليلي، جلسة الدخول). يمكنك تعطيلها من إعدادات المتصفح، مع ملاحظة أن بعض الخصائص قد تتأثر.</p>
<h2>حماية البيانات</h2>
<p>نطبق إجراءات تقنية وتنظيمية مناسبة لحماية بياناتك من الوصول غير المصرح به، وتُنقل البيانات عبر اتصالات مشفّرة.</p>
<h2>حقوقك</h2>
<p>يحق لك طلب الاطلاع على بياناتك أو تصحيحها أو حذف حسابك نهائياً في أي وقت عبر صفحة «تواصل معنا».</p>
<h2>تحديث هذه السياسة</h2>
<p>قد نحدّث هذه السياسة من حين لآخر، وننشر أي تعديل في هذه الصفحة مع تاريخ سريانه.</p>
HTML,
                    ],
                    'en' => [
                        'title' => 'Privacy Policy',
                        'seo_title' => 'Privacy Policy — The Last Messenger',
                        'seo_description' => 'The Last Messenger privacy policy: what data we collect, how we use it, and how we protect it.',
                        'body' => <<<'HTML'
<h2>Introduction</h2>
<p>We respect the privacy of visitors to "The Last Messenger" and are committed to protecting their personal data. This policy explains what we collect, how we use it, and how we protect it.</p>
<h2>Data We Collect</h2>
<ul>
<li><strong>Account data:</strong> when you register we collect only your name and email, to enable favorites and personalization.</li>
<li><strong>Usage data:</strong> aggregated page visits and browsing duration to improve content and performance, never linked to your personal identity.</li>
<li><strong>Contact messages:</strong> when you contact us we keep your name, email, and message in order to reply.</li>
</ul>
<h2>How We Use Data</h2>
<p>We use data solely to operate the site, improve its services, and answer your inquiries. We never sell your data or share it with third parties for marketing purposes.</p>
<h2>Cookies</h2>
<p>We use essential cookies to remember your preferences (language, dark mode, login session). You can disable them in your browser settings, though some features may be affected.</p>
<h2>Data Protection</h2>
<p>We apply appropriate technical and organizational measures to protect your data from unauthorized access, and data is transferred over encrypted connections.</p>
<h2>Your Rights</h2>
<p>You may request access to your data, correct it, or permanently delete your account at any time through the "Contact Us" page.</p>
<h2>Updates to This Policy</h2>
<p>We may update this policy from time to time; any changes will be published on this page with their effective date.</p>
HTML,
                    ],
                ],
            ],
            [
                'slug' => 'terms',
                'translations' => [
                    'ar' => [
                        'title' => 'شروط الاستخدام',
                        'seo_title' => 'شروط الاستخدام — خاتَمُ المُرْسَلِين',
                        'seo_description' => 'شروط استخدام موقع خاتَمُ المُرْسَلِين وحقوق الملكية الفكرية وضوابط إعادة النشر.',
                        'body' => <<<'HTML'
<h2>قبول الشروط</h2>
<p>باستخدامك موقع «خاتَمُ المُرْسَلِين» فإنك توافق على الالتزام بهذه الشروط. إذا كنت لا توافق على أي منها فيرجى التوقف عن استخدام الموقع.</p>
<h2>طبيعة المحتوى</h2>
<p>الموقع موسوعة معرفية تعليمية، ومحتواه موجَّه للاستفادة العلمية والدعوية. نبذل وسعنا في التحقق من صحة المحتوى وعزوه إلى مصادره، ومع ذلك لا يغني الموقع عن الرجوع إلى أهل العلم في النوازل والفتاوى الخاصة.</p>
<h2>إعادة النشر والاقتباس</h2>
<ul>
<li>يجوز اقتباس مواد الموقع للأغراض التعليمية والدعوية غير الربحية مع ذكر المصدر ورابط الصفحة.</li>
<li>لا يجوز إعادة نشر المحتوى بصورة تجارية أو تعديل النصوص الشرعية أو نسبتها لغير مصادرها.</li>
<li>النصوص القرآنية والأحاديث النبوية لا يملكها أحد، أما الشروح والترجمات والتصاميم فهي حقوق للمشروع.</li>
</ul>
<h2>حسابات المستخدمين</h2>
<p>أنت مسؤول عن سرية بيانات دخولك وعن أي نشاط يتم عبر حسابك. يحق لنا إيقاف أي حساب يسيء استخدام الموقع أو ينتهك هذه الشروط.</p>
<h2>حدود المسؤولية</h2>
<p>نسعى لاستمرارية الخدمة ودقتها، غير أننا لا نضمن خلو الموقع من الانقطاع أو الخطأ غير المقصود، ولا نتحمل المسؤولية عن أي أضرار ناتجة عن استخدام الموقع بما يخالف هذه الشروط.</p>
<h2>تعديل الشروط</h2>
<p>نحتفظ بحق تعديل هذه الشروط في أي وقت، ويسري التعديل من تاريخ نشره في هذه الصفحة.</p>
HTML,
                    ],
                    'en' => [
                        'title' => 'Terms of Use',
                        'seo_title' => 'Terms of Use — The Last Messenger',
                        'seo_description' => 'The Last Messenger terms of use, intellectual property rights, and republishing guidelines.',
                        'body' => <<<'HTML'
<h2>Acceptance of Terms</h2>
<p>By using "The Last Messenger" you agree to be bound by these terms. If you do not agree with any of them, please discontinue using the site.</p>
<h2>Nature of the Content</h2>
<p>The site is an educational knowledge encyclopedia intended for scholarly and outreach purposes. We strive to verify content and attribute it to its sources; nevertheless, the site does not substitute consulting qualified scholars for specific religious rulings.</p>
<h2>Republishing and Quotation</h2>
<ul>
<li>Site material may be quoted for non-profit educational purposes with attribution and a link to the page.</li>
<li>Commercial republishing, altering religious texts, or misattributing them is not permitted.</li>
<li>Quranic texts and Prophetic narrations belong to no one; commentaries, translations, and designs are the project's rights.</li>
</ul>
<h2>User Accounts</h2>
<p>You are responsible for the confidentiality of your credentials and any activity under your account. We may suspend any account that misuses the site or violates these terms.</p>
<h2>Limitation of Liability</h2>
<p>We strive for service continuity and accuracy, but do not guarantee the site is free of interruption or unintended error, and accept no liability for damages arising from use of the site contrary to these terms.</p>
<h2>Changes to the Terms</h2>
<p>We reserve the right to amend these terms at any time; amendments take effect from their publication date on this page.</p>
HTML,
                    ],
                ],
            ],
        ];
    }
}
