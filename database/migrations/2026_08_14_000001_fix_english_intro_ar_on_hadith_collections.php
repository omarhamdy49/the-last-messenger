<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Sunnah.com API serves its ENGLISH shortIntro under lang=ar, so the
 * import filled `intro_ar` with English text for every collection. Replace it
 * with curated Arabic intros. Guarded per row: only rows whose current
 * intro_ar is empty or mostly-Latin are touched, so a real Arabic intro an
 * editor wrote is never clobbered.
 */
return new class extends Migration
{
    private const INTROS = [
        'bukhari' => 'صحيح البخاري للإمام محمد بن إسماعيل البخاري (ت 256هـ)، أصح كتب الحديث عند جمهور علماء المسلمين، جمع فيه أصح ما ورد عن النبي ﷺ، ويضم أكثر من 7500 حديث بالمكرر موزعة على 97 كتابًا.',
        'muslim' => 'صحيح مسلم للإمام مسلم بن الحجاج النيسابوري (ت 261هـ)، ثاني أصح كتب الحديث بعد صحيح البخاري، امتاز بحسن ترتيبه وجمعه طرق الحديث في موضع واحد.',
        'nasai' => 'سنن النسائي (المجتبى) للإمام أحمد بن شعيب النسائي (ت 303هـ)، أحد الكتب الستة، اشتهر بدقة النقد وشدة التحري في الرجال.',
        'abudawud' => 'سنن أبي داود للإمام سليمان بن الأشعث السجستاني (ت 275هـ)، أحد الكتب الستة، جمع فيه أحاديث الأحكام حتى قيل إنه يكفي الفقيه في بابه.',
        'tirmidhi' => 'جامع الترمذي للإمام أبي عيسى محمد بن عيسى الترمذي (ت 279هـ)، أحد الكتب الستة، تميز ببيان درجة الحديث وذكر مذاهب الفقهاء والعمل به.',
        'ibnmajah' => 'سنن ابن ماجه للإمام محمد بن يزيد القزويني (ت 273هـ)، سادس الكتب الستة، انفرد بزوائد كثيرة على الكتب الخمسة.',
        'ahmad' => 'مسند الإمام أحمد بن حنبل (ت 241هـ)، من أوسع دواوين السنة، رُتبت أحاديثه على مسانيد الصحابة رضي الله عنهم، ويضم نحو ثلاثين ألف حديث.',
        'shamail' => 'الشمائل المحمدية للإمام الترمذي (ت 279هـ)، كتاب جمع أوصاف النبي ﷺ الخَلقية والخُلقية وهديه في شؤون حياته كلها.',
        'riyadussalihin' => 'رياض الصالحين للإمام يحيى بن شرف النووي (ت 676هـ)، من أشهر كتب الترغيب والآداب، جمع فيه أحاديث الرقائق والأخلاق مرتبة على الأبواب.',
        'nawawi40' => 'الأربعون النووية للإمام النووي (ت 676هـ)، أحاديث جامعة لقواعد الدين وأصوله، عليها مدار الإسلام، مع زيادات ابن رجب.',
        'qudsi40' => 'أربعون حديثًا قدسيًا مختارة مما يرويه النبي ﷺ عن ربه عز وجل، في الرقائق والرجاء وسعة رحمة الله تعالى.',
        'shahwaliullah40' => 'الأربعون حديثًا التي اختارها الإمام شاه ولي الله الدهلوي (ت 1176هـ)، أحاديث قصيرة جامعة في الفضائل والأعمال الصالحة.',
    ];

    public function up(): void
    {
        foreach (self::INTROS as $slug => $intro) {
            $current = DB::table('hadith_collections')->where('slug', $slug)->value('intro_ar');

            if ($current !== null && $this->isMostlyArabic($current)) {
                continue;
            }

            DB::table('hadith_collections')->where('slug', $slug)->update(['intro_ar' => $intro]);
        }

        \App\Domain\Hadith\Services\HadithBrowseService::forget();
    }

    public function down(): void
    {
        // Data fix; nothing sensible to restore.
    }

    private function isMostlyArabic(string $text): bool
    {
        return preg_match_all('/\p{Arabic}/u', $text) > preg_match_all('/\p{Latin}/u', $text);
    }
};
