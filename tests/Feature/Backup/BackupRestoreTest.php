<?php

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\BackupService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
        'permission.testing' => true,
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->plan = Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['backup'],
        'is_active' => true,
    ]);

    $this->tenant = Tenant::create([
        'name' => 'Backup Clinic',
        'slug' => 'backup-clinic',
        'domain' => 'backup-clinic.test',
        'database' => 'tenant_backup_clinic',
        'email' => 'clinic@example.com',
        'status' => 'active',
        'plan_id' => $this->plan->id,
    ]);

    TenantUser::register('admin@clinic.test', $this->tenant->id);

    Subscription::create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'amount' => 99,
        'currency' => 'PKR',
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $this->tenant);

    $this->user = User::create([
        'name' => 'Clinic Admin',
        'email' => 'admin@clinic.test',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

afterEach(function () {
    $paths = [
        storage_path('app/backups/tenants/backup-clinic'),
        storage_path('app/public/tenants/backup-clinic'),
    ];

    foreach ($paths as $path) {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            @rmdir($path);
        }
    }
});

function zipEntry(string $zipPath, string $name): string|false
{
    $zip = new ZipArchive;
    $zip->open($zipPath);
    $contents = $zip->getFromName($name);
    $zip->close();

    return $contents;
}

it('names the backup zip from the hospital name in settings', function () {
    Setting::set('hospital_name', 'Hassan Health Care Centre');

    $filename = app(BackupService::class)->create('database');

    expect($filename)->toStartWith('hassan-health-care-centre_database_')
        ->and($filename)->toEndWith('.zip');
});

it('puts tenant sql and landlord tenant data in a database backup zip', function () {
    $filename = app(BackupService::class)->create('database');
    $path = app(BackupService::class)->path($filename);

    $sql = zipEntry($path, 'database/tenant.sql');
    $landlord = json_decode(zipEntry($path, 'database/landlord_tenant.json'), true);
    $info = json_decode(zipEntry($path, 'backup_info.json'), true);

    expect($sql)->toContain('admin@clinic.test')
        ->and($sql)->toContain('Clinic Admin')
        ->and($landlord['tenant']['slug'])->toBe('backup-clinic')
        ->and(collect($landlord['tenant_users'])->pluck('email')->all())->toContain('admin@clinic.test')
        ->and($landlord['subscriptions'])->not->toBeEmpty()
        ->and($info['type'])->toBe('database')
        ->and($info['tenant_slug'])->toBe('backup-clinic')
        ->and($info['database'])->toBe('tenant')
        ->and(zipEntry($path, 'storage/public/.keep'))->toBeFalse();
});

it('puts tenant files in a files backup zip', function () {
    $dir = storage_path('app/public/'.tenant_storage_path());
    mkdir($dir, 0755, true);
    file_put_contents($dir.'/logo.txt', 'clinic-logo');

    $filename = app(BackupService::class)->create('files');
    $path = app(BackupService::class)->path($filename);

    expect(zipEntry($path, 'storage/public/logo.txt'))->toBe('clinic-logo')
        ->and(zipEntry($path, 'database/tenant.sql'))->toBeFalse()
        ->and(json_decode(zipEntry($path, 'backup_info.json'), true)['type'])->toBe('files');
});

it('includes database and files in a full backup zip', function () {
    $dir = storage_path('app/public/'.tenant_storage_path());
    mkdir($dir, 0755, true);
    file_put_contents($dir.'/logo.txt', 'clinic-logo');

    $filename = app(BackupService::class)->create('full');
    $path = app(BackupService::class)->path($filename);

    expect(zipEntry($path, 'database/tenant.sql'))->toContain('admin@clinic.test')
        ->and(zipEntry($path, 'storage/public/logo.txt'))->toBe('clinic-logo')
        ->and(json_decode(zipEntry($path, 'backup_info.json'), true)['type'])->toBe('full');
});

it('restores tenant database rows and landlord tenant-scoped rows', function () {
    $service = app(BackupService::class);
    $filename = $service->create('database');

    $this->user->update(['name' => 'Changed Name']);
    TenantUser::register('new@clinic.test', $this->tenant->id);

    $other = Tenant::create([
        'name' => 'Other Clinic',
        'slug' => 'other-clinic',
        'domain' => 'other-clinic.test',
        'database' => 'tenant_other_clinic',
        'status' => 'active',
        'plan_id' => $this->plan->id,
    ]);
    TenantUser::register('other@clinic.test', $other->id);

    $service->restore($filename);

    expect(User::where('email', 'admin@clinic.test')->first()->name)->toBe('Clinic Admin')
        ->and(TenantUser::where('tenant_id', $this->tenant->id)->pluck('email')->all())->toBe(['admin@clinic.test'])
        ->and(TenantUser::where('email', 'other@clinic.test')->where('tenant_id', $other->id)->exists())->toBeTrue()
        ->and(Subscription::where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('restores tenant files into the tenant storage prefix', function () {
    $dir = storage_path('app/public/'.tenant_storage_path());
    mkdir($dir, 0755, true);
    file_put_contents($dir.'/logo.txt', 'clinic-logo');

    $service = app(BackupService::class);
    $filename = $service->create('files');

    unlink($dir.'/logo.txt');
    file_put_contents($dir.'/extra.txt', 'should-go');

    $service->restore($filename);

    expect(file_get_contents($dir.'/logo.txt'))->toBe('clinic-logo')
        ->and(file_exists($dir.'/extra.txt'))->toBeFalse();
});

it('refuses to restore a backup from another tenant', function () {
    $dir = storage_path('app/backups/'.tenant_storage_path());
    mkdir($dir, 0755, true);

    $zip = new ZipArchive;
    $zip->open($dir.'/foreign.zip', ZipArchive::CREATE);
    $zip->addFromString('backup_info.json', json_encode([
        'type' => 'database',
        'tenant_slug' => 'someone-else',
    ]));
    $zip->close();

    expect(fn () => app(BackupService::class)->restore('foreign.zip'))
        ->toThrow(RuntimeException::class, 'This backup belongs to a different tenant');
});

it('creates a database backup zip with tenant sql via http', function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('create backup', 'web');
    $this->user->givePermissionTo('create backup');

    $this->actingAs($this->user)
        ->post(route('backup.create'), ['type' => 'database'])
        ->assertRedirect(route('backup.index'))
        ->assertSessionHas('success');

    $zips = glob(storage_path('app/backups/'.tenant_storage_path().'/*.zip'));
    expect($zips)->not->toBeEmpty()
        ->and(zipEntry($zips[0], 'database/tenant.sql'))->toContain('admin@clinic.test')
        ->and(zipEntry($zips[0], 'database/landlord_tenant.json'))->toContain('backup-clinic');
});
