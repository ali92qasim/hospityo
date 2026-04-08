<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Tenant driver resolution
|--------------------------------------------------------------------------
| DB_LANDLORD_DRIVER controls both landlord and tenant connections.
| Set to 'sqlite' for local dev, 'mysql' for production.
*/
$landlordDriver = env('DB_LANDLORD_DRIVER', 'sqlite');
$tenantDriver   = env('DB_TENANT_DRIVER', $landlordDriver);

return [

    'default' => env('DB_CONNECTION', 'landlord'),

    'connections' => [

        /*
        |----------------------------------------------------------------------
        | Landlord Connection (Central Database)
        |----------------------------------------------------------------------
        | Holds tenants, plans, super_admins, subscriptions.
        */
        'landlord' => match ($landlordDriver) {
            'mysql', 'mariadb' => [
                'driver'    => $landlordDriver,
                'host'      => env('DB_HOST', '127.0.0.1'),
                'port'      => env('DB_PORT', '3306'),
                'database'  => env('DB_DATABASE', 'landlord'),
                'username'  => env('DB_USERNAME', 'root'),
                'password'  => env('DB_PASSWORD', ''),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'prefix_indexes' => true,
                'strict'    => true,
                'engine'    => null,
                'options'   => extension_loaded('pdo_mysql') ? array_filter([
                    (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ],
            'pgsql' => [
                'driver'   => 'pgsql',
                'host'     => env('DB_HOST', '127.0.0.1'),
                'port'     => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'landlord'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset'  => 'utf8',
                'prefix'   => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode'  => env('DB_SSLMODE', 'prefer'),
            ],
            default => [ // sqlite
                'driver'   => 'sqlite',
                'database' => env('DB_DATABASE') ?: database_path('database.sqlite'),
                'prefix'   => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => null,
                'journal_mode' => null,
                'synchronous'  => null,
                'transaction_mode' => 'DEFERRED',
            ],
        },

        /*
        |----------------------------------------------------------------------
        | Tenant Connection (Per-Tenant Database)
        |----------------------------------------------------------------------
        | Spatie's SwitchTenantDatabaseTask sets the database at runtime.
        | For MySQL: switches the database name.
        | For SQLite: switches the file path.
        |
        | The 'database' field is a safe placeholder — never used directly.
        */
        'tenant' => match ($tenantDriver) {
            'mysql', 'mariadb' => [
                'driver'    => $tenantDriver,
                'host'      => env('DB_HOST', '127.0.0.1'),
                'port'      => env('DB_PORT', '3306'),
                'database'  => null, // Spatie sets this per-tenant
                'username'  => env('DB_USERNAME', 'root'),
                'password'  => env('DB_PASSWORD', ''),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'prefix_indexes' => true,
                'strict'    => true,
                'engine'    => null,
                'options'   => extension_loaded('pdo_mysql') ? array_filter([
                    (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ],
            'pgsql' => [
                'driver'   => 'pgsql',
                'host'     => env('DB_HOST', '127.0.0.1'),
                'port'     => env('DB_PORT', '5432'),
                'database' => null,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset'  => 'utf8',
                'prefix'   => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode'  => env('DB_SSLMODE', 'prefer'),
            ],
            default => [ // sqlite
                'driver'   => 'sqlite',
                'database' => database_path('.tenant_placeholder'), // safe non-null placeholder
                'prefix'   => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => null,
                'journal_mode' => null,
                'synchronous'  => null,
                'transaction_mode' => 'DEFERRED',
            ],
        },

        /*
        |----------------------------------------------------------------------
        | Standard Laravel connections (kept for compatibility)
        |----------------------------------------------------------------------
        */
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],

];
