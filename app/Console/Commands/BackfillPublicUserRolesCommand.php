<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use Illuminate\Console\Command;

final class BackfillPublicUserRolesCommand extends Command
{
    protected $signature = 'users:backfill-public-role';

    protected $description = 'Assign the public user role to role-less social accounts';

    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $updated = $this->users->backfillSocialAccountsWithoutRole();

        $this->info("Assigned the user role to {$updated} social account(s).");

        return self::SUCCESS;
    }
}
