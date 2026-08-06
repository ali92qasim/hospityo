<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function data()
    {
        $query = User::query();

        return DataTables::eloquent($query)->toJson();
    }
    public function index()
    {
        $users = User::with('roles')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now()
        ]);

        $user->syncRoles($request->roles ?? []);

        // Register email → tenant mapping for central login
        $tenant = Tenant::current();
        if ($tenant) {
            TenantUser::register($user->email, $tenant->id);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $oldEmail = $user->email;

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);

        $user->syncRoles($request->roles ?? []);

        // Keep landlord email → tenant mapping in sync for central login
        $tenant = Tenant::current();
        if ($tenant) {
            if ($user->wasChanged('email')) {
                TenantUser::unregister($oldEmail, $tenant->id);
            }
            TenantUser::register($user->email, $tenant->id);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $tenant = Tenant::current();
        if ($tenant) {
            TenantUser::unregister($user->email, $tenant->id);
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }
}
