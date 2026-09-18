<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Enums\ScheduleStatus;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Models\ContentSchedule;
use App\Domain\Shared\Services\SchedulerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Business-rule guards in SchedulerService.
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
            'app/Http/Controllers/Admin/SchedulerController.php',
            'app/Domain/Quran/Services/ResourceService.php',
            'app/Http/Controllers/Admin/Quran/ResourceRowController.php',
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
