<?php

namespace App\Console\Commands;

use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create
                            {name : The hospital/clinic name}
                            {--slug= : URL-safe subdomain slug (auto-generated from name if omitted)}
                            {--email= : Hospital contact email}
                            {--admin-name=Admin : Admin user name}
                            {--admin-email= : Admin user email (required)}
                            {--admin-password=password : Admin user password}
                            {--sync : Run provisioning synchronously instead of queued}';

    protected $description = 'Provision a new tenant with database, migrations, and seed data';

    public function handle(TenantProvisioningService $provisioning): int
    {
        $name = $this->argument('name');
        $adminEmail = $this->option('admin-email');

        if (! $adminEmail) {
            $adminEmail = $this->ask('Admin email address');
        }

        try {
            $tenant = $provisioning->provision(
                data: [
                    'name'           => $name,
                    'slug'           => $this->option('slug'),
                    'email'          => $this->option('email'),
                    'admin_name'     => $this->option('admin-name'),
                    'admin_email'    => $adminEmail,
                    'admin_password' => $this->option('admin-password'),
                ],
                async: ! $this->option('sync'),
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Tenant created: {$tenant->name}");
        $this->info("  Subdomain: {$tenant->domain}");
        $this->info("  Database:  {$tenant->database}");
        $this->info("  Status:    {$tenant->status}");

        if ($this->option('sync')) {
            $tenant->refresh();
            $this->info("✓ Provisioning complete. Tenant is {$tenant->status}.");
            $this->info("  URL: http://{$tenant->domain}");
        } else {
            $this->info('Provisioning jobs dispatched to queue. Run `php artisan queue:work` to process.');
        }

        return self::SUCCESS;
    }
}
