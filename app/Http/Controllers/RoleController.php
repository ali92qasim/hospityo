<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->paginate(15);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        // Load all permissions ordered by name so the form is easy to scan
        $permissions = Permission::orderBy('name')->get();
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => 'web',
        ]);

        $this->syncPermissionsSafely($role, $request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update(['name' => $request->name]);

        $this->syncPermissionsSafely($role, $request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    // -------------------------------------------------------------------------

    /**
     * Sync permissions to a role safely.
     *
     * The form submits permission names as strings. Spatie's syncPermissions()
     * looks up each name with guard_name = 'web'. If any permission has the
     * wrong guard_name in the DB (legacy data), findByName() throws
     * PermissionDoesNotExist and the entire sync fails silently.
     *
     * This method resolves permissions by ID instead of name to avoid that
     * problem entirely, then clears the Spatie cache.
     */
    private function syncPermissionsSafely(Role $role, array $permissionNames): void
    {
        try {
            // Resolve permission IDs from names — only picks up permissions
            // that actually exist in the DB with guard_name = 'web'
            $permissionIds = Permission::where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->pluck('id');

            // syncPermissions() with IDs bypasses the findByName() guard_name check
            $role->syncPermissions($permissionIds->toArray());

        } catch (\Throwable $e) {
            Log::error('[RoleController] syncPermissions failed', [
                'role'  => $role->name,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Always clear cache — even on failure, stale cache is worse
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
