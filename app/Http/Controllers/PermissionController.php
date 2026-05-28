<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->paginate(20);
        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tenant.permissions,name',
        ]);

        Permission::create([
            'name'       => $request->name,
            'guard_name' => 'web',
        ]);

        // Clear Spatie's permission cache so the new permission is immediately visible
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tenant.permissions,name,' . $permission->id,
        ]);

        $permission->update([
            'name'       => $request->name,
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
