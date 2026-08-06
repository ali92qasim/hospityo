<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUser extends Model
{
    protected $connection = 'landlord';

    protected $fillable = ['email', 'tenant_id'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Find the tenant for a given email.
     * If user belongs to multiple tenants, returns the first active one.
     */
    public static function findTenantByEmail(string $email): ?Tenant
    {
        return static::where('email', $email)
            ->with('tenant')
            ->get()
            ->filter(fn($tu) => $tu->tenant && $tu->tenant->status === 'active')
            ->first()
            ?->tenant;
    }

    /**
     * Find all tenants for a given email.
     */
    public static function findTenantsByEmail(string $email): \Illuminate\Support\Collection
    {
        return static::where('email', $email)
            ->with('tenant')
            ->get()
            ->filter(fn($tu) => $tu->tenant && in_array($tu->tenant->status, ['active', 'provisioning']))
            ->pluck('tenant');
    }

    /**
     * Register a user-tenant mapping.
     */
    public static function register(string $email, int $tenantId): self
    {
        return static::firstOrCreate([
            'email' => strtolower($email),
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Update the email on a user-tenant mapping (e.g. after a tenant user email change).
     */
    public static function updateEmail(string $oldEmail, string $newEmail, int $tenantId): void
    {
        static::where('email', strtolower($oldEmail))
            ->where('tenant_id', $tenantId)
            ->delete();

        static::register($newEmail, $tenantId);
    }

    /**
     * Remove a user-tenant mapping (e.g. after a tenant user is deleted).
     */
    public static function unregister(string $email, int $tenantId): void
    {
        static::where('email', strtolower($email))
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
