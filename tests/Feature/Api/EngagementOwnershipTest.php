<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression guard for the /engagement/users/{id} IDOR.
 *
 * The endpoint returns a full activity profile (streaks, monthly counters,
 * time spent). It must only ever answer for the caller's own id.
 */
final class EngagementOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_caller_can_read_their_own_engagement_by_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['api:access']);

        $this->getJson("/api/v1/engagement/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $user->id);
    }

    public function test_caller_cannot_read_another_users_engagement(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($other, ['api:access']);

        $this->getJson("/api/v1/engagement/users/{$owner->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    /**
     * A non-existent id must not be distinguishable from a foreign one:
     * both are refused before any lookup happens, so the endpoint cannot be
     * used to enumerate which user ids exist.
     */
    public function test_unknown_user_id_is_refused_not_reported_as_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);

        $this->getJson('/api/v1/engagement/users/999999')
            ->assertForbidden();
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/engagement/users/1')
            ->assertUnauthorized();
    }
}
