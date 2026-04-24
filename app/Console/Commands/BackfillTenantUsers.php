<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTenantUsers extends Command
{
    protected $signature = 'tenants:backfill-users';
    protected $description = 'Backfill tenant_users table from existing tenant databases';

    public function handle(): int
    {
        $tenants = Tenant::where('status', 'active')->get();
        $count = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenant->makeCurrent();

                $users = DB::connection('tenant')->table('users')->select('email')->get();

                foreach ($users as $user) {
                    TenantUser::register($user->email, $tenant->id);
                    $count++;
                }

                $this->info("✓ {$tenant->name}: {$users->count()} users mapped");

                Tenant::forgetCurrent();
            } catch (\Throwable $e) {
                Tenant::forgetCurrent();
                $this->error("✗ {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info("Done. {$count} user-tenant mappings created.");
        return 0;
    }
}
