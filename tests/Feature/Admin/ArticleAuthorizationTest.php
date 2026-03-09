<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_cannot_access_admin_articles(): void
    {
        $response = $this->get('/su/portal/v1/admin/articles');

        // Unauthenticated requests are redirected to the login page
        $response->assertRedirect();
    }

    public function test_non_admin_user_cannot_access_admin_panel(): void
    {
        $user = $this->createUser();

        // Non-admin web requests are redirected to login with an error
        $response = $this->actingAs($user)->get('/su/portal/v1/admin/articles');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_user_can_access_article_list(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get('/su/portal/v1/admin/articles');

        $response->assertOk();
    }

    public function test_super_admin_user_can_access_article_list(): void
    {
        $user = $this->createAdminUser([], 'super_admin');

        $response = $this->actingAs($user)->get('/su/portal/v1/admin/articles');

        $response->assertOk();
    }

    public function test_editor_user_can_access_article_list(): void
    {
        $user = $this->createAdminUser([], 'editor');

        $response = $this->actingAs($user)->get('/su/portal/v1/admin/articles');

        $response->assertOk();
    }

    public function test_json_request_from_non_admin_returns_forbidden(): void
    {
        $user = $this->createUser();

        // JSON requests from non-admin users receive a 403 Forbidden response
        $response = $this->actingAs($user)->getJson('/su/portal/v1/admin/articles');

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_json_request_returns_unauthorized(): void
    {
        $response = $this->getJson('/su/portal/v1/admin/articles');

        // Laravel's built-in auth middleware returns 401 for JSON requests.
        // The response is either from Laravel's auth middleware or AdminAuthMiddleware.
        $response->assertUnauthorized();
    }
}
