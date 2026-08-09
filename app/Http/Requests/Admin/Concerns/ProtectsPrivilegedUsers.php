<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Concerns;

use App\Models\User;

/**
 * Shared authorization rules for user management.
 *
 * Admins run day-to-day user administration, but super_admin is the account
 * that can hand out super_admin. Two rules keep that boundary intact:
 *
 *  1. Only a super_admin may grant or revoke the super_admin role.
 *  2. Only a super_admin may modify or delete an account that already holds it
 *     — otherwise an admin could reset a super_admin's password and sign in as
 *     them, which is the same escalation by another route.
 */
trait ProtectsPrivilegedUsers
{
    protected const SUPER_ADMIN = 'super_admin';

    protected function callerIsSuperAdmin(): bool
    {
        return (bool) $this->user()?->hasRole(self::SUPER_ADMIN);
    }

    protected function callerManagesUsers(): bool
    {
        return (bool) $this->user()?->hasAnyRole([self::SUPER_ADMIN, 'admin']);
    }

    /**
     * True when the payload would grant the super_admin role.
     */
    protected function payloadGrantsSuperAdmin(): bool
    {
        return in_array(self::SUPER_ADMIN, (array) $this->input('roles', []), true);
    }

    /**
     * The user this request acts on, taken from either route parameter shape
     * ({user} on the resource routes, {id} on the custom ones).
     */
    protected function targetUserId(): ?int
    {
        $id = $this->route('user') ?? $this->route('id');

        if ($id instanceof User) {
            return $id->id;
        }

        return is_numeric($id) ? (int) $id : null;
    }

    protected function targetHoldsSuperAdmin(): bool
    {
        $id = $this->targetUserId();

        if ($id === null) {
            return false;
        }

        return User::whereKey($id)
            ->whereHas('roles', fn ($q) => $q->where('name', self::SUPER_ADMIN))
            ->exists();
    }

    /**
     * The common gate: caller must manage users, and a non-super_admin may
     * neither grant super_admin nor act on a super_admin account.
     */
    protected function authorizeUserManagement(): bool
    {
        if (! $this->callerManagesUsers()) {
            return false;
        }

        if ($this->callerIsSuperAdmin()) {
            return true;
        }

        return ! $this->payloadGrantsSuperAdmin() && ! $this->targetHoldsSuperAdmin();
    }
}
