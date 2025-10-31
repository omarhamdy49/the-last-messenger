<?php

/**
 * Comprehensive Admin Routes Testing Script
 * Tests all admin panel routes to ensure no errors
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AdminRouteTester
{
    private $results = [];
    private $user;
    private $passedCount = 0;
    private $failedCount = 0;
    private $totalCount = 0;

    public function __construct()
    {
        $this->user = \App\Models\User::where('email', 'supm@dwit.com')->first();
        if (!$this->user) {
            echo "✗ User supm@dwit.com not found\n";
            exit(1);
        }
        Auth::login($this->user);
        echo "✓ Authenticated as {$this->user->email}\n";
    }

    public function testRoute($method, $uri, $description, $expectedStatus = 200)
    {
        $this->totalCount++;
        try {
            $response = $this->makeRequest($method, $uri);
            $statusCode = $response->getStatusCode();

            if ($statusCode === $expectedStatus || ($expectedStatus === 'redirect' && $statusCode >= 300 && $statusCode < 400)) {
                $this->addResult("✓ PASS", $description);
                $this->passedCount++;
            } else {
                $this->addResult("✗ FAIL", "$description - Expected $expectedStatus, got $statusCode");
                $this->failedCount++;
            }
        } catch (\Exception $e) {
            $this->addResult("✗ ERROR", "$description - " . $e->getMessage());
            $this->failedCount++;
        }
    }

    private function makeRequest($method, $uri)
    {
        $request = \Illuminate\Http\Request::create($uri, $method);
        $request->setUserResolver(fn() => $this->user);

        return app('Illuminate\Routing\Router')
            ->dispatch($request);
    }

    public function addResult($status, $message)
    {
        $this->results[] = "[$status] $message";
    }

    public function runAllTests()
    {
        echo "\n╔═════════════════════════════════════════════════════════════════╗\n";
        echo "║         COMPREHENSIVE ADMIN ROUTES TESTING SUITE               ║\n";
        echo "║            Testing all admin panel routes one by one           ║\n";
        echo "╚═════════════════════════════════════════════════════════════════╝\n";

        // Dashboard
        echo "\n=== Testing Dashboard Routes ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/dashboard', 'Dashboard view');

        // Categories
        echo "\n=== Testing Categories Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/categories', 'List categories');
        $this->testRoute('GET', '/su/portal/v1/admin/categories/create', 'Create category form');

        // Get a category ID for show/edit/delete tests
        $category = \App\Domain\Blog\Models\Category::first();
        if ($category) {
            $this->testRoute('GET', "/su/portal/v1/admin/categories/{$category->id}", 'Show category');
            $this->testRoute('GET', "/su/portal/v1/admin/categories/{$category->id}/edit", 'Edit category form');
        }

        // Articles
        echo "\n=== Testing Articles Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/articles', 'List articles');
        $this->testRoute('GET', '/su/portal/v1/admin/articles/create', 'Create article form');

        $article = \App\Domain\Blog\Models\Article::first();
        if ($article) {
            $this->testRoute('GET', "/su/portal/v1/admin/articles/{$article->id}", 'Show article');
            $this->testRoute('GET', "/su/portal/v1/admin/articles/{$article->id}/edit", 'Edit article form');
        }

        // Tags
        echo "\n=== Testing Tags Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/tags', 'List tags');
        $this->testRoute('GET', '/su/portal/v1/admin/tags/create', 'Create tag form');

        $tag = \App\Domain\Blog\Models\Tag::first();
        if ($tag) {
            $this->testRoute('GET', "/su/portal/v1/admin/tags/{$tag->id}", 'Show tag');
            $this->testRoute('GET', "/su/portal/v1/admin/tags/{$tag->id}/edit", 'Edit tag form');
        }

        // Series
        echo "\n=== Testing Series Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/series', 'List series');
        $this->testRoute('GET', '/su/portal/v1/admin/series/create', 'Create series form');

        $series = \App\Domain\Blog\Models\Series::first();
        if ($series) {
            $this->testRoute('GET', "/su/portal/v1/admin/series/{$series->id}", 'Show series');
            $this->testRoute('GET', "/su/portal/v1/admin/series/{$series->id}/edit", 'Edit series form');
        }

        // Hadith
        echo "\n=== Testing Hadith Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/hadith', 'List hadith');
        $this->testRoute('GET', '/su/portal/v1/admin/hadith/create', 'Create hadith form');

        $hadith = \App\Domain\Hadith\Models\Hadith::first();
        if ($hadith) {
            $this->testRoute('GET', "/su/portal/v1/admin/hadith/{$hadith->id}", 'Show hadith');
            $this->testRoute('GET', "/su/portal/v1/admin/hadith/{$hadith->id}/edit", 'Edit hadith form');
        }

        // Hadith Narrators
        echo "\n=== Testing Hadith Narrators Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/hadith-narrators', 'List hadith narrators');
        $this->testRoute('GET', '/su/portal/v1/admin/hadith-narrators/create', 'Create narrator form');

        $narrator = \App\Domain\Hadith\Models\HadithNarrator::first();
        if ($narrator) {
            $this->testRoute('GET', "/su/portal/v1/admin/hadith-narrators/{$narrator->id}", 'Show narrator');
            $this->testRoute('GET', "/su/portal/v1/admin/hadith-narrators/{$narrator->id}/edit", 'Edit narrator form');
        }

        // Media Programs
        echo "\n=== Testing Media Programs Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/media-programs', 'List media programs');
        $this->testRoute('GET', '/su/portal/v1/admin/media-programs/create', 'Create program form');

        $program = \App\Domain\Media\Models\MediaProgram::first();
        if ($program) {
            $this->testRoute('GET', "/su/portal/v1/admin/media-programs/{$program->id}", 'Show program');
            $this->testRoute('GET', "/su/portal/v1/admin/media-programs/{$program->id}/edit", 'Edit program form');
        }

        // Media Categories
        echo "\n=== Testing Media Categories Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/media-categories', 'List media categories');
        $this->testRoute('GET', '/su/portal/v1/admin/media-categories/create', 'Create media category form');

        $mediaCategory = \App\Domain\Media\Models\MediaCategory::first();
        if ($mediaCategory) {
            $this->testRoute('GET', "/su/portal/v1/admin/media-categories/{$mediaCategory->id}", 'Show media category');
            $this->testRoute('GET', "/su/portal/v1/admin/media-categories/{$mediaCategory->id}/edit", 'Edit media category form');
        }

        // Quran - Surahs
        echo "\n=== Testing Quran Module (Surahs) ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/surahs', 'List surahs');

        $surah = \App\Domain\Quran\Models\Surah::first();
        if ($surah) {
            $this->testRoute('GET', "/su/portal/v1/admin/surahs/{$surah->id}", 'Show surah');
            $this->testRoute('GET', "/su/portal/v1/admin/surahs/{$surah->id}/edit", 'Edit surah form');
        }

        // Quran - Verses
        echo "\n=== Testing Quran Module (Verses) ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/verses', 'List verses');

        // Sahaba
        echo "\n=== Testing Sahaba Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/sahaba', 'List companions');
        $this->testRoute('GET', '/su/portal/v1/admin/sahaba/create', 'Create companion form');

        $sahabi = \App\Domain\Sahaba\Models\Sahabi::first();
        if ($sahabi) {
            $this->testRoute('GET', "/su/portal/v1/admin/sahaba/{$sahabi->id}", 'Show companion');
            $this->testRoute('GET', "/su/portal/v1/admin/sahaba/{$sahabi->id}/edit", 'Edit companion form');
        }

        // Genealogy
        echo "\n=== Testing Genealogy Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/genealogy', 'List genealogy people');
        $this->testRoute('GET', '/su/portal/v1/admin/genealogy/create', 'Create person form');

        $person = \App\Domain\Genealogy\Models\Person::first();
        if ($person) {
            $this->testRoute('GET', "/su/portal/v1/admin/genealogy/{$person->id}", 'Show person');
            $this->testRoute('GET', "/su/portal/v1/admin/genealogy/{$person->id}/edit", 'Edit person form');
        }

        // Seerah Events
        echo "\n=== Testing Seerah Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/seerah-events', 'List seerah events');
        $this->testRoute('GET', '/su/portal/v1/admin/seerah-events/create', 'Create event form');

        $event = \App\Domain\Seerah\Models\SeerahEvent::first();
        if ($event) {
            $this->testRoute('GET', "/su/portal/v1/admin/seerah-events/{$event->id}", 'Show event');
            $this->testRoute('GET', "/su/portal/v1/admin/seerah-events/{$event->id}/edit", 'Edit event form');
        }

        // Users
        echo "\n=== Testing Users Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/users', 'List users');
        $this->testRoute('GET', '/su/portal/v1/admin/users/create', 'Create user form');

        $anotherUser = \App\Models\User::where('id', '!=', $this->user->id)->first();
        if ($anotherUser) {
            $this->testRoute('GET', "/su/portal/v1/admin/users/{$anotherUser->id}", 'Show user');
            $this->testRoute('GET', "/su/portal/v1/admin/users/{$anotherUser->id}/edit", 'Edit user form');
        }

        // Roles
        echo "\n=== Testing Roles Module ===\n";
        $this->testRoute('GET', '/su/portal/v1/admin/roles', 'List roles');

        // Save results to file
        $this->saveResults();
    }

    public function saveResults()
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $successRate = $this->totalCount > 0 ? round(($this->passedCount / $this->totalCount) * 100, 2) : 0;

        $report = "# Admin Routes Test Report\n\n";
        $report .= "**Date:** $timestamp\n";
        $report .= "**Tested by:** {$this->user->email}\n";
        $report .= "**Total Routes Tested:** {$this->totalCount}\n";
        $report .= "**Passed:** {$this->passedCount}\n";
        $report .= "**Failed:** {$this->failedCount}\n";
        $report .= "**Success Rate:** {$successRate}%\n\n";

        $report .= "## Test Results\n\n";
        foreach ($this->results as $result) {
            $report .= "$result\n";
        }

        $report .= "\n## Summary\n\n";
        if ($this->failedCount === 0) {
            $report .= "✓ All routes tested successfully!\n";
        } else {
            $report .= "✗ {$this->failedCount} route(s) failed. Review results above.\n";
        }

        file_put_contents('test-admin-routes-results.md', $report);
        echo "\n✓ Report saved to test-admin-routes-results.md\n";
    }

    public function displaySummary()
    {
        echo "\n=== SUMMARY ===\n";
        echo "Total: {$this->totalCount} | Passed: {$this->passedCount} | Failed: {$this->failedCount}\n";
    }
}

// Run tests
$tester = new AdminRouteTester();
$tester->runAllTests();
$tester->displaySummary();
