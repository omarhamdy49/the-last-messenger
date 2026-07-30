<?php

declare(strict_types=1);

namespace Database\Seeders\Hadith;

use App\Domain\Hadith\Models\HadithCollection;
use Illuminate\Database\Seeder;

/**
 * The 17 collections, idempotent on `code`.
 *
 * Every `code`, `reference_template` and capability flag below was read off
 * sunnah.com on 2026-07-30, not inferred. Six codes differ from the
 * prophet-data-generator directory names and `/ahmed` 404s outright, so nothing
 * here may be derived from a directory listing — see research.md §2.
 */
class HadithCollectionSeeder extends Seeder
{
    /**
     * `{hadithNumber}` is substituted, not appended: the three forties render
     * the number in the middle of the line, and malik/darimi use the book-scoped
     * form as their canonical reference.
     *
     * @var list<array<string, mixed>>
     */
    private const COLLECTIONS = [
        [
            'code' => 'bukhari',
            'name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'],
            'author' => ['ar' => 'محمد بن إسماعيل البخاري', 'en' => 'Imam Muhammad al-Bukhari'],
            'reference_template' => 'Sahih al-Bukhari {hadithNumber}',
            'has_volumes' => true,
            'featured' => true,
            'display_order' => 1,
        ],
        [
            'code' => 'muslim',
            'name' => ['ar' => 'صحيح مسلم', 'en' => 'Sahih Muslim'],
            'author' => ['ar' => 'مسلم بن الحجاج النيسابوري', 'en' => 'Imam Muslim ibn al-Hajjaj'],
            'reference_template' => 'Sahih Muslim {hadithNumber}',
            'featured' => true,
            'display_order' => 2,
        ],
        [
            'code' => 'nasai',
            'name' => ['ar' => 'سنن النسائي', 'en' => "Sunan an-Nasa'i"],
            'author' => ['ar' => 'أحمد بن شعيب النسائي', 'en' => "Imam Ahmad an-Nasa'i"],
            'reference_template' => "Sunan an-Nasa'i {hadithNumber}",
            'has_volumes' => true,
            'display_order' => 3,
        ],
        [
            'code' => 'abudawud',
            'name' => ['ar' => 'سنن أبي داود', 'en' => 'Sunan Abi Dawud'],
            'author' => ['ar' => 'أبو داود السجستاني', 'en' => 'Imam Abu Dawud as-Sijistani'],
            'reference_template' => 'Sunan Abi Dawud {hadithNumber}',
            'display_order' => 4,
        ],
        [
            'code' => 'tirmidhi',
            'name' => ['ar' => 'جامع الترمذي', 'en' => 'Jami` at-Tirmidhi'],
            'author' => ['ar' => 'محمد بن عيسى الترمذي', 'en' => 'Imam Muhammad at-Tirmidhi'],
            'reference_template' => 'Jami` at-Tirmidhi {hadithNumber}',
            'has_volumes' => true,
            'display_order' => 5,
        ],
        [
            'code' => 'ibnmajah',
            'name' => ['ar' => 'سنن ابن ماجه', 'en' => 'Sunan Ibn Majah'],
            'author' => ['ar' => 'محمد بن يزيد ابن ماجه', 'en' => 'Imam Ibn Majah'],
            'reference_template' => 'Sunan Ibn Majah {hadithNumber}',
            'has_volumes' => true,
            'display_order' => 6,
        ],
        [
            'code' => 'malik',
            'name' => ['ar' => 'موطأ مالك', 'en' => 'Muwatta Malik'],
            'author' => ['ar' => 'مالك بن أنس', 'en' => 'Imam Malik ibn Anas'],
            // Canonical reference is book-scoped: sunnah.com's first reference
            // row for malik is "Book 1, Hadith 1", not a collection-wide number.
            'reference_template' => 'Book {bookNumber}, Hadith {hadithNumber}',
            'has_chapters' => false,
            'display_order' => 7,
        ],
        [
            'code' => 'ahmad',
            'name' => ['ar' => 'مسند أحمد', 'en' => 'Musnad Ahmad'],
            'author' => ['ar' => 'أحمد بن حنبل', 'en' => 'Imam Ahmad ibn Hanbal'],
            'reference_template' => 'Musnad Ahmad {hadithNumber}',
            'has_chapters' => false,
            'display_order' => 8,
        ],
        [
            'code' => 'darimi',
            'name' => ['ar' => 'سنن الدارمي', 'en' => 'Sunan ad-Darimi'],
            'author' => ['ar' => 'عبد الله بن عبد الرحمن الدارمي', 'en' => 'Imam ad-Darimi'],
            'reference_template' => 'Book {bookNumber}, Hadith {hadithNumber}',
            'has_chapters' => false,
            'display_order' => 9,
        ],
        [
            'code' => 'adab',
            'name' => ['ar' => 'الأدب المفرد', 'en' => 'Al-Adab Al-Mufrad'],
            'author' => ['ar' => 'محمد بن إسماعيل البخاري', 'en' => 'Imam Muhammad al-Bukhari'],
            'reference_template' => 'Al-Adab Al-Mufrad {hadithNumber}',
            'display_order' => 10,
        ],
        [
            'code' => 'shamail',
            'name' => ['ar' => 'الشمائل المحمدية', 'en' => "Ash-Shama'il Al-Muhammadiyah"],
            'author' => ['ar' => 'محمد بن عيسى الترمذي', 'en' => 'Imam Muhammad at-Tirmidhi'],
            'reference_template' => "Ash-Shama'il Al-Muhammadiyah {hadithNumber}",
            'has_chapters' => false,
            'display_order' => 11,
        ],
        [
            'code' => 'riyadussalihin',
            'name' => ['ar' => 'رياض الصالحين', 'en' => 'Riyad as-Salihin'],
            'author' => ['ar' => 'يحيى بن شرف النووي', 'en' => 'Imam an-Nawawi'],
            'reference_template' => 'Riyad as-Salihin {hadithNumber}',
            'display_order' => 12,
        ],
        [
            'code' => 'mishkat',
            'name' => ['ar' => 'مشكاة المصابيح', 'en' => 'Mishkat al-Masabih'],
            'author' => ['ar' => 'ولي الدين التبريزي', 'en' => 'Wali ad-Din at-Tabrizi'],
            'reference_template' => 'Mishkat al-Masabih {hadithNumber}',
            'display_order' => 13,
        ],
        [
            'code' => 'bulugh',
            'name' => ['ar' => 'بلوغ المرام', 'en' => 'Bulugh al-Maram'],
            'author' => ['ar' => 'ابن حجر العسقلاني', 'en' => 'Ibn Hajar al-Asqalani'],
            'reference_template' => 'Bulugh al-Maram {hadithNumber}',
            'display_order' => 14,
        ],
        // The three forties expose no book links at all, so each needs a hidden
        // synthetic book. Their reference line puts the number first.
        [
            'code' => 'nawawi40',
            'name' => ['ar' => 'الأربعون النووية', 'en' => "An-Nawawi's 40 Hadith"],
            'author' => ['ar' => 'يحيى بن شرف النووي', 'en' => 'Imam an-Nawawi'],
            'reference_template' => 'Hadith {hadithNumber}, 40 Hadith an-Nawawi',
            'has_books' => false,
            'has_chapters' => false,
            'display_order' => 15,
        ],
        [
            'code' => 'qudsi40',
            'name' => ['ar' => 'الأربعون القدسية', 'en' => '40 Hadith Qudsi'],
            'author' => null,
            'reference_template' => 'Hadith {hadithNumber}, 40 Hadith Qudsi',
            'has_books' => false,
            'has_chapters' => false,
            'display_order' => 16,
        ],
        [
            'code' => 'shahwaliullah40',
            'name' => ['ar' => 'أربعون شاه ولي الله', 'en' => '40 Hadith of Shah Waliullah'],
            'author' => ['ar' => 'شاه ولي الله الدهلوي', 'en' => 'Shah Waliullah Dehlawi'],
            'reference_template' => 'Hadith {hadithNumber}, 40 Hadith Shah Waliullah',
            'has_books' => false,
            'has_chapters' => false,
            'display_order' => 17,
        ],
    ];

    public function run(): void
    {
        foreach (self::COLLECTIONS as $collection) {
            HadithCollection::query()->updateOrCreate(
                ['code' => $collection['code']],
                [
                    'name' => $collection['name'],
                    'author' => $collection['author'] ?? null,
                    'reference_template' => $collection['reference_template'],
                    'has_books' => $collection['has_books'] ?? true,
                    'has_chapters' => $collection['has_chapters'] ?? true,
                    'has_volumes' => $collection['has_volumes'] ?? false,
                    'featured' => $collection['featured'] ?? false,
                    'display_order' => $collection['display_order'],
                    'is_published' => true,
                ],
            );
        }
    }
}
