<?php

/**
 * Comprehensive Admin Panel Testing Script
 * Tests all CRUD operations across all admin modules
 * Outputs results to test-results.md
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminPanelTester
{
    private $results = [];
    private $user;
    private $testCounts = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
    ];

    public function __construct()
    {
        // Authenticate as admin user
        $this->user = \App\Models\User::where('email', 'supm@dwit.com')->first();
        if ($this->user) {
            Auth::login($this->user);
            echo "✓ Authenticated as {$this->user->email}\n";
        } else {
            echo "✗ User supm@dwit.com not found\n";
            exit(1);
        }
    }

    public function test($description, $callback)
    {
        $this->testCounts['total']++;
        try {
            $callback();
            $this->addResult("✓ PASS", $description);
            $this->testCounts['passed']++;
        } catch (\Exception $e) {
            $this->addResult("✗ FAIL", $description . " - " . $e->getMessage());
            $this->testCounts['failed']++;
        }
    }

    public function addResult($status, $message)
    {
        $this->results[] = "[$status] $message";
    }

    // ========== CATEGORIES TESTS ==========
    public function testCategories()
    {
        echo "\n=== Testing Categories Module ===\n";

        // CREATE
        $this->test("Create category with all fields", function () {
            $category = \App\Domain\Blog\Models\Category::create([
                'name' => ['ar' => 'تصنيف اختبار', 'en' => 'Test Category', 'fr' => 'Catégorie Test', 'tr' => 'Test Kategorisi', 'ur' => 'ٹیسٹ زمرہ'],
                'slug' => 'test-category-' . time(),
                'type' => 'blog',
                'description' => ['ar' => 'وصف', 'en' => 'Description', 'fr' => 'Description', 'tr' => 'Açıklama', 'ur' => 'تفصیل'],
            ]);
            $this->categoryId = $category->id;
            if (!$this->categoryId) throw new \Exception("Failed to create category");
        });

        // READ
        $this->test("Read category by ID", function () {
            $category = \App\Domain\Blog\Models\Category::find($this->categoryId);
            if (!$category || $category->id !== $this->categoryId) throw new \Exception("Category not found");
        });

        // UPDATE
        $this->test("Update category fields", function () {
            $updated = \App\Domain\Blog\Models\Category::where('id', $this->categoryId)->update([
                'name' => ['ar' => 'تحديث التصنيف', 'en' => 'Updated Category', 'fr' => 'Catégorie Mise à Jour', 'tr' => 'Güncelleme Kategorisi', 'ur' => 'اپ ڈیٹ شدہ زمرہ'],
            ]);
            if (!$updated) throw new \Exception("Failed to update category");
        });

        // LIST
        $this->test("List categories with pagination", function () {
            $service = app(\App\Domain\Blog\Services\CategoryService::class);
            $results = $service->paginateWithFilters(50, 'test', [], []);
            if (!$results) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete category", function () {
            $deleted = \App\Domain\Blog\Models\Category::where('id', $this->categoryId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete category");
        });
    }

    // ========== ARTICLES TESTS ==========
    public function testArticles()
    {
        echo "\n=== Testing Articles Module ===\n";

        // CREATE
        $this->test("Create article with all fields", function () {
            $category = \App\Domain\Blog\Models\Category::first();
            $article = \App\Domain\Blog\Models\Article::create([
                'title' => ['ar' => 'مقال اختبار', 'en' => 'Test Article', 'fr' => 'Article Test', 'tr' => 'Test Makalesi', 'ur' => 'ٹیسٹ مضمون'],
                'slug' => 'test-article-' . time(),
                'content' => ['ar' => 'المحتوى', 'en' => 'Content', 'fr' => 'Contenu', 'tr' => 'İçerik', 'ur' => 'مواد'],
                'excerpt' => ['ar' => 'ملخص', 'en' => 'Excerpt', 'fr' => 'Extrait', 'tr' => 'Özet', 'ur' => 'خلاصہ'],
                'status' => 'published',
                'user_id' => $this->user->id,
                'category_id' => $category ? $category->id : null,
            ]);
            $this->articleId = $article->id;
            if (!$this->articleId) throw new \Exception("Failed to create article");
        });

        // READ
        $this->test("Read article by ID", function () {
            $article = \App\Domain\Blog\Models\Article::find($this->articleId);
            if (!$article || $article->id !== $this->articleId) throw new \Exception("Article not found");
        });

        // UPDATE
        $this->test("Update article content", function () {
            $updated = \App\Domain\Blog\Models\Article::where('id', $this->articleId)->update([
                'title' => ['ar' => 'مقال محدثة', 'en' => 'Updated Article', 'fr' => 'Article Mis à Jour', 'tr' => 'Güncellenmiş Makale', 'ur' => 'اپ ڈیٹ شدہ مضمون'],
            ]);
            if (!$updated) throw new \Exception("Failed to update article");
        });

        // SEARCH
        $this->test("Search articles", function () {
            $service = app(\App\Domain\Blog\Services\ArticleService::class);
            $results = $service->searchArticles('test', 15);
            if (!$results) throw new \Exception("Search failed");
        });

        // DELETE
        $this->test("Delete article", function () {
            $deleted = \App\Domain\Blog\Models\Article::where('id', $this->articleId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete article");
        });
    }

    // ========== TAGS TESTS ==========
    public function testTags()
    {
        echo "\n=== Testing Tags Module ===\n";

        // CREATE
        $this->test("Create tag with multilingual name", function () {
            $tag = \App\Domain\Blog\Models\Tag::create([
                'name' => ['ar' => 'وسم اختبار', 'en' => 'Test Tag', 'fr' => 'Étiquette Test', 'tr' => 'Test Etiketi', 'ur' => 'ٹیسٹ ٹیگ'],
                'slug' => 'test-tag-' . time(),
            ]);
            $this->tagId = $tag->id;
            if (!$this->tagId) throw new \Exception("Failed to create tag");
        });

        // READ
        $this->test("Read tag by ID", function () {
            $tag = \App\Domain\Blog\Models\Tag::find($this->tagId);
            if (!$tag || $tag->id !== $this->tagId) throw new \Exception("Tag not found");
        });

        // UPDATE
        $this->test("Update tag name", function () {
            $updated = \App\Domain\Blog\Models\Tag::where('id', $this->tagId)->update([
                'name' => ['ar' => 'وسم محدث', 'en' => 'Updated Tag', 'fr' => 'Étiquette Mise à Jour', 'tr' => 'Güncellenmiş Etiket', 'ur' => 'اپ ڈیٹ شدہ ٹیگ'],
            ]);
            if (!$updated) throw new \Exception("Failed to update tag");
        });

        // PAGINATE with SEARCH
        $this->test("List tags with pagination", function () {
            $service = app(\App\Domain\Blog\Services\TagService::class);
            $tags = $service->paginate(50, 'test');
            if (!$tags) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete tag", function () {
            $deleted = \App\Domain\Blog\Models\Tag::where('id', $this->tagId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete tag");
        });
    }

    // ========== HADITH TESTS ==========
    public function testHadith()
    {
        echo "\n=== Testing Hadith Module ===\n";

        // Create book first
        $book = \App\Domain\Hadith\Models\HadithBook::firstOrCreate(
            ['slug' => 'sahih-bukhari'],
            ['name' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari', 'fr' => 'Sahih Bukhari', 'tr' => 'Sahih Buhari', 'ur' => 'صحیح البخاری']]
        );

        // CREATE
        $this->test("Create hadith entry", function () use ($book) {
            $hadith = \App\Domain\Hadith\Models\Hadith::create([
                'book_id' => $book->id,
                'arabic_text' => 'نص الحديث',
                'translations' => ['ar' => 'نص الحديث', 'en' => 'Hadith text', 'fr' => 'Texte Hadith', 'tr' => 'Hadis Metni', 'ur' => 'حدیث کا متن'],
                'grade' => 'sahih',
                'hadith_number' => rand(1000, 9999),
            ]);
            $this->hadithId = $hadith->id;
            if (!$this->hadithId) throw new \Exception("Failed to create hadith");
        });

        // READ
        $this->test("Read hadith by ID", function () {
            $hadith = \App\Domain\Hadith\Models\Hadith::find($this->hadithId);
            if (!$hadith || $hadith->id !== $this->hadithId) throw new \Exception("Hadith not found");
        });

        // UPDATE
        $this->test("Update hadith grade", function () {
            $updated = \App\Domain\Hadith\Models\Hadith::where('id', $this->hadithId)->update([
                'grade' => 'daif',
            ]);
            if (!$updated) throw new \Exception("Failed to update hadith");
        });

        // LIST
        $this->test("List hadith with pagination", function () {
            $service = app(\App\Domain\Hadith\Services\HadithService::class);
            $hadith = $service->getSahihHadith(15);
            if (!$hadith) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete hadith", function () {
            $deleted = \App\Domain\Hadith\Models\Hadith::where('id', $this->hadithId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete hadith");
        });
    }

    // ========== MEDIA PROGRAMS TESTS ==========
    public function testMediaPrograms()
    {
        echo "\n=== Testing Media Programs Module ===\n";

        // Create category first
        $category = \App\Domain\Media\Models\MediaCategory::firstOrCreate(
            ['slug' => 'islamic-programs'],
            ['name' => ['ar' => 'برامج إسلامية', 'en' => 'Islamic Programs', 'fr' => 'Programmes Islamiques', 'tr' => 'İslami Programlar', 'ur' => 'اسلامی پروگرام']]
        );

        // CREATE
        $this->test("Create media program with episodes", function () use ($category) {
            $program = \App\Domain\Media\Models\MediaProgram::create([
                'category_id' => $category->id,
                'title' => ['ar' => 'برنامج اختبار', 'en' => 'Test Program', 'fr' => 'Programme Test', 'tr' => 'Test Programı', 'ur' => 'ٹیسٹ پروگرام'],
                'slug' => 'test-program-' . time(),
                'description' => ['ar' => 'وصف البرنامج', 'en' => 'Program description', 'fr' => 'Description du programme', 'tr' => 'Program açıklaması', 'ur' => 'پروگرام کی تفصیل'],
                'status' => 'ongoing',
            ]);
            $this->programId = $program->id;
            if (!$this->programId) throw new \Exception("Failed to create program");
        });

        // READ
        $this->test("Read media program details", function () {
            $program = \App\Domain\Media\Models\MediaProgram::find($this->programId);
            if (!$program || $program->id !== $this->programId) throw new \Exception("Program not found");
        });

        // UPDATE
        $this->test("Update program metadata", function () {
            $updated = \App\Domain\Media\Models\MediaProgram::where('id', $this->programId)->update([
                'title' => ['ar' => 'برنامج محدث', 'en' => 'Updated Program', 'fr' => 'Programme Mis à Jour', 'tr' => 'Güncellenmiş Program', 'ur' => 'اپ ڈیٹ شدہ پروگرام'],
            ]);
            if (!$updated) throw new \Exception("Failed to update program");
        });

        // LIST
        $this->test("List media programs with pagination", function () {
            $service = app(\App\Domain\Media\Services\MediaProgramService::class);
            $programs = $service->paginate(15);
            if (!$programs) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete media program", function () {
            $deleted = \App\Domain\Media\Models\MediaProgram::where('id', $this->programId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete program");
        });
    }

    // ========== MEDIA CATEGORIES TESTS ==========
    public function testMediaCategories()
    {
        echo "\n=== Testing Media Categories Module ===\n";

        // CREATE
        $this->test("Create media category", function () {
            $category = \App\Domain\Media\Models\MediaCategory::create([
                'name' => ['ar' => 'تصنيف برامج اختبار', 'en' => 'Test Media Category', 'fr' => 'Catégorie Média Test', 'tr' => 'Test Medya Kategorisi', 'ur' => 'ٹیسٹ میڈیا زمرہ'],
                'slug' => 'test-media-cat-' . time(),
                'description' => ['ar' => 'الوصف', 'en' => 'Description', 'fr' => 'Description', 'tr' => 'Açıklama', 'ur' => 'تفصیل'],
            ]);
            $this->mediaCategoryId = $category->id;
            if (!$this->mediaCategoryId) throw new \Exception("Failed to create media category");
        });

        // READ
        $this->test("Read media category by ID", function () {
            $category = \App\Domain\Media\Models\MediaCategory::find($this->mediaCategoryId);
            if (!$category || $category->id !== $this->mediaCategoryId) throw new \Exception("Media category not found");
        });

        // UPDATE
        $this->test("Update media category", function () {
            $updated = \App\Domain\Media\Models\MediaCategory::where('id', $this->mediaCategoryId)->update([
                'name' => ['ar' => 'تصنيف برامج محدث', 'en' => 'Updated Media Category', 'fr' => 'Catégorie Média Mise à Jour', 'tr' => 'Güncellenmiş Medya Kategorisi', 'ur' => 'اپ ڈیٹ شدہ میڈیا زمرہ'],
            ]);
            if (!$updated) throw new \Exception("Failed to update media category");
        });

        // LIST with FILTERS
        $this->test("List media categories with filters", function () {
            $service = app(\App\Domain\Media\Services\MediaCategoryService::class);
            $categories = $service->paginateWithFilters(50, '', [], []);
            if (!$categories) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete media category", function () {
            $deleted = \App\Domain\Media\Models\MediaCategory::where('id', $this->mediaCategoryId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete media category");
        });
    }

    // ========== QURAN TESTS ==========
    public function testQuran()
    {
        echo "\n=== Testing Quran Module ===\n";

        // Get or create surah
        $surah = \App\Domain\Quran\Models\Surah::firstOrCreate(
            ['number' => 1],
            [
                'name' => ['ar' => 'الفاتحة', 'en' => 'Al-Fatihah', 'fr' => 'Al-Fatiha', 'tr' => 'El-Fatiha', 'ur' => 'الفاتحة'],
                'name_transliteration' => 'Al-Fatihah',
                'verses_count' => 7,
            ]
        );

        // CREATE verse
        $this->test("Create Quran verse", function () use ($surah) {
            $verseNumber = rand(1, 7);
            $verse = \App\Domain\Quran\Models\Verse::create([
                'surah_id' => $surah->id,
                'verse_number' => $verseNumber,
                'verse_key' => $verseNumber, // Global verse number (1-6236), for Surah 1 it's the verse number itself
                'text_uthmani' => 'الحمد لله رب العالمين',
                'text_simple' => 'الحمد لله رب العالمين',
                'juz_number' => 1,
                'page_number' => 1,
                'translations' => ['en' => 'All praises and thanks be to Allah', 'ar' => 'الحمد لله رب العالمين', 'fr' => 'Toutes les louanges et remerciements appartiennent à Allah', 'tr' => 'Bütün hamdler Allah\'a mahsustur', 'ur' => 'تمام تعریف اللہ کے لیے ہے'],
            ]);
            $this->verseId = $verse->id;
            if (!$this->verseId) throw new \Exception("Failed to create verse");
        });

        // READ
        $this->test("Read Quran verse", function () {
            $verse = \App\Domain\Quran\Models\Verse::find($this->verseId);
            if (!$verse || $verse->id !== $this->verseId) throw new \Exception("Verse not found");
        });

        // UPDATE
        $this->test("Update verse translation", function () {
            $updated = \App\Domain\Quran\Models\Verse::where('id', $this->verseId)->update([
                'translations' => ['en' => 'Updated translation', 'ar' => 'ترجمة محدثة', 'fr' => 'Traduction mise à jour', 'tr' => 'Güncellenmiş çeviri', 'ur' => 'اپ ڈیٹ شدہ ترجمہ'],
            ]);
            if (!$updated) throw new \Exception("Failed to update verse");
        });

        // LIST
        $this->test("List Quran verses", function () {
            $service = app(\App\Domain\Quran\Services\VerseService::class);
            $verses = $service->paginateWithFilters(15, '', 'verse_key', []);
            if (!$verses) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete verse", function () {
            $deleted = \App\Domain\Quran\Models\Verse::where('id', $this->verseId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete verse");
        });
    }

    // ========== SAHABA TESTS ==========
    public function testSahaba()
    {
        echo "\n=== Testing Sahaba (Companions) Module ===\n";

        // CREATE
        $this->test("Create companion profile", function () {
            $sahabi = \App\Domain\Sahaba\Models\Sahabi::create([
                'name' => ['ar' => 'أبو بكر الصديق', 'en' => 'Abu Bakr As-Siddiq', 'fr' => 'Abou Bakr As-Siddiq', 'tr' => 'Ebu Bekir Es-Siddik', 'ur' => 'ابو بکر صدیق'],
                'slug' => 'test-sahabi-' . time(),
                'gender' => 'male',
                'status' => 'prophet_companion',
            ]);
            $this->sahabiId = $sahabi->id;
            if (!$this->sahabiId) throw new \Exception("Failed to create sahabi");
        });

        // READ
        $this->test("Read companion profile", function () {
            $sahabi = \App\Domain\Sahaba\Models\Sahabi::find($this->sahabiId);
            if (!$sahabi || $sahabi->id !== $this->sahabiId) throw new \Exception("Sahabi not found");
        });

        // UPDATE
        $this->test("Update companion information", function () {
            $updated = \App\Domain\Sahaba\Models\Sahabi::where('id', $this->sahabiId)->update([
                'gender' => 'female',
            ]);
            if (!$updated) throw new \Exception("Failed to update sahabi");
        });

        // LIST
        $this->test("List companions with pagination", function () {
            $service = app(\App\Domain\Sahaba\Services\SahabiService::class);
            $sahaba = $service->getAllCompanions(15);
            if (!$sahaba) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete companion profile", function () {
            $deleted = \App\Domain\Sahaba\Models\Sahabi::where('id', $this->sahabiId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete sahabi");
        });
    }

    // ========== GENEALOGY TESTS ==========
    public function testGenealogy()
    {
        echo "\n=== Testing Genealogy (Family Tree) Module ===\n";

        // CREATE
        $this->test("Create genealogy person entry", function () {
            $person = \App\Domain\Genealogy\Models\Person::create([
                'name' => ['ar' => 'محمد', 'en' => 'Muhammad', 'fr' => 'Muhammad', 'tr' => 'Muhammed', 'ur' => 'محمد'],
                'slug' => 'person-' . time(),
                'person_type' => 'prophet',
                'gender' => 'male',
                'is_featured' => true,
            ]);
            $this->personId = $person->id;
            if (!$this->personId) throw new \Exception("Failed to create person");
        });

        // READ
        $this->test("Read genealogy person", function () {
            $person = \App\Domain\Genealogy\Models\Person::find($this->personId);
            if (!$person || $person->id !== $this->personId) throw new \Exception("Person not found");
        });

        // UPDATE
        $this->test("Update person information", function () {
            $updated = \App\Domain\Genealogy\Models\Person::where('id', $this->personId)->update([
                'is_featured' => false,
            ]);
            if (!$updated) throw new \Exception("Failed to update person");
        });

        // LIST
        $this->test("List genealogy people with pagination", function () {
            $service = app(\App\Domain\Genealogy\Services\GenealogyService::class);
            $people = $service->getAllPeople(15);
            if (!$people) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete genealogy person", function () {
            $deleted = \App\Domain\Genealogy\Models\Person::where('id', $this->personId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete person");
        });
    }

    // ========== SEERAH TESTS ==========
    public function testSeerah()
    {
        echo "\n=== Testing Seerah (Timeline) Module ===\n";

        // CREATE
        $this->test("Create seerah timeline event", function () {
            $event = \App\Domain\Seerah\Models\SeerahEvent::create([
                'title' => ['ar' => 'حدث تاريخي', 'en' => 'Historical Event', 'fr' => 'Événement Historique', 'tr' => 'Tarihi Olay', 'ur' => 'تاریخی واقعہ'],
                'slug' => 'test-event-' . time(),
                'description' => ['ar' => 'وصف الحدث', 'en' => 'Event description', 'fr' => 'Description de l\'événement', 'tr' => 'Olay açıklaması', 'ur' => 'واقعہ کی تفصیل'],
                'year_hijri' => 1,
                'year_gregorian' => 622,
                'month' => 1,
                'day' => 1,
                'event_type' => 'birth',
            ]);
            $this->eventId = $event->id;
            if (!$this->eventId) throw new \Exception("Failed to create seerah event");
        });

        // READ
        $this->test("Read seerah event", function () {
            $event = \App\Domain\Seerah\Models\SeerahEvent::find($this->eventId);
            if (!$event || $event->id !== $this->eventId) throw new \Exception("Event not found");
        });

        // UPDATE
        $this->test("Update event details", function () {
            $updated = \App\Domain\Seerah\Models\SeerahEvent::where('id', $this->eventId)->update([
                'event_type' => 'migration',
            ]);
            if (!$updated) throw new \Exception("Failed to update event");
        });

        // LIST
        $this->test("List timeline events with pagination", function () {
            $service = app(\App\Domain\Seerah\Services\SeerahEventService::class);
            $events = $service->getAllEvents(15);
            if (!$events) throw new \Exception("Pagination failed");
        });

        // DELETE
        $this->test("Delete seerah event", function () {
            $deleted = \App\Domain\Seerah\Models\SeerahEvent::where('id', $this->eventId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete event");
        });
    }

    // ========== USERS TESTS ==========
    public function testUsers()
    {
        echo "\n=== Testing Users Module ===\n";

        // CREATE
        $this->test("Create new user account", function () {
            $user = \App\Models\User::create([
                'name' => 'Test User',
                'email' => 'testuser-' . time() . '@example.com',
                'password' => bcrypt('password123'),
            ]);
            $this->testUserId = $user->id;
            if (!$this->testUserId) throw new \Exception("Failed to create user");
        });

        // READ
        $this->test("Read user profile", function () {
            $user = \App\Models\User::find($this->testUserId);
            if (!$user || $user->id !== $this->testUserId) throw new \Exception("User not found");
        });

        // UPDATE
        $this->test("Update user information", function () {
            $updated = \App\Models\User::where('id', $this->testUserId)->update([
                'name' => 'Updated Test User',
            ]);
            if (!$updated) throw new \Exception("Failed to update user");
        });

        // ASSIGN ROLE
        $this->test("Assign role to user", function () {
            $user = \App\Models\User::find($this->testUserId);
            $user->assignRole('user');
            if (!$user->hasRole('user')) throw new \Exception("Failed to assign role");
        });

        // DELETE
        $this->test("Delete user account", function () {
            $deleted = \App\Models\User::where('id', $this->testUserId)->delete();
            if (!$deleted) throw new \Exception("Failed to delete user");
        });
    }

    public function runAllTests()
    {
        echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║         COMPREHENSIVE ADMIN PANEL TESTING SUITE               ║\n";
        echo "║                    Testing All CRUD Operations                ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n";

        $this->testCategories();
        $this->testArticles();
        $this->testTags();
        $this->testHadith();
        $this->testMediaPrograms();
        $this->testMediaCategories();
        $this->testQuran();
        $this->testSahaba();
        $this->testGenealogy();
        $this->testSeerah();
        $this->testUsers();

        $this->generateReport();
    }

    public function generateReport()
    {
        $report = "# Admin Panel Test Results\n\n";
        $report .= "**Date:** " . date('Y-m-d H:i:s') . "\n";
        $report .= "**Tested by:** " . $this->user->email . "\n";
        $report .= "**Total Tests:** " . $this->testCounts['total'] . "\n";
        $report .= "**Passed:** " . $this->testCounts['passed'] . "\n";
        $report .= "**Failed:** " . $this->testCounts['failed'] . "\n";
        $report .= "**Success Rate:** " . ($this->testCounts['total'] > 0 ? round(($this->testCounts['passed'] / $this->testCounts['total']) * 100, 2) : 0) . "%\n\n";

        $report .= "## Test Results\n\n";
        foreach ($this->results as $result) {
            $report .= $result . "\n";
        }

        $report .= "\n## Summary\n\n";
        if ($this->testCounts['failed'] === 0) {
            $report .= "✓ All tests passed successfully!\n";
        } else {
            $report .= "✗ " . $this->testCounts['failed'] . " test(s) failed. Review results above.\n";
        }

        file_put_contents('test-results.md', $report);
        echo "\n✓ Report saved to test-results.md\n";
        echo "\n=== SUMMARY ===\n";
        echo "Total: " . $this->testCounts['total'] . " | Passed: " . $this->testCounts['passed'] . " | Failed: " . $this->testCounts['failed'] . "\n";
    }
}

$tester = new AdminPanelTester();
$tester->runAllTests();
