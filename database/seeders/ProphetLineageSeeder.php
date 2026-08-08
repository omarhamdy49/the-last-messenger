<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\Generation;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\ValueObjects\MuslimProfile;
use Illuminate\Database\Seeder;

/**
 * The Prophet ﷺ and his paternal lineage — the tree's root and its spine.
 *
 * WHY THIS EXISTS: the genealogy had no root. `resolveDefaultRoot()` fell back to
 * "whoever has the most recorded children", which made the landing tree depend
 * on import order. Both the admin and public trees now root at whoever carries
 * the `prophet` role, and this seeder is what puts someone there.
 *
 * THE CHAIN is the standard Adnani lineage as carried in the seerah literature
 * (Ibn Hisham, Ibn Sa'd), ordered son → father from the Prophet ﷺ up to Adnan.
 * It is seeded as data, not code: every name here is editable in the admin
 * afterwards, and re-running this seeder will not overwrite those edits.
 *
 * IDEMPOTENCE: `firstOrCreate` on slug, never `updateOrCreate`. An editor's
 * correction to a name or a date must survive a re-seed — the whole point of
 * making this content rather than constants.
 *
 * Years are left null except for the Prophet ﷺ himself. Inventing birth and
 * death years for twenty ancestors would dress guesses up as records.
 */
final class ProphetLineageSeeder extends Seeder
{
    /**
     * Son → father. Index 0 is the Prophet ﷺ; each entry's father is the next.
     *
     * @var list<array{slug:string,name_ar:string,name_en:string}>
     */
    private const PATERNAL_CHAIN = [
        ['slug' => 'muhammad', 'name_ar' => 'محمد ﷺ', 'name_en' => 'Muhammad ﷺ'],
        ['slug' => 'abdullah-ibn-abd-al-muttalib', 'name_ar' => 'عبد الله بن عبد المطلب', 'name_en' => 'Abdullah ibn Abd al-Muttalib'],
        ['slug' => 'abd-al-muttalib', 'name_ar' => 'عبد المطلب بن هاشم', 'name_en' => 'Abd al-Muttalib ibn Hashim'],
        ['slug' => 'hashim-ibn-abd-manaf', 'name_ar' => 'هاشم بن عبد المناف', 'name_en' => 'Hashim ibn Abd Manaf'],
        ['slug' => 'abd-manaf-ibn-qusayy', 'name_ar' => 'عبد المناف بن قصي', 'name_en' => 'Abd Manaf ibn Qusayy'],
        ['slug' => 'qusayy-ibn-kilab', 'name_ar' => 'قصي بن كلاب', 'name_en' => 'Qusayy ibn Kilab'],
        ['slug' => 'kilab-ibn-murrah', 'name_ar' => 'كلاب بن مرة', 'name_en' => 'Kilab ibn Murrah'],
        ['slug' => 'murrah-ibn-kaab', 'name_ar' => 'مرة بن كعب', 'name_en' => 'Murrah ibn Kaab'],
        ['slug' => 'kaab-ibn-luayy', 'name_ar' => 'كعب بن لؤي', 'name_en' => 'Kaab ibn Luayy'],
        ['slug' => 'luayy-ibn-ghalib', 'name_ar' => 'لؤي بن غالب', 'name_en' => 'Luayy ibn Ghalib'],
        ['slug' => 'ghalib-ibn-fihr', 'name_ar' => 'غالب بن فهر', 'name_en' => 'Ghalib ibn Fihr'],
        ['slug' => 'fihr-ibn-malik', 'name_ar' => 'فهر بن مالك', 'name_en' => 'Fihr ibn Malik'],
        ['slug' => 'malik-ibn-al-nadr', 'name_ar' => 'مالك بن النضر', 'name_en' => 'Malik ibn al-Nadr'],
        ['slug' => 'al-nadr-ibn-kinanah', 'name_ar' => 'النضر بن كنانة', 'name_en' => 'al-Nadr ibn Kinanah'],
        ['slug' => 'kinanah-ibn-khuzaymah', 'name_ar' => 'كنانة بن خزيمة', 'name_en' => 'Kinanah ibn Khuzaymah'],
        ['slug' => 'khuzaymah-ibn-mudrikah', 'name_ar' => 'خزيمة بن مدركة', 'name_en' => 'Khuzaymah ibn Mudrikah'],
        ['slug' => 'mudrikah-ibn-ilyas', 'name_ar' => 'مدركة بن إلياس', 'name_en' => 'Mudrikah ibn Ilyas'],
        ['slug' => 'ilyas-ibn-mudar', 'name_ar' => 'إلياس بن مضر', 'name_en' => 'Ilyas ibn Mudar'],
        ['slug' => 'mudar-ibn-nizar', 'name_ar' => 'مضر بن نزار', 'name_en' => 'Mudar ibn Nizar'],
        ['slug' => 'nizar-ibn-maadd', 'name_ar' => 'نزار بن معد', 'name_en' => 'Nizar ibn Maadd'],
        ['slug' => 'maadd-ibn-adnan', 'name_ar' => 'معد بن عدنان', 'name_en' => 'Maadd ibn Adnan'],
        ['slug' => 'adnan', 'name_ar' => 'عدنان', 'name_en' => 'Adnan'],
    ];

    public function run(): void
    {
        // Depends on the `prophet` role existing.
        $this->callOnce(IslamicRoleSeeder::class);

        /** @var list<Muslim> $people */
        $people = [];

        foreach (self::PATERNAL_CHAIN as $index => $entry) {
            $people[$index] = Muslim::firstOrCreate(
                ['slug' => $entry['slug']],
                [
                    'name_ar' => $entry['name_ar'],
                    'name_en' => $entry['name_en'],
                    'gender' => Gender::Male->value,
                    'is_published' => true,
                    // Only the Prophet ﷺ carries dates here; -53 AH is the year
                    // of his birth expressed against the Hijra, and 11 AH his
                    // death. Every ancestor is left blank rather than guessed.
                    'birth_year_hijri' => $index === 0 ? -53 : null,
                    'death_year_hijri' => $index === 0 ? 11 : null,
                    'generation' => $index === 0
                        ? Generation::Prophet->value
                        : Generation::Later->value,
                    'tree_display_order' => 0,
                ],
            );
        }

        // Link son → father, but only where the child has no father recorded:
        // an editor may have corrected the chain, and this must not undo that.
        foreach (self::PATERNAL_CHAIN as $index => $entry) {
            $father = $people[$index + 1] ?? null;

            if ($father === null || $people[$index]->father_id !== null) {
                continue;
            }

            $people[$index]->update(['father_id' => $father->id]);
        }

        $this->seedMother($people[0]);
        $this->seedProphetProfile($people[0]);

        // The role is what roots the tree; assign it without disturbing any
        // other roles the record already carries.
        $prophetRole = IslamicRole::where('slug', 'prophet')->first();

        if ($prophetRole !== null && ! $people[0]->roles()->where('islamic_roles.id', $prophetRole->id)->exists()) {
            $people[0]->roles()->attach($prophetRole, ['is_featured' => true, 'sort_order' => 0]);
        }
    }

    /**
     * The extended profile the home page's ID card renders.
     *
     * These exact values were hardcoded in the per-locale `site` translation
     * files, so the card was not editable by anyone without a deploy. Seeding
     * them here is what makes the page read from the record instead.
     *
     * Skipped entirely if a profile already exists: an editor's correction must
     * survive a re-seed.
     */
    private function seedProphetProfile(Muslim $prophet): void
    {
        if (! $prophet->profile->isEmpty()) {
            return;
        }

        $prophet->update([
            'profile' => MuslimProfile::fromArray([
                'full_name_ar' => 'محمد بن عبد الله بن عبد المطلب',
                'full_name_en' => 'Muhammad ibn Abdullah ibn Abd al-Muttalib',
                'tribe_ar' => 'من بني هاشم، من قريش',
                'tribe_en' => 'Of Banu Hashim, of the Quraysh',

                'birth_day_ar' => '12 ربيع الأول',
                'birth_day_en' => '12 Rabi al-Awwal',
                'birth_region_ar' => 'الحجاز',
                'birth_region_en' => 'The Hejaz',
                'birth_year_ce' => 571,

                'death_day_ar' => '12 ربيع الأول - 11 هـ',
                'death_day_en' => '12 Rabi al-Awwal, 11 AH',
                'death_place_ar' => 'المدينة',
                'death_place_en' => 'Madinah',
                'death_detail_ar' => 'في بيت السيدة عائشة رضي الله عنها',
                'death_detail_en' => 'In the house of Aisha, may Allah be pleased with her',
                'death_year_ce' => 632,

                'mission_place_ar' => 'غار حراء',
                'mission_place_en' => 'The Cave of Hira',
                'mission_age' => 40,
                'mission_year_ce' => 610,
            ]),
        ]);
    }

    private function seedMother(Muslim $prophet): void
    {
        $mother = Muslim::firstOrCreate(
            ['slug' => 'aminah-bint-wahb'],
            [
                'name_ar' => 'آمنة بنت وهب',
                'name_en' => 'Aminah bint Wahb',
                'gender' => Gender::Female->value,
                'is_published' => true,
                'generation' => Generation::Later->value,
            ],
        );

        if ($prophet->mother_id === null) {
            $prophet->update(['mother_id' => $mother->id]);
        }
    }
}
