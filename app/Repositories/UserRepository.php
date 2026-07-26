<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class UserRepository
{
    /**
     * Create a public visitor account with the non-privileged `user` role.
     *
     * The role assignment is the point of this method: admin and public accounts
     * share one users table and are separated only by a role-based gate, so an
     * account created without a role is invisible to that gate. Every public
     * registration path (web, social, API) must come through here rather than
     * calling User::create() — a fourth path that forgets the role is exactly the
     * defect this replaced. findOrCreate keeps it safe when role seeding has not
     * run, and the transaction stops a user existing without its role.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createPublicAccount(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $role = Role::findOrCreate('user', 'web');
            $user = User::create($attributes);
            $user->assignRole($role);

            return $user;
        });
    }

    public function backfillSocialAccountsWithoutRole(): int
    {
        return DB::transaction(function (): int {
            $role = Role::findOrCreate('user', 'web');
            $users = User::query()
                ->whereNotNull('provider')
                ->whereNotNull('provider_id')
                ->whereDoesntHave('roles')
                ->lockForUpdate()
                ->get();

            $users->each->assignRole($role);

            return $users->count();
        });
    }
}
