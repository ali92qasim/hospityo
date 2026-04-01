<?php

use Spatie\Multitenancy\Jobs\TenantAware;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedClosure;
use Spatie\Multitenancy\Actions\ForgetCurrentTenantAction;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Actions\MakeTenantCurrentAction;
use Spatie\Multitenancy\Actions\MigrateTenantAction;

return [
    /*
     * Tenant finder: resolves the current tenant from the incoming request.
     * DomainTenantFinder matches request hostname against the `domain` column.
     */
    'tenant_finder' => \App\TenantFinder\SubdomainTenantFinder::class,

    /*
     * Fields used by tenant:artisan command to match tenants.
     */
    'tenant_artisan_search_fields' => [
        'id',
        'slug',
        'domain',
    ],

    /*
     * Tasks executed when switching tenants.
     * SwitchTenantDatabaseTask swaps the `tenant` DB connection to the tenant's database.
     * PrefixCacheTask isolates cache per tenant.
     */
    'switch_tenant_tasks' => [
        Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
        Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
    ],

    /*
     * Custom Tenant model — extends Spatie's base Tenant.
     */
    'tenant_model' => \App\Models\Tenant::class,

    /*
     * Queued jobs are tenant-aware by default.
     */
    'queues_are_tenant_aware_by_default' => true,

    /*
     * The DB connection name used for tenant databases.
     * Must match a key in config/database.php connections.
     */
    'tenant_database_connection_name' => 'tenant',

    /*
     * The DB connection name for the central/landlord database.
     */
    'landlord_database_connection_name' => 'landlord',

    'current_tenant_context_key' => 'tenantId',

    'current_tenant_container_key' => 'currentTenant',

    'shared_routes_cache' => false,

    'actions' => [
        'make_tenant_current_action' => MakeTenantCurrentAction::class,
        'forget_current_tenant_action' => ForgetCurrentTenantAction::class,
        'make_queue_tenant_aware_action' => MakeQueueTenantAwareAction::class,
        'migrate_tenant' => MigrateTenantAction::class,
    ],

    'queueable_to_job' => [
        SendQueuedMailable::class => 'mailable',
        SendQueuedNotifications::class => 'notification',
        CallQueuedClosure::class => 'closure',
        CallQueuedListener::class => 'class',
        BroadcastEvent::class => 'event',
    ],

    'tenant_aware_interface' => TenantAware::class,
    'not_tenant_aware_interface' => NotTenantAware::class,

    'tenant_aware_jobs' => [],
    'not_tenant_aware_jobs' => [],
];
