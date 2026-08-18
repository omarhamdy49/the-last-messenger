<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Services\TafseerService;
use App\Domain\Shared\Enums\ScheduleStatus;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Models\ContentSchedule;
use App\Domain\Shared\Services\SchedulerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Business-rule guards in SchedulerService and TafseerService.
 *
 * Both services threw exception classes that do not exist —
 * App\Exceptions\Domain\BusinessLogicException (5 sites) and
 * App\Exceptions\Domain\DuplicateResourceException (2 sites). PHP raises a
 * class-not-found Error at the throw, so:
 *
 *   - where the controller had a `catch (\Throwable)` fallback, the guard returned a
 *     generic failure with the internal "Class ... not found" text leaked to the user;
 *   - where it did not (SchedulerController::update / cancel), it was an unhandled 500.
 *
 * Either way the intended 4xx never happened, and the controllers' matching
 * `catch (BusinessLogicException)` blocks were unreachable dead code. These tests assert
 * the guards now raise a real, catchable domain exception.
 */
class BusinessRuleGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function makeTafsir(string $verseKey, string $lang): VerseTafsir
    {
        $surah = Surah::create([
            'revelation_place' => 'makkah',
            'revelation_order' => 5,
            'bismillah_pre' => true,
            'name_ar' => 'سورة الاختبار',
            'name_en' => 'Test Surah',
            'transliteration' => 'Test-Surah',
            'verses_count' => 7,
        ]);

        $verse = Verse::create([
            'surah_id' => $surah->id,
            'verse_number' => 1,
            'verse_key' => $verseKey,
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'manzil_number' => 1,
            'page_number' => 1,
        ]);

        return VerseTafsir::create([
            'verse_id' => $verse->id,
            'resource_id' => null,
            'verse_key' => $verseKey,
            'lang' => $lang,
            'text' => 'نص التفسير',
            'active' => true,
        ]);
    }

    public function test_duplicate_tafseer_raises_a_business_rule_violation(): void
    {
        $existing = $this->makeTafsir('1:1', 'ar');

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('Tafseer already exists for verse 1:1 in language ar');

        app(TafseerService::class)->create([
            'verse_id' => $existing->verse_id,
            'verse_key' => '1:1',
            'lang' => 'ar',
            'text' => 'نص آخر',
        ]);
    }

    public function test_duplicate_tafseer_on_update_raises_a_business_rule_violation(): void
    {
        $first = $this->makeTafsir('1:1', 'ar');

        $second = VerseTafsir::create([
            'verse_id' => $first->verse_id,
            'resource_id' => null,
            'verse_key' => '1:1',
            'lang' => 'en',
            'text' => 'Some tafsir',
        ]);

        $this->expectException(BusinessRuleViolationException::class);

        // Moving `$second` onto (1:1, ar) collides with `$first`.
        app(TafseerService::class)->updateTafseer($second, [
            'verse_key' => '1:1',
            'lang' => 'ar',
        ]);
    }

    /**
     * SchedulerService::updateSchedule() looks the schedule up first (raising
     * ResourceNotFoundException), so reaching the business-rule guard needs a real row
     * in a status that is not PENDING — canBeUpdated() is PENDING-only.
     */
    public function test_updating_a_non_pending_schedule_raises_a_business_rule_violation(): void
    {
        $user = User::factory()->create();

        $schedule = ContentSchedule::create([
            'schedulable_type' => 'article',
            'schedulable_id' => 1,
            'scheduled_at' => now()->addDay(),
            'action' => 'publish',
            'status' => ScheduleStatus::PUBLISHED,
            'timezone' => 'UTC',
            'created_by' => $user->id,
        ]);

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('Schedule cannot be updated in its current status');

        app(SchedulerService::class)->updateSchedule($schedule->id, ['action' => 'unpublish']);
    }

    /**
     * Regression guard for the whole defect class: an exception a service throws must
     * actually exist. `Foo::class` resolves to a string without autoloading and an
     * unused `use` never errors, so these only fail when the line runs — which is why
     * seven of them shipped.
     */
    public function test_every_domain_exception_referenced_by_these_services_exists(): void
    {
        $files = [
            'app/Domain/Shared/Services/SchedulerService.php',
            'app/Domain/Quran/Services/TafseerService.php',
            'app/Http/Controllers/Admin/SchedulerController.php',
            'app/Http/Controllers/Admin/Quran/TafseerController.php',
        ];

        foreach ($files as $file) {
            preg_match_all('/^use (App\\\\[^; ]+);/m', file_get_contents(base_path($file)), $matches);

            foreach ($matches[1] as $class) {
                $this->assertTrue(
                    class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class),
                    "[{$file}] imports [{$class}], which does not exist."
                );
            }
        }
    }
}
