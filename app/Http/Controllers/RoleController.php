<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
        return view('admin.roles.create');
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
        $role->load('permissions');

        return view('admin.roles.edit', compact('role'));
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
     * Sync permissions to a role.
     *
     * The form submits permission names as strings. Resolve them to Permission
     * models (guard_name = web) and pass those models to Spatie so sync does
     * not depend on integer vs string ID handling. Posted names that are not
     * in the DB are rejected before sync so we never silently drop them or
     * wipe the role. An intentionally empty selection still clears all
     * permissions. Other failures are logged and rethrown as validation errors.
     */
    private function syncPermissionsSafely(Role $role, array $permissionNames): void
    {
        try {
            $permissionNames = array_values(array_unique($permissionNames));

            $permissions = Permission::where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->get();

            $missing = array_values(array_diff($permissionNames, $permissions->pluck('name')->all()));

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'permissions' => 'Unknown permissions: '.implode(', ', $missing)
                        .'. Run tenants:sync-permissions or add them to the seeder first.',
                ]);
            }

            $role->syncPermissions($permissions);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[RoleController] syncPermissions failed', [
                'role'  => $role->name,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'permissions' => 'Failed to assign permissions: '.$e->getMessage(),
            ]);
        } finally {
            // Always clear cache — even on failure, stale cache is worse
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
