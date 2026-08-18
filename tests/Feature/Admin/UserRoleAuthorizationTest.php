<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guards user and role management against privilege escalation.
 *
 * The admin route group admits editors, so the user/role endpoints need their
 * own super_admin gate: assign-roles and assign-permissions can grant
 * super_admin, and bulk-delete can remove admin accounts.
 */
class UserRoleAuthorizationTest extends TestCase
{
    /**
     * Passwords here satisfy the admin policy: 12+ chars, mixed case, a number and a symbol.
     * Admin-created accounts used to have the weakest policy in the application (bare
     * min:8) despite reaching /su/portal/v1/admin — weaker than public registration.
     */
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_editor_cannot_grant_themselves_super_admin(): void
    {
        $editor = $this->createAdminUser(['email' => 'editor@example.com'], 'editor');

        $this->actingAs($editor, 'admin')
            ->post(route('admin.users.assign-roles', $editor->id), [
                'roles' => ['super_admin'],
            ])
            ->assertForbidden();

        $this->assertFalse($editor->fresh()->hasRole('super_admin'));
    }

    public function test_admin_cannot_grant_super_admin(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $target = $this->createUser(['email' => 'target@example.com']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.assign-roles', $target->id), [
                'roles' => ['super_admin'],
            ])
            ->assertForbidden();

        $this->assertFalse($target->fresh()->hasRole('super_admin'));
    }

    public function test_admin_can_assign_a_non_privileged_role(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $target = $this->createUser(['email' => 'target@example.com']);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.users.assign-roles', $target->id), [
                'roles' => ['writer'],
            ])
            ->assertOk();

        $this->assertTrue($target->fresh()->hasRole('writer'));
    }

    public function test_admin_can_update_a_regular_user(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $target = $this->createUser(['email' => 'target@example.com', 'name' => 'Before']);

        $this->actingAs($admin, 'admin')
            ->putJson(route('admin.users.update', $target->id), [
                'name' => 'After',
                'email' => 'target@example.com',
            ])
            ->assertOk();

        $this->assertSame('After', $target->fresh()->name);
    }

    public function test_admin_cannot_update_a_super_admin_account(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $victim = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');

        $this->actingAs($admin, 'admin')
            ->putJson(route('admin.users.update', $victim->id), [
                'name' => 'Hijacked',
                'password' => 'Str0ng!Portal#Pass',
                'password_confirmation' => 'Str0ng!Portal#Pass',
            ])
            ->assertForbidden();

        $this->assertSame('super@example.com', $victim->fresh()->email);
        $this->assertNotSame('Hijacked', $victim->fresh()->name);
    }

    public function test_admin_cannot_open_the_edit_page_of_a_super_admin(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $victim = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.edit', $victim->id))
            ->assertForbidden();
    }

    public function test_admin_cannot_bulk_delete_a_super_admin(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $victim = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.bulk-delete'), ['ids' => [$victim->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_admin_cannot_edit_role_permissions(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);
        $roleId = Role::where('name', 'admin')->value('id');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.roles.assign-permissions', $roleId), [
                'permissions' => ['users.delete'],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_read_the_role_catalogue_for_the_edit_form(): void
    {
        $admin = $this->createAdminUser(['email' => 'admin@example.com']);

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.roles.index'))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_edit_page_renders_the_user_with_assigned_roles(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');
        $target = $this->createUser(['email' => 'target@example.com', 'name' => 'Target User']);
        $target->assignRole('editor');

        $response = $this->actingAs($superAdmin, 'admin')
            ->get(route('admin.users.edit', $target->id))
            ->assertOk();

        // The form is server-rendered, so the record and its roles must be in
        // the markup — this is what the old two-XHR version failed to load.
        $response->assertSee('Target User');
        $response->assertSee('target@example.com');
        $response->assertSee('editor');
    }

    public function test_super_admin_can_assign_roles(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');
        $target = $this->createUser(['email' => 'target@example.com']);

        $this->actingAs($superAdmin, 'admin')
            ->postJson(route('admin.users.assign-roles', $target->id), [
                'roles' => ['editor'],
            ])
            ->assertOk();

        $this->assertTrue($target->fresh()->hasRole('editor'));
    }

    public function test_editor_cannot_assign_permissions_to_a_role(): void
    {
        $editor = $this->createAdminUser(['email' => 'editor@example.com'], 'editor');
        $roleId = Role::where('name', 'editor')->value('id');

        $this->actingAs($editor, 'admin')
            ->post(route('admin.roles.assign-permissions', $roleId), [
                'permissions' => ['users.delete'],
            ])
            ->assertForbidden();
    }

    public function test_editor_cannot_bulk_delete_users(): void
    {
        $editor = $this->createAdminUser(['email' => 'editor@example.com'], 'editor');
        $victim = $this->createUser(['email' => 'victim@example.com']);

        $this->actingAs($editor, 'admin')
            ->post(route('admin.users.bulk-delete'), ['ids' => [$victim->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_editor_cannot_delete_a_user(): void
    {
        $editor = $this->createAdminUser(['email' => 'editor@example.com'], 'editor');
        $victim = $this->createUser(['email' => 'victim@example.com']);

        $this->actingAs($editor, 'admin')
            ->delete(route('admin.users.destroy', $victim->id))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    /**
     * The index deletes via a plain HTML form, so a JSON body would be rendered
     * in the browser at /users/{id} instead of returning to the list.
     */
    public function test_html_delete_redirects_to_index_instead_of_returning_json(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');
        $victim = $this->createUser(['email' => 'victim@example.com']);

        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.users.destroy', $victim->id))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    }

    public function test_json_delete_still_returns_json(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');
        $victim = $this->createUser(['email' => 'victim@example.com']);

        $this->actingAs($superAdmin, 'admin')
            ->deleteJson(route('admin.users.destroy', $victim->id))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_self_deletion_is_refused_with_a_redirect_for_html(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');

        $this->actingAs($superAdmin, 'admin')
            ->delete(route('admin.users.destroy', $superAdmin->id))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_html_store_redirects_to_index_instead_of_returning_json(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');

        $this->actingAs($superAdmin, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'New Editor',
                'email' => 'new-editor@example.com',
                'password' => 'Str0ng!Portal#Pass',
                'password_confirmation' => 'Str0ng!Portal#Pass',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'new-editor@example.com']);
    }

    public function test_assign_roles_audit_log_records_the_previous_roles(): void
    {
        $superAdmin = $this->createAdminUser(['email' => 'super@example.com'], 'super_admin');
        $target = $this->createUser(['email' => 'target@example.com']);
        $target->assignRole('editor');

        $this->actingAs($superAdmin, 'admin')
            ->postJson(route('admin.users.assign-roles', $target->id), [
                'roles' => ['writer'],
            ])
            ->assertOk();

        $log = \DB::table('admin_logs')
            ->where('action', 'assign_roles')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log, 'assign_roles action should be logged');
        $this->assertStringContainsString('editor', (string) $log->old_values);
    }

    public function test_user_factory_baseline_roles_are_unaffected(): void
    {
        $this->seedRolesAndPermissions();
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyRole(['super_admin', 'admin', 'editor']));
    }
}
