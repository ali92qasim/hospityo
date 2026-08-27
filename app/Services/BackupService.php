<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Services\Backup\DatabaseDumper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BackupService
{
    public function __construct(private DatabaseDumper $dumper) {}

    public function create(string $type = 'full'): string
    {
        $type = in_array($type, ['full', 'database', 'files'], true) ? $type : 'full';
        $tenant = $this->currentTenant();

        $backupName = $this->backupFilename($type, $tenant);
        $directory = $this->backupDirectory();

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $zipFile = $directory.DIRECTORY_SEPARATOR.$backupName;
        $zip = new ZipArchive;

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create backup file');
        }

        if (in_array($type, ['full', 'database'], true)) {
            $zip->addFromString('database/tenant.sql', $this->dumper->dump('tenant'));
            $zip->addFromString(
                'database/landlord_tenant.json',
                json_encode($this->exportLandlordSlice($tenant), JSON_PRETTY_PRINT)
            );
        }

        if (in_array($type, ['full', 'files'], true)) {
            $this->addTenantFiles($zip);
        }

        $zip->addFromString('backup_info.json', json_encode([
            'type' => $type,
            'created_at' => Carbon::now()->toDateTimeString(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database' => 'tenant',
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'tenant_database' => $tenant->database,
            'driver' => config('database.connections.tenant.driver'),
        ], JSON_PRETTY_PRINT));

        $zip->close();

        return $backupName;
    }

    public function restore(string $filename): void
    {
        $path = $this->path($filename);

        if (! is_file($path)) {
            throw new RuntimeException('Backup file not found');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open backup file');
        }

        $tempPath = storage_path('app/temp_restore_'.uniqid());
        $zip->extractTo($tempPath);
        $zip->close();

        try {
            $metadata = [];
            $metadataFile = $tempPath.DIRECTORY_SEPARATOR.'backup_info.json';
            if (is_file($metadataFile)) {
                $metadata = json_decode((string) file_get_contents($metadataFile), true) ?: [];
            }

            $tenant = $this->currentTenant();
            if (! empty($metadata['tenant_slug']) && $metadata['tenant_slug'] !== $tenant->slug) {
                throw new RuntimeException('This backup belongs to a different tenant');
            }

            $sqlFile = $tempPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'tenant.sql';
            if (is_file($sqlFile)) {
                $this->dumper->restore('tenant', (string) file_get_contents($sqlFile));
            }

            $landlordFile = $tempPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'landlord_tenant.json';
            if (is_file($landlordFile)) {
                $this->restoreLandlordSlice(
                    $tenant,
                    json_decode((string) file_get_contents($landlordFile), true) ?: []
                );
            }

            $filesPath = $tempPath.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'public';
            if (is_dir($filesPath)) {
                $this->restoreFiles($filesPath);
            }

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
        } finally {
            $this->deleteDirectory($tempPath);
        }
    }

    /**
     * @return list<array{name: string, size: string, date: Carbon, path: string}>
     */
    public function list(): array
    {
        $directory = $this->backupDirectory();

        if (! is_dir($directory)) {
            return [];
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.zip') ?: [];
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->formatBytes((int) filesize($file)),
                'date' => Carbon::createFromTimestamp(filemtime($file)),
                'path' => $file,
            ];
        }

        usort($backups, fn (array $a, array $b) => $b['date']->timestamp <=> $a['date']->timestamp);

        return $backups;
    }

    public function path(string $filename): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR.$this->assertSafeFilename($filename);
    }

    public function delete(string $filename): void
    {
        $path = $this->path($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }

    protected function currentTenant(): Tenant
    {
        $tenant = Tenant::current();

        if (! $tenant || blank($tenant->slug)) {
            throw new RuntimeException('No active tenant');
        }

        return $tenant;
    }

    protected function backupFilename(string $type, Tenant $tenant): string
    {
        $hospital = setting('hospital_name') ?: $tenant->name ?: $tenant->slug;
        $slug = Str::slug((string) $hospital) ?: Str::slug((string) $tenant->slug) ?: 'hospital';

        return $slug.'_'.$type.'_'.Carbon::now()->format('Y-m-d_His').'.zip';
    }

    protected function backupDirectory(): string
    {
        return storage_path('app/backups/'.tenant_storage_path());
    }

    protected function assertSafeFilename(string $filename): string
    {
        $safe = basename($filename);

        if ($safe !== $filename || ! str_ends_with(strtolower($safe), '.zip')) {
            throw new RuntimeException('Invalid backup filename');
        }

        return $safe;
    }

    /**
     * @return array{tenant: array<string, mixed>, tenant_users: array<int, array<string, mixed>>, subscriptions: array<int, array<string, mixed>>, subscription_payments: array<int, array<string, mixed>>}
     */
    protected function exportLandlordSlice(Tenant $tenant): array
    {
        $tenant->refresh();

        return [
            'tenant' => $tenant->toArray(),
            'tenant_users' => TenantUser::where('tenant_id', $tenant->id)->get()->toArray(),
            'subscriptions' => Subscription::where('tenant_id', $tenant->id)->get()->toArray(),
            'subscription_payments' => SubscriptionPayment::where('tenant_id', $tenant->id)->get()->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function restoreLandlordSlice(Tenant $current, array $data): void
    {
        SubscriptionPayment::where('tenant_id', $current->id)->delete();
        Subscription::where('tenant_id', $current->id)->delete();
        TenantUser::where('tenant_id', $current->id)->delete();

        foreach ($data['tenant_users'] ?? [] as $row) {
            TenantUser::create([
                'email' => $row['email'],
                'tenant_id' => $current->id,
                'login_token' => $row['login_token'] ?? null,
            ]);
        }

        $planId = $current->plan_id;
        $backupPlanId = $data['tenant']['plan_id'] ?? null;
        if ($backupPlanId && Plan::whereKey($backupPlanId)->exists()) {
            $planId = $backupPlanId;
        }

        $current->update([
            'name' => $data['tenant']['name'] ?? $current->name,
            'email' => $data['tenant']['email'] ?? $current->email,
            'phone' => $data['tenant']['phone'] ?? $current->phone,
            'logo' => $data['tenant']['logo'] ?? $current->logo,
            'status' => $data['tenant']['status'] ?? $current->status,
            'settings' => $data['tenant']['settings'] ?? $current->settings,
            'trial_ends_at' => $data['tenant']['trial_ends_at'] ?? $current->trial_ends_at,
            'plan_id' => $planId,
        ]);

        $subscriptionIdMap = [];
        foreach ($data['subscriptions'] ?? [] as $row) {
            $subscriptionPlanId = $planId;
            if (! empty($row['plan_id']) && Plan::whereKey($row['plan_id'])->exists()) {
                $subscriptionPlanId = $row['plan_id'];
            }

            $subscription = Subscription::create([
                'tenant_id' => $current->id,
                'plan_id' => $subscriptionPlanId,
                'payfast_subscription_id' => $row['payfast_subscription_id'] ?? null,
                'payfast_transaction_id' => $row['payfast_transaction_id'] ?? null,
                'status' => $row['status'] ?? 'pending',
                'gateway' => $row['gateway'] ?? 'manual',
                'gateway_subscription_id' => $row['gateway_subscription_id'] ?? null,
                'gateway_customer_id' => $row['gateway_customer_id'] ?? null,
                'amount' => $row['amount'] ?? 0,
                'currency' => $row['currency'] ?? 'PKR',
                'starts_at' => $row['starts_at'] ?? null,
                'ends_at' => $row['ends_at'] ?? null,
                'trial_ends_at' => $row['trial_ends_at'] ?? null,
                'cancelled_at' => $row['cancelled_at'] ?? null,
                'payfast_meta' => $row['payfast_meta'] ?? null,
            ]);

            if (isset($row['id'])) {
                $subscriptionIdMap[$row['id']] = $subscription->id;
            }
        }

        foreach ($data['subscription_payments'] ?? [] as $row) {
            $subscriptionId = $subscriptionIdMap[$row['subscription_id'] ?? null] ?? null;
            if (! $subscriptionId) {
                continue;
            }

            SubscriptionPayment::create([
                'subscription_id' => $subscriptionId,
                'tenant_id' => $current->id,
                'payfast_transaction_id' => $row['payfast_transaction_id'] ?? null,
                'gateway_transaction_id' => $row['gateway_transaction_id'] ?? null,
                'status' => $row['status'] ?? 'success',
                'amount' => $row['amount'] ?? 0,
                'currency' => $row['currency'] ?? 'PKR',
                'payment_method' => $row['payment_method'] ?? null,
                'payfast_response' => $row['payfast_response'] ?? null,
                'paid_at' => $row['paid_at'] ?? null,
            ]);
        }
    }

    protected function addTenantFiles(ZipArchive $zip): void
    {
        $source = storage_path('app/public/'.tenant_storage_path());
        $added = 0;

        if (is_dir($source)) {
            $sourceReal = rtrim(str_replace('\\', '/', (string) realpath($source)), '/');
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filePath = str_replace('\\', '/', $file->getRealPath());
                $relative = ltrim(substr($filePath, strlen($sourceReal)), '/');
                $zip->addFile($file->getRealPath(), 'storage/public/'.$relative);
                $added++;
            }
        }

        if ($added === 0) {
            $zip->addFromString('storage/public/.keep', '');
        }
    }

    protected function restoreFiles(string $sourcePath): void
    {
        $destination = storage_path('app/public/'.tenant_storage_path());

        if (is_dir($destination)) {
            $this->deleteDirectory($destination);
        }

        $this->copyDirectory($sourcePath, $destination);
    }

    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $sourceReal = rtrim(str_replace('\\', '/', (string) realpath($source)), '/');
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = str_replace('\\', '/', $file->getRealPath());
            $relative = ltrim(substr($filePath, strlen($sourceReal)), '/');
            $targetPath = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($file->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }

                continue;
            }

            $parent = dirname($targetPath);
            if (! is_dir($parent)) {
                mkdir($parent, 0755, true);
            }

            copy($file->getRealPath(), $targetPath);
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
