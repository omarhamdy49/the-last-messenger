<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\Generation;
use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use Illuminate\Database\Seeder;

/**
 * The household of the Prophet ﷺ — wives, children, grandchildren, and the
 * uncles, aunts and cousins of Banu Hashim.
 *
 * WHY THIS EXISTS
 * ---------------
 * ProphetLineageSeeder seeds the *ascent* — the paternal chain up to Adnan —
 * and nothing else. Of 7,830 rows in `muslims`, only those 24 carried a
 * `father_id`. So the public tree had a spine and no family: the Prophet ﷺ
 * showed zero children, zero wives, zero relatives, because none of them
 * existed as linked records. Name-matching rows like "خديجة" in that table are
 * unpublished narrator entries from the hadith import, not family records.
 *
 * HOW RELATIONS ARE MODELLED
 * --------------------------
 * Descent is the only thing stored explicitly: `father_id` / `mother_id`.
 * Everything lateral is *derived* from it —
 *   - siblings  = share a father (or mother)
 *   - uncles    = the father's brothers, i.e. the other sons of عبد المطلب
 *   - cousins   = the uncles' children
 * so the uncles here are seeded as sons of عبد المطلب rather than as `uncle`
 * relationship rows. One edge in the right place produces every lateral
 * relation for free and cannot contradict itself; a hand-entered `uncle` row
 * can, and would need a matching `nephew` row to stay consistent.
 *
 * `muslim_relationships` is therefore used only for marriage, which genuinely
 * is not derivable from descent.
 *
 * SOURCES AND CONTESTED POINTS
 * ----------------------------
 * The mainstream seerah record — Ibn Hisham's Sira, Ibn Sa'd's Tabaqat, and
 * Ibn Kathir. Where the sources disagree, this seeder takes the majority
 * position and says so in the record's own `notes`, rather than silently
 * picking a side:
 *   - الطيب and الطاهر are treated as epithets of عبد الله, not as two further
 *     sons — the position of the majority, though some lists count them
 *     separately and arrive at more than three sons.
 *   - محسن بن علي is omitted: reported, but not firmly enough to assert here.
 *   - The list of عبد المطلب's sons varies between sources; the ones seeded are
 *     those the standard lists agree on.
 *   - مارية القبطية is recorded as إبراهيم's mother — a fact of descent — and
 *     deliberately given no `spouse` row, since the majority do not count her
 *     among أمهات المؤمنين.
 *
 * DATES are Hijri years; negative means before the Hijra. Only years that the
 * sources broadly agree on are recorded. Everything else is left null rather
 * than dressed up as a record — a guessed date is indistinguishable from an
 * attested one once it is in the column.
 *
 * IDEMPOTENCE: `firstOrCreate` on slug, never `updateOrCreate`, and links are
 * only written where the column is still empty. An editor's correction has to
 * survive a re-seed — that is the whole point of this being content.
 */
final class ProphetFamilySeeder extends Seeder
{
    private const PROPHET = 'muhammad';

    private const ABD_AL_MUTTALIB = 'abd-al-muttalib';

    /**
     * أمهات المؤمنين. `spouse` rows are written from the Prophet ﷺ to each.
     *
     * @var list<array{slug:string,ar:string,en:string,death?:int,kunya_ar?:string,notes?:string}>
     */
    private const WIVES = [
        ['slug' => 'khadijah-bint-khuwaylid', 'ar' => 'خديجة بنت خويلد', 'en' => 'Khadijah bint Khuwaylid', 'death' => -3, 'kunya_ar' => 'أم المؤمنين', 'notes' => 'أول من آمن به ﷺ، وأم أولاده جميعاً إلا إبراهيم. لم يتزوج عليها حتى توفيت.'],
        ['slug' => 'sawda-bint-zamaa', 'ar' => 'سودة بنت زمعة', 'en' => 'Sawda bint Zamʿa', 'death' => 54, 'kunya_ar' => 'أم المؤمنين'],
        ['slug' => 'aisha-bint-abi-bakr', 'ar' => 'عائشة بنت أبي بكر', 'en' => 'Aisha bint Abi Bakr', 'death' => 58, 'kunya_ar' => 'أم المؤمنين', 'notes' => 'أفقه نساء الأمة وأكثر أمهات المؤمنين رواية للحديث.'],
        ['slug' => 'hafsa-bint-umar-um-al-muminin', 'ar' => 'حفصة بنت عمر', 'en' => 'Hafsa bint Umar', 'death' => 45, 'kunya_ar' => 'أم المؤمنين', 'notes' => 'حُفظ عندها المصحف بعد جمعه في عهد أبي بكر رضي الله عنه.'],
        ['slug' => 'zaynab-bint-khuzayma', 'ar' => 'زينب بنت خزيمة', 'en' => 'Zaynab bint Khuzayma', 'death' => 4, 'kunya_ar' => 'أم المساكين', 'notes' => 'لقّبت بأم المساكين لكثرة إطعامها إياهم. لم تلبث عنده ﷺ إلا أشهراً.'],
        ['slug' => 'umm-salama-hind-bint-abi-umayya', 'ar' => 'أم سلمة هند بنت أبي أمية', 'en' => 'Umm Salama Hind bint Abi Umayya', 'death' => 62, 'kunya_ar' => 'أم المؤمنين', 'notes' => 'آخر أمهات المؤمنين وفاةً على قول الأكثرين.'],
        ['slug' => 'zaynab-bint-jahsh', 'ar' => 'زينب بنت جحش', 'en' => 'Zaynab bint Jahsh', 'death' => 20, 'kunya_ar' => 'أم المؤمنين'],
        ['slug' => 'juwayriya-bint-al-harith', 'ar' => 'جويرية بنت الحارث', 'en' => 'Juwayriya bint al-Harith', 'death' => 56, 'kunya_ar' => 'أم المؤمنين'],
        ['slug' => 'umm-habiba-ramla-bint-abi-sufyan', 'ar' => 'أم حبيبة رملة بنت أبي سفيان', 'en' => 'Umm Habiba Ramla bint Abi Sufyan', 'death' => 44, 'kunya_ar' => 'أم المؤمنين'],
        ['slug' => 'safiyya-bint-huyayy', 'ar' => 'صفية بنت حيي', 'en' => 'Safiyya bint Huyayy', 'death' => 50, 'kunya_ar' => 'أم المؤمنين'],
        ['slug' => 'maymuna-bint-al-harith', 'ar' => 'ميمونة بنت الحارث', 'en' => 'Maymuna bint al-Harith', 'death' => 51, 'kunya_ar' => 'أم المؤمنين', 'notes' => 'آخر من تزوّج ﷺ من النساء.'],
    ];

    /**
     * The children. All are عبد الله's grandchildren through the Prophet ﷺ;
     * every one is Khadijah's except إبراهيم.
     *
     * @var list<array{slug:string,ar:string,en:string,gender:string,mother:string,death?:int,order:int,notes?:string}>
     */
    private const CHILDREN = [
        ['slug' => 'al-qasim-ibn-muhammad', 'ar' => 'القاسم بن محمد', 'en' => 'al-Qasim ibn Muhammad', 'gender' => 'male', 'mother' => 'khadijah-bint-khuwaylid', 'order' => 1, 'notes' => 'أكبر ولده ﷺ، وبه كان يُكنّى أبا القاسم. توفي صغيراً بمكة قبل الهجرة.'],
        ['slug' => 'zaynab-bint-muhammad', 'ar' => 'زينب بنت محمد', 'en' => 'Zaynab bint Muhammad', 'gender' => 'female', 'mother' => 'khadijah-bint-khuwaylid', 'death' => 8, 'order' => 2, 'notes' => 'أكبر بناته ﷺ، وزوج أبي العاص بن الربيع.'],
        ['slug' => 'ruqayya-bint-muhammad', 'ar' => 'رقية بنت محمد', 'en' => 'Ruqayya bint Muhammad', 'gender' => 'female', 'mother' => 'khadijah-bint-khuwaylid', 'death' => 2, 'order' => 3, 'notes' => 'زوج عثمان بن عفان، وتوفيت والنبي ﷺ في بدر.'],
        ['slug' => 'umm-kulthum-bint-muhammad', 'ar' => 'أم كلثوم بنت محمد', 'en' => 'Umm Kulthum bint Muhammad', 'gender' => 'female', 'mother' => 'khadijah-bint-khuwaylid', 'death' => 9, 'order' => 4, 'notes' => 'تزوّجها عثمان بن عفان بعد وفاة أختها رقية، فسُمّي ذا النورين.'],
        ['slug' => 'fatima-al-zahra', 'ar' => 'فاطمة الزهراء', 'en' => 'Fatima al-Zahra', 'gender' => 'female', 'mother' => 'khadijah-bint-khuwaylid', 'death' => 11, 'order' => 5, 'notes' => 'سيدة نساء أهل الجنة، وزوج علي بن أبي طالب. أول أهل بيته ﷺ لحوقاً به، ومنها عقبه.'],
        ['slug' => 'abdullah-ibn-muhammad', 'ar' => 'عبد الله بن محمد', 'en' => 'Abdullah ibn Muhammad', 'gender' => 'male', 'mother' => 'khadijah-bint-khuwaylid', 'order' => 6, 'notes' => 'يُلقّب بالطيّب والطاهر. وذهب بعضهم إلى أن الطيّب والطاهر ولدان آخران، والأكثر على أنهما لقبان له. توفي صغيراً بمكة.'],
        ['slug' => 'ibrahim-ibn-muhammad', 'ar' => 'إبراهيم بن محمد', 'en' => 'Ibrahim ibn Muhammad', 'gender' => 'male', 'mother' => 'mariya-al-qibtiyya', 'death' => 10, 'order' => 7, 'notes' => 'وُلد بالمدينة وتوفي وهو ابن نحو ثمانية عشر شهراً، وفيه قال ﷺ: «إن العين لتدمع وإن القلب ليحزن».'],
    ];

    /**
     * The grandchildren, keyed to the couple they belong to.
     *
     * @var list<array{slug:string,ar:string,en:string,gender:string,father:string,mother:string,death?:int,order:int,notes?:string}>
     */
    private const GRANDCHILDREN = [
        ['slug' => 'al-hasan-ibn-ali', 'ar' => 'الحسن بن علي', 'en' => 'al-Hasan ibn Ali', 'gender' => 'male', 'father' => 'ali-ibn-abi-talib', 'mother' => 'fatima-al-zahra', 'death' => 50, 'order' => 1, 'notes' => 'سيد شباب أهل الجنة، وبتنازله عن الخلافة حُقن دم المسلمين في عام الجماعة.'],
        ['slug' => 'al-husayn-ibn-ali', 'ar' => 'الحسين بن علي', 'en' => 'al-Husayn ibn Ali', 'gender' => 'male', 'father' => 'ali-ibn-abi-talib', 'mother' => 'fatima-al-zahra', 'death' => 61, 'order' => 2, 'notes' => 'سيد شباب أهل الجنة، استُشهد بكربلاء.'],
        ['slug' => 'zaynab-bint-ali', 'ar' => 'زينب بنت علي', 'en' => 'Zaynab bint Ali', 'gender' => 'female', 'father' => 'ali-ibn-abi-talib', 'mother' => 'fatima-al-zahra', 'order' => 3],
        ['slug' => 'umm-kulthum-bint-ali', 'ar' => 'أم كلثوم بنت علي', 'en' => 'Umm Kulthum bint Ali', 'gender' => 'female', 'father' => 'ali-ibn-abi-talib', 'mother' => 'fatima-al-zahra', 'order' => 4],
        ['slug' => 'umama-bint-abi-al-as', 'ar' => 'أمامة بنت أبي العاص', 'en' => 'Umama bint Abi al-As', 'gender' => 'female', 'father' => 'abu-al-as-ibn-al-rabi', 'mother' => 'zaynab-bint-muhammad', 'order' => 1, 'notes' => 'كان ﷺ يحملها في الصلاة. تزوّجها علي بن أبي طالب بعد وفاة خالتها فاطمة.'],
        ['slug' => 'ali-ibn-abi-al-as', 'ar' => 'علي بن أبي العاص', 'en' => 'Ali ibn Abi al-As', 'gender' => 'male', 'father' => 'abu-al-as-ibn-al-rabi', 'mother' => 'zaynab-bint-muhammad', 'order' => 2, 'notes' => 'توفي صغيراً.'],
        ['slug' => 'abdullah-ibn-uthman', 'ar' => 'عبد الله بن عثمان', 'en' => 'Abdullah ibn Uthman', 'gender' => 'male', 'father' => 'uthman-ibn-affan', 'mother' => 'ruqayya-bint-muhammad', 'order' => 1, 'notes' => 'توفي صغيراً بالمدينة، وبه كان يُكنّى عثمان رضي الله عنه.'],
    ];

    /**
     * Sons of عبد المطلب — the Prophet's ﷺ paternal uncles. Seeded as his
     * father's brothers, which is what makes them uncles; no `uncle` row is
     * written, because the edge is already there.
     *
     * @var list<array{slug:string,ar:string,en:string,death?:int,order:int,notes?:string}>
     */
    private const UNCLES = [
        ['slug' => 'al-harith-ibn-abd-al-muttalib', 'ar' => 'الحارث بن عبد المطلب', 'en' => 'al-Harith ibn Abd al-Muttalib', 'order' => 1, 'notes' => 'أكبر ولد عبد المطلب، وبه كان يُكنّى.'],
        ['slug' => 'al-zubayr-ibn-abd-al-muttalib', 'ar' => 'الزبير بن عبد المطلب', 'en' => 'al-Zubayr ibn Abd al-Muttalib', 'order' => 2, 'notes' => 'من أشراف قريش، وشهد حلف الفضول.'],
        ['slug' => 'abu-talib-ibn-abd-al-muttalib', 'ar' => 'أبو طالب بن عبد المطلب', 'en' => 'Abu Talib ibn Abd al-Muttalib', 'death' => -3, 'order' => 3, 'notes' => 'كافل النبي ﷺ بعد جدّه، وحاميه من قريش. توفي قبل الهجرة في عام الحزن. واسمه عبد مناف.'],
        ['slug' => 'hamza-ibn-abd-al-muttalib', 'ar' => 'حمزة بن عبد المطلب', 'en' => 'Hamza ibn Abd al-Muttalib', 'death' => 3, 'order' => 4, 'notes' => 'أسد الله وأسد رسوله، وسيد الشهداء. استُشهد يوم أحد.'],
        ['slug' => 'abu-lahab-ibn-abd-al-muttalib', 'ar' => 'أبو لهب بن عبد المطلب', 'en' => 'Abu Lahab ibn Abd al-Muttalib', 'order' => 5, 'notes' => 'واسمه عبد العزّى. اشتد في عداوة النبي ﷺ، ونزلت فيه سورة المسد.'],
        ['slug' => 'al-abbas-ibn-abd-al-muttalib', 'ar' => 'العباس بن عبد المطلب', 'en' => 'al-Abbas ibn Abd al-Muttalib', 'death' => 32, 'order' => 6, 'notes' => 'عمّ النبي ﷺ، وإليه يُنسب بنو العباس.'],
        ['slug' => 'al-muqawwim-ibn-abd-al-muttalib', 'ar' => 'المقوّم بن عبد المطلب', 'en' => 'al-Muqawwim ibn Abd al-Muttalib', 'order' => 7],
        ['slug' => 'dirar-ibn-abd-al-muttalib', 'ar' => 'ضرار بن عبد المطلب', 'en' => 'Dirar ibn Abd al-Muttalib', 'order' => 8],
    ];

    /**
     * Daughters of عبد المطلب — the Prophet's ﷺ paternal aunts.
     *
     * @var list<array{slug:string,ar:string,en:string,order:int,notes?:string}>
     */
    private const AUNTS = [
        ['slug' => 'safiyya-bint-abd-al-muttalib', 'ar' => 'صفية بنت عبد المطلب', 'en' => 'Safiyya bint Abd al-Muttalib', 'order' => 1, 'notes' => 'أسلمت وهاجرت، وهي أم الزبير بن العوّام.'],
        ['slug' => 'atika-bint-abd-al-muttalib', 'ar' => 'عاتكة بنت عبد المطلب', 'en' => 'Atika bint Abd al-Muttalib', 'order' => 2],
        ['slug' => 'arwa-bint-abd-al-muttalib', 'ar' => 'أروى بنت عبد المطلب', 'en' => 'Arwa bint Abd al-Muttalib', 'order' => 3],
        ['slug' => 'umayma-bint-abd-al-muttalib', 'ar' => 'أميمة بنت عبد المطلب', 'en' => 'Umayma bint Abd al-Muttalib', 'order' => 4, 'notes' => 'أم زينب بنت جحش أم المؤمنين.'],
        ['slug' => 'barra-bint-abd-al-muttalib', 'ar' => 'برّة بنت عبد المطلب', 'en' => 'Barra bint Abd al-Muttalib', 'order' => 5],
        ['slug' => 'umm-hakim-al-bayda-bint-abd-al-muttalib', 'ar' => 'أم حكيم البيضاء بنت عبد المطلب', 'en' => 'Umm Hakim al-Bayda bint Abd al-Muttalib', 'order' => 6, 'notes' => 'توأمة عبد الله والد النبي ﷺ.'],
    ];

    /**
     * The cousins — children of the uncles and aunts above. Linked to their own
     * parent, so the cousin relation to the Prophet ﷺ falls out of the graph.
     *
     * @var list<array{slug:string,ar:string,en:string,gender:string,father?:string,mother?:string,death?:int,order:int,notes?:string}>
     */
    private const COUSINS = [
        // بنو أبي طالب
        ['slug' => 'talib-ibn-abi-talib', 'ar' => 'طالب بن أبي طالب', 'en' => 'Talib ibn Abi Talib', 'gender' => 'male', 'father' => 'abu-talib-ibn-abd-al-muttalib', 'order' => 1],
        ['slug' => 'aqil-ibn-abi-talib', 'ar' => 'عقيل بن أبي طالب', 'en' => 'Aqil ibn Abi Talib', 'gender' => 'male', 'father' => 'abu-talib-ibn-abd-al-muttalib', 'death' => 60, 'order' => 2, 'notes' => 'أعلم قريش بأنسابها.'],
        ['slug' => 'jafar-ibn-abi-talib', 'ar' => 'جعفر بن أبي طالب', 'en' => 'Jafar ibn Abi Talib', 'gender' => 'male', 'father' => 'abu-talib-ibn-abd-al-muttalib', 'death' => 8, 'order' => 3, 'notes' => 'ذو الجناحين، استُشهد يوم مؤتة.'],
        ['slug' => 'ali-ibn-abi-talib', 'ar' => 'علي بن أبي طالب', 'en' => 'Ali ibn Abi Talib', 'gender' => 'male', 'father' => 'abu-talib-ibn-abd-al-muttalib', 'death' => 40, 'order' => 4, 'notes' => 'ابن عمّ النبي ﷺ وزوج ابنته فاطمة، ورابع الخلفاء الراشدين، وأول من أسلم من الصبيان.'],
        ['slug' => 'umm-hani-bint-abi-talib', 'ar' => 'أم هانئ بنت أبي طالب', 'en' => 'Umm Hani bint Abi Talib', 'gender' => 'female', 'father' => 'abu-talib-ibn-abd-al-muttalib', 'order' => 5, 'notes' => 'من بيتها كان الإسراء على قول.'],

        // بنو العباس
        ['slug' => 'al-fadl-ibn-al-abbas', 'ar' => 'الفضل بن العباس', 'en' => 'al-Fadl ibn al-Abbas', 'gender' => 'male', 'father' => 'al-abbas-ibn-abd-al-muttalib', 'order' => 1, 'notes' => 'أردفه النبي ﷺ خلفه في حجة الوداع.'],
        ['slug' => 'abdullah-ibn-abbas', 'ar' => 'عبد الله بن عباس', 'en' => 'Abdullah ibn Abbas', 'gender' => 'male', 'father' => 'al-abbas-ibn-abd-al-muttalib', 'death' => 68, 'order' => 2, 'notes' => 'حبر الأمة وترجمان القرآن، دعا له النبي ﷺ بالفقه في الدين.'],
        ['slug' => 'ubaydullah-ibn-al-abbas', 'ar' => 'عبيد الله بن العباس', 'en' => 'Ubaydullah ibn al-Abbas', 'gender' => 'male', 'father' => 'al-abbas-ibn-abd-al-muttalib', 'order' => 3],
        ['slug' => 'qutham-ibn-al-abbas', 'ar' => 'قثم بن العباس', 'en' => 'Qutham ibn al-Abbas', 'gender' => 'male', 'father' => 'al-abbas-ibn-abd-al-muttalib', 'order' => 4],

        // بنو الحارث
        ['slug' => 'abu-sufyan-ibn-al-harith', 'ar' => 'أبو سفيان بن الحارث', 'en' => 'Abu Sufyan ibn al-Harith', 'gender' => 'male', 'father' => 'al-harith-ibn-abd-al-muttalib', 'order' => 1, 'notes' => 'ابن عمّ النبي ﷺ وأخوه من الرضاعة، أسلم عام الفتح وثبت معه يوم حنين.'],
        ['slug' => 'nawfal-ibn-al-harith', 'ar' => 'نوفل بن الحارث', 'en' => 'Nawfal ibn al-Harith', 'gender' => 'male', 'father' => 'al-harith-ibn-abd-al-muttalib', 'order' => 2],
        ['slug' => 'rabia-ibn-al-harith', 'ar' => 'ربيعة بن الحارث', 'en' => 'Rabia ibn al-Harith', 'gender' => 'male', 'father' => 'al-harith-ibn-abd-al-muttalib', 'order' => 3],

        // ابن صفية — قرابته من جهة العمّة
        ['slug' => 'al-zubayr-ibn-al-awwam', 'ar' => 'الزبير بن العوّام', 'en' => 'al-Zubayr ibn al-Awwam', 'gender' => 'male', 'mother' => 'safiyya-bint-abd-al-muttalib', 'death' => 36, 'order' => 1, 'notes' => 'حواريّ رسول الله ﷺ، وأحد العشرة المبشّرين بالجنة، وابن عمّته صفية.'],

        // بنو أبي لهب
        ['slug' => 'utba-ibn-abi-lahab', 'ar' => 'عتبة بن أبي لهب', 'en' => 'Utba ibn Abi Lahab', 'gender' => 'male', 'father' => 'abu-lahab-ibn-abd-al-muttalib', 'order' => 1, 'notes' => 'أسلم عام الفتح.'],
        ['slug' => 'muattib-ibn-abi-lahab', 'ar' => 'معتّب بن أبي لهب', 'en' => 'Muattib ibn Abi Lahab', 'gender' => 'male', 'father' => 'abu-lahab-ibn-abd-al-muttalib', 'order' => 2, 'notes' => 'أسلم عام الفتح وشهد حنيناً.'],
    ];

    /**
     * People who marry into the household. They are not blood relatives, so
     * they carry no descent edge to the tree — only a `spouse` row.
     *
     * @var list<array{slug:string,ar:string,en:string,gender:string,death?:int,notes?:string}>
     */
    private const IN_LAWS = [
        ['slug' => 'uthman-ibn-affan', 'ar' => 'عثمان بن عفان', 'en' => 'Uthman ibn Affan', 'gender' => 'male', 'death' => 35, 'notes' => 'ذو النورين، ثالث الخلفاء الراشدين. تزوّج رقية ثم أم كلثوم ابنتي النبي ﷺ.'],
        ['slug' => 'abu-al-as-ibn-al-rabi', 'ar' => 'أبو العاص بن الربيع', 'en' => 'Abu al-As ibn al-Rabi', 'gender' => 'male', 'death' => 12, 'notes' => 'زوج زينب بنت النبي ﷺ، أثنى عليه ﷺ في صهره.'],
        ['slug' => 'mariya-al-qibtiyya', 'ar' => 'مارية القبطية', 'en' => 'Mariya al-Qibtiyya', 'gender' => 'female', 'death' => 16, 'notes' => 'أم إبراهيم بن النبي ﷺ، أهداها المقوقس. وهي أم ولد، ولا يعدّها الأكثرون في أمهات المؤمنين.'],
    ];

    /**
     * Marriages. Stored once, from the first party to the second.
     *
     * @var list<array{0:string,1:string,2:string}>
     */
    private const MARRIAGES = [
        ['abdullah-ibn-abd-al-muttalib', 'aminah-bint-wahb', 'والدا النبي ﷺ'],
        ['fatima-al-zahra', 'ali-ibn-abi-talib', 'زوج فاطمة الزهراء'],
        ['ruqayya-bint-muhammad', 'uthman-ibn-affan', 'تزوّجها بمكة وهاجر بها إلى الحبشة'],
        ['umm-kulthum-bint-muhammad', 'uthman-ibn-affan', 'تزوّجها بعد وفاة أختها رقية'],
        ['zaynab-bint-muhammad', 'abu-al-as-ibn-al-rabi', 'تزوّجها قبل البعثة'],
    ];

    public function run(): void
    {
        // The spine has to exist before anything can hang off it.
        $this->callOnce(ProphetLineageSeeder::class);

        $prophet = Muslim::where('slug', self::PROPHET)->first();
        $abdAlMuttalib = Muslim::where('slug', self::ABD_AL_MUTTALIB)->first();

        if ($prophet === null || $abdAlMuttalib === null) {
            $this->command?->warn('ProphetFamilySeeder: lineage root missing; nothing seeded.');

            return;
        }

        // Create everyone first, then wire the edges. A child may name a parent
        // that appears later in these lists (إبراهيم names مارية, who lives in
        // IN_LAWS), so no single pass can resolve every link as it goes.
        foreach (self::WIVES as $i => $w) {
            $this->person($w['slug'], $w['ar'], $w['en'], Gender::Female, Generation::Companion, $w['death'] ?? null, $i + 1, $w['notes'] ?? null, $w['kunya_ar'] ?? null);
        }
        foreach (self::IN_LAWS as $i => $p) {
            $this->person($p['slug'], $p['ar'], $p['en'], Gender::from($p['gender']), Generation::Companion, $p['death'] ?? null, $i + 1, $p['notes'] ?? null);
        }
        foreach (self::CHILDREN as $c) {
            $this->person($c['slug'], $c['ar'], $c['en'], Gender::from($c['gender']), Generation::Companion, $c['death'] ?? null, $c['order'], $c['notes'] ?? null);
        }
        foreach (self::GRANDCHILDREN as $g) {
            $this->person($g['slug'], $g['ar'], $g['en'], Gender::from($g['gender']), Generation::Companion, $g['death'] ?? null, $g['order'], $g['notes'] ?? null);
        }
        foreach (self::UNCLES as $u) {
            $this->person($u['slug'], $u['ar'], $u['en'], Gender::Male, Generation::Later, $u['death'] ?? null, $u['order'], $u['notes'] ?? null);
        }
        foreach (self::AUNTS as $a) {
            $this->person($a['slug'], $a['ar'], $a['en'], Gender::Female, Generation::Later, null, $a['order'], $a['notes'] ?? null);
        }
        foreach (self::COUSINS as $c) {
            $this->person($c['slug'], $c['ar'], $c['en'], Gender::from($c['gender']), Generation::Companion, $c['death'] ?? null, $c['order'], $c['notes'] ?? null);
        }

        // ── Descent edges ────────────────────────────────────────────────
        foreach (self::CHILDREN as $c) {
            $this->link($c['slug'], self::PROPHET, $c['mother']);
        }
        foreach (self::GRANDCHILDREN as $g) {
            $this->link($g['slug'], $g['father'], $g['mother']);
        }
        foreach (self::UNCLES as $u) {
            $this->link($u['slug'], self::ABD_AL_MUTTALIB, null);
        }
        foreach (self::AUNTS as $a) {
            $this->link($a['slug'], self::ABD_AL_MUTTALIB, null);
        }
        foreach (self::COUSINS as $c) {
            $this->link($c['slug'], $c['father'] ?? null, $c['mother'] ?? null);
        }

        // ── Marriages ────────────────────────────────────────────────────
        foreach (self::WIVES as $i => $w) {
            $this->marry(self::PROPHET, $w['slug'], $w['notes'] ?? null, $i + 1);
        }
        foreach (self::MARRIAGES as $i => [$a, $b, $note]) {
            $this->marry($a, $b, $note, $i + 1);
        }
    }

    private function person(
        string $slug,
        string $ar,
        string $en,
        Gender $gender,
        Generation $generation,
        ?int $death,
        int $order,
        ?string $notes = null,
        ?string $kunyaAr = null,
    ): Muslim {
        return Muslim::firstOrCreate(
            ['slug' => $slug],
            [
                'name_ar' => $ar,
                'name_en' => $en,
                'gender' => $gender->value,
                'generation' => $generation->value,
                'death_year_hijri' => $death,
                'tree_display_order' => $order,
                'is_published' => true,
                'kunya' => $kunyaAr === null ? null : ['ar' => $kunyaAr],
                'bio' => $notes === null ? null : ['ar' => $notes],
            ],
        );
    }

    /**
     * Write descent edges, but never over one that already exists — a curator
     * may have corrected the parentage, and a re-seed must not undo it.
     */
    private function link(string $childSlug, ?string $fatherSlug, ?string $motherSlug): void
    {
        $child = Muslim::where('slug', $childSlug)->first();

        if ($child === null) {
            return;
        }

        $updates = [];

        if ($fatherSlug !== null && $child->father_id === null) {
            $fatherId = Muslim::where('slug', $fatherSlug)->value('id');
            if ($fatherId !== null) {
                $updates['father_id'] = $fatherId;
            }
        }

        if ($motherSlug !== null && $child->mother_id === null) {
            $motherId = Muslim::where('slug', $motherSlug)->value('id');
            if ($motherId !== null) {
                $updates['mother_id'] = $motherId;
            }
        }

        if ($updates !== []) {
            $child->update($updates);
        }
    }

    /**
     * A marriage is stored once. The unique index is on
     * (muslim_id, related_muslim_id, relationship_type), which does not know
     * that the pair is unordered — so the reverse direction is checked too
     * before inserting, or a re-seed with the arguments swapped would create a
     * second row for the same marriage.
     */
    private function marry(string $aSlug, string $bSlug, ?string $notes, int $order): void
    {
        $a = Muslim::where('slug', $aSlug)->value('id');
        $b = Muslim::where('slug', $bSlug)->value('id');

        if ($a === null || $b === null) {
            return;
        }

        $exists = MuslimRelationship::query()
            ->where('relationship_type', RelationshipType::Spouse->value)
            ->where(fn ($q) => $q
                ->where(fn ($p) => $p->where('muslim_id', $a)->where('related_muslim_id', $b))
                ->orWhere(fn ($p) => $p->where('muslim_id', $b)->where('related_muslim_id', $a)))
            ->exists();

        if ($exists) {
            return;
        }

        MuslimRelationship::create([
            'muslim_id' => $a,
            'related_muslim_id' => $b,
            'relationship_type' => RelationshipType::Spouse->value,
            'notes' => $notes,
            'sort_order' => $order,
            'hide_from_tree' => false,
        ]);
    }
}
