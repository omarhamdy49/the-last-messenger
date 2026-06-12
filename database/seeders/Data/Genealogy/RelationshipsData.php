<?php

declare(strict_types=1);

namespace Database\Seeders\Data\Genealogy;

/**
 * Explicit relationship-graph definitions that cannot be derived from the
 * father/mother parent links alone: spouses, siblings, the Prophet's ﷺ
 * uncles/aunts and cousins, and direct grandparent ↔ grandchild edges.
 *
 * Parent/child edges (father/mother/son/daughter) are generated separately by
 * the seeder from each person's `father` / `mother` slug.
 */
final class RelationshipsData
{
    /** The tree root — the Messenger of Allah ﷺ. */
    public const PROPHET_SLUG = ProphetHouseholdData::PROPHET_SLUG;

    /**
     * Marriage pairs. The seeder creates both directions (husband ↔ wife).
     *
     * @return array<int, array{husband: string, wife: string, notes?: string}>
     */
    public static function spouses(): array
    {
        return [
            // The Prophet's ﷺ parents and grandparents
            ['husband' => 'abdullah-ibn-abd-al-muttalib', 'wife' => 'aminah-bint-wahb'],
            ['husband' => 'abd-al-muttalib', 'wife' => 'fatimah-bint-amr'],
            ['husband' => 'abd-al-muttalib', 'wife' => 'halah-bint-wuhayb'],
            ['husband' => 'hashim-ibn-abd-manaf', 'wife' => 'salma-bint-amr'],

            // The Prophet ﷺ and the Mothers of the Believers (+ Maria)
            ['husband' => self::PROPHET_SLUG, 'wife' => 'khadijah-bint-khuwaylid', 'notes' => 'الزوجة الأولى وأم أكثر ولده'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'sawdah-bint-zamah'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'aisha-bint-abi-bakr'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'hafsah-bint-umar'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'zaynab-bint-khuzaymah'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'umm-salamah'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'zaynab-bint-jahsh'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'juwayriyah-bint-al-harith'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'umm-habibah'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'safiyyah-bint-huyayy'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'maymunah-bint-al-harith'],
            ['husband' => self::PROPHET_SLUG, 'wife' => 'maria-al-qibtiyya', 'notes' => 'أم إبراهيم ابن النبي ﷺ'],

            // The Prophet's ﷺ daughters and their husbands (the sons-in-law)
            ['husband' => 'abu-al-as-ibn-al-rabi', 'wife' => 'zaynab-bint-muhammad'],
            ['husband' => 'uthman-ibn-affan', 'wife' => 'ruqayyah-bint-muhammad'],
            ['husband' => 'uthman-ibn-affan', 'wife' => 'umm-kulthum-bint-muhammad', 'notes' => 'تزوجها بعد وفاة أختها رقية'],
            ['husband' => 'ali-ibn-abi-talib', 'wife' => 'fatimah-bint-muhammad'],

            // Grandchildren's marriages within the household
            ['husband' => 'abdullah-ibn-jafar', 'wife' => 'zaynab-bint-ali'],
            ['husband' => 'umar-ibn-al-khattab', 'wife' => 'umm-kulthum-bint-ali'],

            // Parents of the Prophet's ﷺ cousins / in-laws (context links)
            ['husband' => 'abu-talib', 'wife' => 'fatimah-bint-asad'],

            // Zayd's marriage to Zaynab bint Jahsh (ended in divorce before
            // her marriage to the Prophet ﷺ — Quran 33:37)
            ['husband' => 'zayd-ibn-harithah', 'wife' => 'zaynab-bint-jahsh', 'notes' => 'انتهى بالطلاق قبل زواجها من النبي ﷺ'],
        ];
    }

    /**
     * Sibling groups — every member is related to every other as
     * brother/sister (gender of the *sibling* decides the type).
     *
     * @return array<int, array<int, string>>
     */
    public static function siblingGroups(): array
    {
        return [
            // Children of Abd al-Muttalib — the Prophet's ﷺ father and uncles/aunts
            [
                'abdullah-ibn-abd-al-muttalib',
                'abu-talib',
                'al-zubayr-ibn-abd-al-muttalib',
                'hamza-ibn-abd-al-muttalib',
                'al-abbas-ibn-abd-al-muttalib',
                'abu-lahab',
                'al-harith-ibn-abd-al-muttalib',
                'safiyyah-bint-abd-al-muttalib',
                'atikah-bint-abd-al-muttalib',
                'arwa-bint-abd-al-muttalib',
                'umaymah-bint-abd-al-muttalib',
                'barrah-bint-abd-al-muttalib',
            ],

            // Children of the Prophet ﷺ
            [
                'al-qasim-ibn-muhammad',
                'abdullah-ibn-muhammad',
                'zaynab-bint-muhammad',
                'ruqayyah-bint-muhammad',
                'umm-kulthum-bint-muhammad',
                'fatimah-bint-muhammad',
                'ibrahim-ibn-muhammad',
            ],

            // Children of Ali and Fatimah — the Prophet's ﷺ grandchildren
            [
                'al-hasan-ibn-ali',
                'al-husayn-ibn-ali',
                'zaynab-bint-ali',
                'umm-kulthum-bint-ali',
            ],

            // Sons of Abu Talib — the Prophet's ﷺ cousins and foster-brothers
            [
                'ali-ibn-abi-talib',
                'jafar-ibn-abi-talib',
                'aqil-ibn-abi-talib',
            ],
        ];
    }

    /**
     * Paternal uncles and aunts of the Prophet ﷺ (children of Abd al-Muttalib).
     * The seeder links each to the Prophet as uncle/aunt + nephew.
     *
     * @return array<int, string>
     */
    public static function unclesAndAuntsOfTheProphet(): array
    {
        return [
            'abu-talib',
            'hamza-ibn-abd-al-muttalib',
            'al-abbas-ibn-abd-al-muttalib',
            'abu-lahab',
            'al-harith-ibn-abd-al-muttalib',
            'al-zubayr-ibn-abd-al-muttalib',
            'safiyyah-bint-abd-al-muttalib',
            'atikah-bint-abd-al-muttalib',
            'arwa-bint-abd-al-muttalib',
            'umaymah-bint-abd-al-muttalib',
            'barrah-bint-abd-al-muttalib',
        ];
    }

    /**
     * First cousins of the Prophet ﷺ (children of his uncles/aunts).
     * Linked symmetrically as cousin ↔ cousin.
     *
     * @return array<int, string>
     */
    public static function cousinsOfTheProphet(): array
    {
        return [
            'ali-ibn-abi-talib',
            'jafar-ibn-abi-talib',
            'aqil-ibn-abi-talib',
            'abdullah-ibn-abbas',
            'zubayr-ibn-al-awwam',
        ];
    }

    /**
     * Direct grandparent ↔ grandchild edges from the Prophet ﷺ to his
     * grandchildren, making the tree traversable across generations without
     * relying solely on chained parent links. Each entry creates both
     * directions (grandfather/grandmother ↔ grandson/granddaughter).
     *
     * @return array<int, array{grandparent: string, grandchild: string}>
     */
    public static function grandparentLinks(): array
    {
        return [
            ['grandparent' => self::PROPHET_SLUG, 'grandchild' => 'al-hasan-ibn-ali'],
            ['grandparent' => self::PROPHET_SLUG, 'grandchild' => 'al-husayn-ibn-ali'],
            ['grandparent' => self::PROPHET_SLUG, 'grandchild' => 'zaynab-bint-ali'],
            ['grandparent' => self::PROPHET_SLUG, 'grandchild' => 'umm-kulthum-bint-ali'],
            ['grandparent' => self::PROPHET_SLUG, 'grandchild' => 'umamah-bint-zaynab'],
            ['grandparent' => self::PROPHET_SLUG, 'grandchild' => 'abdullah-ibn-uthman'],
        ];
    }
}
