<?php

namespace App\Services\SharedHosting;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CpanelMysqlProvisioner
{
    /**
     * Create a MySQL database via cPanel UAPI and grant ALL PRIVILEGES
     * to the landlord DB username. On failure after create, best-effort delete.
     */
    public function create(string $database): void
    {
        $created = false;

        try {
            $this->execute('Mysql/create_database', ['name' => $database]);
            $created = true;

            $this->execute('Mysql/set_privileges_on_database', [
                'user' => (string) config('database.connections.landlord.username'),
                'database' => $database,
                'privileges' => 'ALL PRIVILEGES',
            ]);
        } catch (\Throwable $e) {
            if ($created) {
                $this->bestEffortDelete($database);
            }

            throw $e;
        }
    }

    protected function bestEffortDelete(string $database): void
    {
        try {
            $this->execute('Mysql/delete_database', ['name' => $database]);
        } catch (\Throwable $cleanup) {
            Log::warning('[SharedHosting] Failed to clean up cPanel database after provisioning error', [
                'database' => $database,
                'error' => $cleanup->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $params
     */
    protected function execute(string $moduleFunction, array $params): void
    {
        $host = rtrim((string) config('shared_hosting.host'), '/');
        $user = (string) config('shared_hosting.user');
        $token = (string) config('shared_hosting.api_token');
        $timeout = (int) config('shared_hosting.timeout', 30);

        if ($host === '' || $user === '' || $token === '') {
            throw new RuntimeException('Shared hosting cPanel configuration is incomplete.');
        }

        $url = "{$host}/execute/{$moduleFunction}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "cpanel {$user}:{$token}",
            ])
                ->timeout($timeout)
                ->acceptJson()
                ->get($url, $params);
        } catch (RequestException $e) {
            Log::error('[SharedHosting] cPanel HTTP request failed', [
                'endpoint' => $moduleFunction,
                'database' => $params['database'] ?? $params['name'] ?? null,
                'status' => $e->response?->status(),
            ]);

            throw new RuntimeException("cPanel API request failed for {$moduleFunction}.", 0, $e);
        }

        $payload = $response->json() ?? [];
        $ok = $response->successful() && (int) ($payload['status'] ?? 0) === 1;

        if (! $ok) {
            Log::error('[SharedHosting] cPanel API returned an error', [
                'endpoint' => $moduleFunction,
                'database' => $params['database'] ?? $params['name'] ?? null,
                'http_status' => $response->status(),
                'errors' => $payload['errors'] ?? null,
            ]);

            throw new RuntimeException("cPanel API failed for {$moduleFunction}.");
        }
    }
}
