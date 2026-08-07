<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

/**
 * Verified Sunnah.com page fallbacks for records whose official API row is corrupt.
 * Keep this list deliberately tiny and require an exact source identity at call sites.
 */
final class SunnahApiFallbacks
{
    /** @return array<string, mixed> */
    public static function bukhari6940(): array
    {
        // Verified against https://sunnah.com/bukhari/89 on 2026-08-07.
        return [
            'collection' => 'bukhari',
            'bookNumber' => '89',
            'chapterId' => '0.00',
            'hadithNumber' => '6940',
            'hadith' => [
                [
                    'lang' => 'en',
                    'chapterNumber' => null,
                    'chapterTitle' => null,
                    'urn' => 160570,
                    'body' => "Narrated Abi Huraira:\n\nThe Prophet (ﷺ) used to invoke Allah in his prayer, \"O Allah! Save `Aiyash bin Abi Rabi`a and Salama bin Hisham and Al-Walid bin Al-Walid; O Allah! Save the weak among the believers; O Allah! Be hard upon the tribe of Mudar and inflict years (of famine) upon them like the (famine) years of Joseph.\"",
                    'grades' => [],
                ],
                [
                    'lang' => 'ar',
                    'chapterNumber' => null,
                    'chapterTitle' => null,
                    'urn' => 65280,
                    'body' => 'حَدَّثَنَا يَحْيَى بْنُ بُكَيْرٍ، حَدَّثَنَا اللَّيْثُ، عَنْ خَالِدِ بْنِ يَزِيدَ، عَنْ سَعِيدِ بْنِ أَبِي هِلاَلٍ، عَنْ هِلاَلِ بْنِ أُسَامَةَ، أَنَّ أَبَا سَلَمَةَ بْنَ عَبْدِ الرَّحْمَنِ، أَخْبَرَهُ عَنْ أَبِي هُرَيْرَةَ، أَنَّ النَّبِيَّ صلى الله عليه وسلم كَانَ يَدْعُو فِي الصَّلاَةِ ‏"‏ اللَّهُمَّ أَنْجِ عَيَّاشَ بْنَ أَبِي رَبِيعَةَ، وَسَلَمَةَ بْنَ هِشَامٍ، وَالْوَلِيدَ بْنَ الْوَلِيدِ، اللَّهُمَّ أَنْجِ الْمُسْتَضْعَفِينَ مِنَ الْمُؤْمِنِينَ، اللَّهُمَّ اشْدُدْ وَطْأَتَكَ عَلَى مُضَرَ، وَابْعَثْ عَلَيْهِمْ سِنِينَ كَسِنِي يُوسُفَ ‏"‏‏.‏',
                    'grades' => [],
                ],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function bukhariBook96Opening(): array
    {
        // Verified against https://sunnah.com/bukhari/96 on 2026-08-07.
        return [
            self::bukhari96Record(
                '7268',
                <<<'EN'
Narrated Tariq bin Shihab:

A Jew said to `Umar, "O Chief of the Believers, if this verse: 'This day I have perfected your religion for you, completed My favors upon you, and have chosen for you, Islam as your religion.' (5.3) had been revealed upon us, we would have taken that day as an `Id (festival) day." `Umar said, "I know definitely on what day this Verse was revealed; it was revealed on the day of `Arafat, on a Friday."
EN,
                'حَدَّثَنَا الْحُمَيْدِيُّ، حَدَّثَنَا سُفْيَانُ، عَنْ مِسْعَرٍ، وَغَيْرِهِ، عَنْ قَيْسِ بْنِ مُسْلِمٍ، عَنْ طَارِقِ بْنِ شِهَابٍ، قَالَ قَالَ رَجُلٌ مِنَ الْيَهُودِ لِعُمَرَ يَا أَمِيرَ الْمُؤْمِنِينَ لَوْ أَنَّ عَلَيْنَا نَزَلَتْ هَذِهِ الآيَةُ ‏‏{‏ الْيَوْمَ أَكْمَلْتُ لَكُمْ دِينَكُمْ وَأَتْمَمْتُ عَلَيْكُمْ نِعْمَتِي وَرَضِيتُ لَكُمُ الإِسْلاَمَ دِينًا‏}‏ لاَتَّخَذْنَا ذَلِكَ الْيَوْمَ عِيدًا‏.‏ فَقَالَ عُمَرُ إِنِّي لأَعْلَمُ أَىَّ يَوْمٍ نَزَلَتْ هَذِهِ الآيَةُ، نَزَلَتْ يَوْمَ عَرَفَةَ فِي يَوْمِ جُمُعَةٍ‏.‏ سَمِعَ سُفْيَانُ مِنْ مِسْعَرٍ وَمِسْعَرٌ قَيْسًا وَقَيْسٌ طَارِقًا‏.‏',
            ),
            self::bukhari96Record(
                '7269',
                <<<'EN'
Narrated Anas bin Malik:

That he heard `Umar speaking while standing on the pulpit of the Prophet (ﷺ) in the morning (following the death of the Prophet), when the people had sworn allegiance to Abu Bakr. He said the Tashah-hud before Abu Bakr, and said, "Amma Ba'du (then after) Allah has chosen for his Apostle what is with Him (Paradise) rather than what is with you (the world).
This is that Book (Qur'an) with which Allah guided your Apostle, so stick to it, for then you will be guided on the right path as Allah guided His Apostle with it."
EN,
                'حَدَّثَنَا يَحْيَى بْنُ بُكَيْرٍ، حَدَّثَنَا اللَّيْثُ، عَنْ عُقَيْلٍ، عَنِ ابْنِ شِهَابٍ، أَخْبَرَنِي أَنَسُ بْنُ مَالِكٍ، أَنَّهُ سَمِعَ عُمَرَ الْغَدَ، حِينَ بَايَعَ الْمُسْلِمُونَ أَبَا بَكْرٍ، وَاسْتَوَى عَلَى مِنْبَرِ رَسُولِ اللَّهِ صلى الله عليه وسلم تَشَهَّدَ قَبْلَ أَبِي بَكْرٍ فَقَالَ أَمَّا بَعْدُ فَاخْتَارَ اللَّهُ لِرَسُولِهِ صلى الله عليه وسلم الَّذِي عِنْدَهُ عَلَى الَّذِي عِنْدَكُمْ، وَهَذَا الْكِتَابُ الَّذِي هَدَى اللَّهُ بِهِ رَسُولَكُمْ فَخُذُوا بِهِ تَهْتَدُوا وَإِنَّمَا هَدَى اللَّهُ بِهِ رَسُولَهُ‏.‏',
            ),
            self::bukhari96Record(
                '7270',
                <<<'EN'
Narrated Ibn `Abbas:

The Prophet (ﷺ) embraced me and said, "O Allah! Teach him (the knowledge of) the Book (Qur'an)."
EN,
                'حَدَّثَنَا مُوسَى بْنُ إِسْمَاعِيلَ، حَدَّثَنَا وُهَيْبٌ، عَنْ خَالِدٍ، عَنْ عِكْرِمَةَ، عَنِ ابْنِ عَبَّاسٍ، قَالَ ضَمَّنِي إِلَيْهِ النَّبِيُّ صلى الله عليه وسلم وَقَالَ ‏"‏ اللَّهُمَّ عَلِّمْهُ الْكِتَابَ ‏"‏‏.‏',
            ),
            self::bukhari96Record(
                '7271',
                <<<'EN'
Narrated Abal Minhal:

Abu Barza said, "(O people!) Allah makes you self-sufficient or has raised you high with Islam and with Muhammad."
EN,
                'حَدَّثَنَا عَبْدُ اللَّهِ بْنُ صَبَّاحٍ، حَدَّثَنَا مُعْتَمِرٌ، قَالَ سَمِعْتُ عَوْفًا، أَنَّ أَبَا الْمِنْهَالِ، حَدَّثَهُ أَنَّهُ، سَمِعَ أَبَا بَرْزَةَ، قَالَ إِنَّ اللَّهَ يُغْنِيكُمْ أَوْ نَغَشَكُمْ بِالإِسْلاَمِ وَبِمُحَمَّدٍ صلى الله عليه وسلم‏.‏ قَالَ أَبُو عَبْد اللَّهِ وَقَعَ هَاهُنَا يُغْنِيكُمْ وَإِنَّمَا هُوَ نَعَشَكُمْ يُنْظَرُ فِي أَصْلِ كِتَابِ الِاعْتِصَامِ.',
            ),
            self::bukhari96Record(
                '7272',
                <<<'EN'
Narrated `Abdullah bin Dinar:

`Abdullah Bin `Umar wrote to `Abdul Malik bin Marwan, swearing allegiance to him: 'I swear allegiance to you in that I will listen and obey what is in accordance with the Laws of Allah and the Tradition of His Apostle as much as I can.'
EN,
                'حَدَّثَنَا إِسْمَاعِيلُ، حَدَّثَنِي مَالِكٌ، عَنْ عَبْدِ اللَّهِ بْنِ دِينَارٍ، أَنَّ عَبْدَ اللَّهِ بْنَ عُمَرَ، كَتَبَ إِلَى عَبْدِ الْمَلِكِ بْنِ مَرْوَانَ يُبَايِعُهُ، وَأُقِرُّ لَكَ بِالسَّمْعِ وَالطَّاعَةِ عَلَى سُنَّةِ اللَّهِ وَسُنَّةِ رَسُولِهِ، فِيمَا اسْتَطَعْتُ‏.‏',
            ),
        ];
    }

    /** @return array<string, mixed> */
    public static function tirmidhi1819(): array
    {
        // Verified against https://sunnah.com/tirmidhi/25 on 2026-08-07.
        // The page's grade value is corrupt ("1819"), so no grade claim is copied.
        return [
            'collection' => 'tirmidhi',
            'bookNumber' => '25',
            'chapterId' => '20.00',
            'hadithNumber' => '1819',
            'hadith' => [
                [
                    'lang' => 'en',
                    'body' => <<<'EN'
Narrated Abu Hurairah:
"The Messenger of Allah (ﷺ) had a disbeliever as a guest. So the Messenger of Allah (ﷺ) ordered that a sheep be milked so he could drink. Then another was milked so he could drink, then another, so he drank until he had drank the milk of seven sheep. The he awoke the next morning and accepted Islam. The Messenger of Allah (ﷺ) ordered that a sheep be milked so he could drink its milk, then he ordered for another but he could not finish it.
So the Messenger of Allah (ﷺ) said: 'The believer drinks with one intestine and the disbeliever drinks with seven.'"
[Abu 'Eisa said:] This Hadith is Sahih Hasan Gharib as a narration of Suhail.
EN,
                    'grades' => [],
                ],
                [
                    'lang' => 'ar',
                    'body' => 'حَدَّثَنَا إِسْحَاقُ بْنُ مُوسَى الأَنْصَارِيُّ، حَدَّثَنَا مَعْنٌ، حَدَّثَنَا مَالِكٌ، عَنْ سُهَيْلِ بْنِ أَبِي صَالِحٍ، عَنْ أَبِيهِ، عَنْ أَبِي هُرَيْرَةَ، أَنَّ رَسُولَ اللَّهِ صلى الله عليه وسلم ضَافَهُ ضَيْفٌ كَافِرٌ فَأَمَرَ لَهُ رَسُولُ اللَّهِ صلى الله عليه وسلم بِشَاةٍ فَحُلِبَتْ فَشَرِبَ ثُمَّ أُخْرَى فَشَرِبَهُ ثُمَّ أُخْرَى فَشَرِبَهُ حَتَّى شَرِبَ حِلاَبَ سَبْعِ شِيَاهٍ ثُمَّ أَصْبَحَ مِنَ الْغَدِ فَأَسْلَمَ فَأَمَرَ لَهُ رَسُولُ اللَّهِ صلى الله عليه وسلم بِشَاةٍ فَحُلِبَتْ فَشَرِبَ حِلاَبَهَا ثُمَّ أَمَرَ لَهُ بِأُخْرَى فَلَمْ يَسْتَتِمَّهَا فَقَالَ رَسُولُ اللَّهِ صلى الله عليه وسلم ‏ "‏ الْمُؤْمِنُ يَشْرَبُ فِي مِعًى وَاحِدٍ وَالْكَافِرُ يَشْرَبُ فِي سَبْعَةِ أَمْعَاءٍ ‏" ‏ ‏.‏ قَالَ أَبُو عِيسَى هَذَا حَدِيثٌ حَسَنٌ صَحِيحٌ غَرِيبٌ مِنْ حَدِيثِ سُهَيْلٍ ‏.‏',
                    'grades' => [],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function bukhari96Record(string $number, string $english, string $arabic): array
    {
        return [
            'collection' => 'bukhari',
            'bookNumber' => '96',
            'chapterId' => '0.00',
            'hadithNumber' => $number,
            'hadith' => [
                ['lang' => 'en', 'body' => $english, 'grades' => []],
                ['lang' => 'ar', 'body' => $arabic, 'grades' => []],
            ],
        ];
    }
}
