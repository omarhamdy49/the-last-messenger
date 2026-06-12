<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\Faq;
use App\Domain\Content\Services\FaqService;
use Illuminate\Database\Seeder;

/**
 * FaqSeeder
 *
 * Seeds real platform FAQs in Arabic (default/fallback locale) and English.
 * Idempotent: FAQs are matched by their stable sort_order within a category.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $index => $faq) {
            $sortOrder = ($index + 1) * 10;

            /** @var Faq $model */
            $model = Faq::query()->updateOrCreate(
                ['category' => $faq['category'], 'sort_order' => $sortOrder],
                ['is_active' => true]
            );

            $model->syncTranslations([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
            ]);
        }

        app(FaqService::class)->flush();

        $this->command?->info('FAQs seeded: '.Faq::query()->count().' questions.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function faqs(): array
    {
        return [
            [
                'category' => 'general',
                'question' => [
                    'ar' => 'ما هو مشروع «خاتَمُ المُرْسَلِين»؟',
                    'en' => 'What is "The Last Messenger" project?',
                ],
                'answer' => [
                    'ar' => 'هو موسوعة معرفية إسلامية غير ربحية تجمع السيرة النبوية والحديث الشريف والقرآن الكريم وتراجم الصحابة وشجرة النسب النبوي والبرامج المرئية في منصة واحدة بأسلوب عصري وتفاعلي وبخمس لغات.',
                    'en' => 'It is a non-profit Islamic knowledge encyclopedia bringing together the Prophetic biography, Hadith, the Quran, companions\' biographies, the Prophetic family tree, and media programs on one modern, interactive platform in five languages.',
                ],
            ],
            [
                'category' => 'general',
                'question' => [
                    'ar' => 'هل استخدام الموقع مجاني؟',
                    'en' => 'Is the site free to use?',
                ],
                'answer' => [
                    'ar' => 'نعم، جميع محتويات الموقع متاحة مجاناً للجميع، ولا توجد اشتراكات مدفوعة أو محتوى مقيد.',
                    'en' => 'Yes — all content is freely available to everyone, with no paid subscriptions or restricted material.',
                ],
            ],
            [
                'category' => 'content',
                'question' => [
                    'ar' => 'ما مصادر الأحاديث المنشورة وكيف يتم التحقق منها؟',
                    'en' => 'What are the sources of the published hadiths, and how are they verified?',
                ],
                'answer' => [
                    'ar' => 'تُنقل الأحاديث من أمهات كتب الحديث كصحيحي البخاري ومسلم وغيرهما، مع ذكر الكتاب ورقم الحديث ودرجته وفق أحكام أئمة الحديث، ويخضع المحتوى للمراجعة العلمية قبل النشر.',
                    'en' => 'Hadiths are taken from the major collections such as Sahih al-Bukhari and Sahih Muslim, citing the book, hadith number, and grading according to the imams of hadith. Content undergoes scholarly review before publication.',
                ],
            ],
            [
                'category' => 'content',
                'question' => [
                    'ar' => 'هل يمكنني الاعتماد على الموقع في الفتوى؟',
                    'en' => 'Can I rely on the site for religious rulings (fatwa)?',
                ],
                'answer' => [
                    'ar' => 'الموقع موسوعة تعليمية توثيقية وليس جهة إفتاء؛ فننصح بالرجوع إلى أهل العلم الموثوقين في المسائل والنوازل الخاصة.',
                    'en' => 'The site is an educational reference, not a fatwa authority; for specific rulings we advise consulting qualified, trusted scholars.',
                ],
            ],
            [
                'category' => 'content',
                'question' => [
                    'ar' => 'كيف أبلغ عن خطأ في المحتوى؟',
                    'en' => 'How do I report an error in the content?',
                ],
                'answer' => [
                    'ar' => 'يسعدنا تلقي التنبيهات عبر صفحة «تواصل معنا» مع ذكر رابط الصفحة ووصف الخطأ، وسيراجعه الفريق العلمي ويصححه بعد التحقق.',
                    'en' => 'Please use the "Contact Us" page, including the page link and a description of the error. Our review team will verify and correct it.',
                ],
            ],
            [
                'category' => 'languages',
                'question' => [
                    'ar' => 'ما اللغات المتوفرة في الموقع؟',
                    'en' => 'Which languages does the site support?',
                ],
                'answer' => [
                    'ar' => 'يتوفر الموقع بخمس لغات: العربية (اللغة الأساسية)، والإنجليزية، والفرنسية، والتركية، والأردية، مع دعم كامل للكتابة من اليمين إلى اليسار. وإذا لم تتوفر ترجمة لمحتوى ما تُعرض النسخة العربية.',
                    'en' => 'The site is available in five languages: Arabic (the primary language), English, French, Turkish, and Urdu, with full right-to-left support. Where a translation is unavailable, the Arabic version is shown.',
                ],
            ],
            [
                'category' => 'account',
                'question' => [
                    'ar' => 'هل أحتاج إلى حساب لتصفح الموقع؟',
                    'en' => 'Do I need an account to browse the site?',
                ],
                'answer' => [
                    'ar' => 'لا، التصفح متاح للجميع دون تسجيل. التسجيل اختياري ويتيح لك حفظ المفضلة ومتابعة تقدمك في البرامج المرئية وتخصيص تجربتك.',
                    'en' => 'No — browsing is open to everyone without registration. An optional account lets you save favorites, track your progress in media programs, and personalize your experience.',
                ],
            ],
            [
                'category' => 'account',
                'question' => [
                    'ar' => 'كيف أحذف حسابي وبياناتي؟',
                    'en' => 'How do I delete my account and data?',
                ],
                'answer' => [
                    'ar' => 'يمكنك طلب حذف حسابك وبياناتك نهائياً في أي وقت عبر صفحة «تواصل معنا»، وسننفذ الطلب وفق سياسة الخصوصية.',
                    'en' => 'You can request permanent deletion of your account and data at any time through the "Contact Us" page, and we will process it according to the privacy policy.',
                ],
            ],
            [
                'category' => 'general',
                'question' => [
                    'ar' => 'هل يمكنني إعادة نشر محتوى الموقع؟',
                    'en' => 'May I republish the site\'s content?',
                ],
                'answer' => [
                    'ar' => 'يجوز الاقتباس للأغراض التعليمية والدعوية غير الربحية مع ذكر المصدر ورابط الصفحة. أما إعادة النشر التجاري فيتطلب إذناً مسبقاً، وتفاصيل ذلك في صفحة شروط الاستخدام.',
                    'en' => 'Quotation for non-profit educational purposes is permitted with attribution and a link to the page. Commercial republishing requires prior permission — see the Terms of Use page for details.',
                ],
            ],
        ];
    }
}
