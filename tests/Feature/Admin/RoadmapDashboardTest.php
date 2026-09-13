<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Models\AdminLog;
use App\Domain\Shared\Services\DashboardService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoadmapDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_hadiths_and_companions_from_current_models(): void
    {
        Hadith::factory()->count(2)->create();
        $role = IslamicRole::query()->firstOrCreate(['slug' => 'sahabi'], ['name_ar' => 'صحابي', 'name_en' => 'Companion']);
        $companion = Muslim::factory()->create();
        $companion->roles()->attach($role);
        Muslim::factory()->create();

        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(2, $response->viewData('stats')['hadith_entries']);
        $this->assertSame(1, $response->viewData('stats')['sahaba_profiles']);
    }

    public function test_activity_feed_uses_the_avatar_path_column(): void
    {
        $user = User::factory()->create(['avatar_path' => 'avatars/reviewer.png']);
        AdminLog::query()->create([
            'user_id' => $user->id, 'action' => 'created',
            'model_type' => User::class, 'model_id' => $user->id,
        ]);

        $activity = app(DashboardService::class)->getRecentActivity();
        $this->assertCount(1, $activity);
        $this->assertSame($user->name, $activity[0]['user']['name']);
        $this->assertSame($user->avatarUrl(), $activity[0]['user']['avatar']);
    }
}
