# Bug Report: Manually Created Permissions Do Not Work

## Summary

Permissions created through the admin UI (Super Admin → Permissions) are saved to the database but have no effect at runtime. Routes protected by `->middleware('permission:...')` deny access even when the permission is assigned to the user's role. Only permissions seeded via `RolePermissionSeeder` work correctly.

---

## Symptoms

- A new permission is created via the UI and assigned to a role.
- A user with that role is still denied access to the protected route.
- Permissions seeded by `RolePermissionSeeder` work fine for the same user.
- New modules added after initial seeding also fail — their permissions don't work until the seeder is re-run.

---

## Root Cause

### The `guard_name` mismatch

Spatie Permission stores a `guard_name` column on every permission and role record. When checking `$user->hasPermissionTo('edit patients')`, Spatie looks up the permission **filtered by guard_name**. If the guard_name on the stored record doesn't match the guard being used at runtime, the permission is not found and access is denied.

**The seeder creates permissions with `guard_name = 'web'`:**

```php
// RolePermissionSeeder.php
Permission::firstOrCreate(
    ['name' => $permission, 'guard_name' => 'web']
);
```

**The `PermissionController::store()` creates permissions WITHOUT specifying `guard_name`:**

```php
// PermissionController.php
Permission::create(['name' => $request->name]);
```

When `guard_name` is omitted, Spatie falls back to the default guard from `config('auth.defaults.guard')`. In this application that default is `'landlord'` (set in `config/database.php` as the default connection), **not** `'web'`.

So seeded permissions have `guard_name = 'web'`, but UI-created permissions have `guard_name = 'landlord'` (or whatever the auth default resolves to). Spatie's permission check only matches records where the guard_name equals the guard of the authenticated user — which is `'web'`. The UI-created permissions are invisible to the check.

### Why new modules also fail

When a developer adds a new module and its routes use `->middleware('permission:view new-module')`, that permission string is checked against the database. If the developer forgot to add it to `RolePermissionSeeder::PERMISSIONS` and re-run the seeder, the permission doesn't exist in the DB at all for existing tenants. Even if an admin creates it manually via the UI, the guard_name bug above means it still won't work.

---

## Evidence

| Source | `guard_name` stored |
|--------|-------------------|
| `RolePermissionSeeder` | `web` (explicit) |
| `PermissionController::store()` | `landlord` or auth default (implicit) |
| `RoleController::store()` via `syncPermissions()` | Matches whatever is in DB |

---

## Fix

### 1. Fix `PermissionController` — always set `guard_name = 'web'`

```php
// PermissionController.php — store()
Permission::create([
    'name'       => $request->name,
    'guard_name' => 'web',
]);

// PermissionController.php — update()
$permission->update([
    'name'       => $request->name,
    'guard_name' => 'web',
]);
```

### 2. Fix existing bad records — one-time migration

Any permissions already created via the UI have the wrong `guard_name`. Run this once per tenant to correct them:

```php
// In a migration or artisan command:
DB::connection('tenant')->table('permissions')
    ->where('guard_name', '!=', 'web')
    ->update(['guard_name' => 'web']);

DB::connection('tenant')->table('roles')
    ->where('guard_name', '!=', 'web')
    ->update(['guard_name' => 'web']);

// Clear Spatie's permission cache after fixing
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

### 3. Fix `RoleController` — guard_name on role creation

The `RoleController` already uses `firstOrCreate` with `guard_name` via `syncPermissions`, but the role itself should also be explicit:

```php
// RoleController.php — store()
$role = Role::create([
    'name'       => $request->name,
    'guard_name' => 'web',
]);
```

### 4. Add `guard_name` validation hint in the UI (optional but helpful)

In the permission create/edit form, either hide the guard_name field entirely (always force `web`) or show it as a read-only field set to `web`, so future developers don't accidentally create permissions with the wrong guard.

### 5. For new modules — add to `RolePermissionSeeder` and provide a command

Add a `SyncTenantPermissions` artisan command (check if it already exists in `app/Console/Commands/`) that re-runs `RolePermissionSeeder` across all tenants. Run it after deploying any new module.

---

## Prevention

- `PermissionController` must always hardcode `guard_name = 'web'`.
- Any new permission string used in a route middleware must be added to `RolePermissionSeeder::PERMISSIONS` before deployment.
- After deploying new permissions, run: `php artisan tenants:run db:seed --class=RolePermissionSeeder`

---

## Affected Files

| File | Issue |
|------|-------|
| `app/Http/Controllers/PermissionController.php` | Missing `guard_name` on `create()` and `update()` |
| `app/Http/Controllers/RoleController.php` | Missing `guard_name` on `Role::create()` |
| `database/seeders/RolePermissionSeeder.php` | Correct — sets `guard_name = 'web'` explicitly |
