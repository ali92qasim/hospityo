<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'email',
        'phone',
        'logo',
        'status',
        'settings',
        'trial_ends_at',
        'plan_id',
    ];

    protected function casts(): array
    {
        return [
            'settings'      => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    // ─── Module Access (SaaS Layer) ──────────────────

    /**
     * Check if this tenant's plan includes a module.
     * Returns true if no plan is assigned (trial/unlimited).
     */
    public function hasModule(string $module): bool
    {
        $plan = $this->plan;

        if (! $plan) {
            return true; // No plan = trial/unlimited access
        }

        return $plan->hasModule($module);
    }

    /**
     * Get all modules available to this tenant.
     */
    public function availableModules(): array
    {
        return $this->plan?->modules ?? ModuleRegistry::all();
    }

    /**
     * Get a plan limit value.
     */
    public function getLimit(string $key, mixed $default = null): mixed
    {
        return $this->plan?->getLimit($key, $default) ?? $default;
    }

    // ─── Helpers ─────────────────────────────────────

    public static function databaseNameFor(string $slug): string
    {
        $driver = config('database.connections.tenant.driver', 'sqlite');

        if ($driver === 'sqlite') {
            return database_path('tenants/tenant_' . $slug . '.sqlite');
        }

        // MySQL/PgSQL: just a database name
        return 'tenant_' . $slug;
    }

    public static function domainFor(string $slug): string
    {
        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST);

        return $slug . '.' . $baseDomain;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function trialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (!$this->trial_ends_at || $this->trial_ends_at->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->trial_ends_at);
    }
}
