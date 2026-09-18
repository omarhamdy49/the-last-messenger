<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiClientAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_index_renders_in_arabic(): void
    {
        ApiClient::factory()->create(['name' => 'Mobile app']);

        $this->get(route('admin.settings.api-clients.index'))
            ->assertOk()
            ->assertSee('Mobile app')
            ->assertSee(__('admin.api_clients.title', [], 'ar'));
    }

    public function test_create_shows_the_secret_once_and_stores_only_its_hash(): void
    {
        $response = $this->post(route('admin.settings.api-clients.store'), [
            'name' => 'Mobile app',
            'scopes' => ['quran', 'media'],
            'allowed_origins' => "https://app.example.com\nhttps://admin.example.com",
            'rate_limit_per_minute' => 120,
            'is_active' => '1',
        ])->assertOk();

        $client = ApiClient::query()->where('name', 'Mobile app')->firstOrFail();
        $secret = $response->viewData('secret');

        $this->assertIsString($secret);
        $response->assertSee($secret)->assertSee($client->key);
        $this->assertTrue($client->secretMatches($secret));
        $this->assertStringNotContainsString($secret, (string) json_encode($client->getAttributes()));
        $this->assertSame(['quran', 'media'], $client->scopes);
        $this->assertSame(['https://app.example.com', 'https://admin.example.com'], $client->allowed_origins);

        // The index never shows a secret.
        $this->get(route('admin.settings.api-clients.index'))->assertDontSee($secret);
    }

    public function test_rotate_changes_the_secret_but_keeps_the_key(): void
    {
        $client = ApiClient::factory()->withSecret('old')->create();
        $key = $client->key;

        $response = $this->post(route('admin.settings.api-clients.rotate', $client))->assertOk();
        $client->refresh();

        $this->assertSame($key, $client->key);
        $this->assertFalse($client->secretMatches('old'));
        $this->assertTrue($client->secretMatches($response->viewData('secret')));
    }

    public function test_toggle_revokes_and_the_api_rejects_the_client_immediately(): void
    {
        $client = ApiClient::factory()->withSecret('s')->create();
        $this->post(route('admin.settings.api-clients.toggle', $client))->assertRedirect();

        $this->withoutApiClient()
            ->getJson('/api/v1/languages/current', ['X-Api-Key' => $client->key, 'X-Api-Secret' => 's'])
            ->assertUnauthorized();
    }

    public function test_update_and_delete(): void
    {
        $client = ApiClient::factory()->create();

        $this->put(route('admin.settings.api-clients.update', $client), [
            'name' => 'Renamed', 'scopes' => ['*'], 'rate_limit_per_minute' => 30, 'is_active' => '1',
        ])->assertRedirect(route('admin.settings.api-clients.index'));
        $this->assertSame('Renamed', $client->fresh()->name);

        $this->delete(route('admin.settings.api-clients.destroy', $client))->assertRedirect();
        $this->assertNull($client->fresh());
    }

    public function test_invalid_payloads_are_rejected(): void
    {
        $this->post(route('admin.settings.api-clients.store'), [
            'name' => 'X', 'scopes' => ['everything'], 'rate_limit_per_minute' => 60,
        ])->assertSessionHasErrors('scopes.0');

        $this->post(route('admin.settings.api-clients.store'), [
            'name' => 'X', 'scopes' => ['*'], 'rate_limit_per_minute' => 60, 'allowed_origins' => 'https://ok.example/path?x=1',
        ])->assertSessionHasErrors('allowed_origins.0');

        $this->post(route('admin.settings.api-clients.store'), [
            'name' => 'X', 'scopes' => ['*'], 'rate_limit_per_minute' => 60, 'key' => 'chosen-key',
        ])->assertSessionHasErrors('unexpected_fields');

        $this->assertSame(0, ApiClient::query()->count());
    }

    public function test_users_without_the_permission_are_forbidden(): void
    {
        $editor = $this->createAdminUser(role: 'editor');
        $this->actingAs($editor, 'admin');

        $this->get(route('admin.settings.api-clients.index'))->assertForbidden();
    }

    public function test_cli_command_creates_a_client_and_prints_both_values(): void
    {
        $this->artisan('api:client:create', ['name' => 'CLI app', '--scopes' => ['quran'], '--limit' => 90])
            ->expectsOutputToContain('X-Api-Key:')
            ->expectsOutputToContain('X-Api-Secret:')
            ->assertSuccessful();

        $client = ApiClient::query()->where('name', 'CLI app')->firstOrFail();
        $this->assertSame(['quran'], $client->scopes);
        $this->assertSame(90, $client->rate_limit_per_minute);

        $this->artisan('api:client:create', ['name' => 'Bad', '--scopes' => ['nope']])->assertFailed();
    }
}
