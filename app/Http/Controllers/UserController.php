<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Faker\Provider\ar_EG\Person;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return view('users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $currentRole = $user->roles->first();
        $permissions = Permission::all();
        $userPermissions = $user->permissions;
        $availablePermissions = $permissions->diff($userPermissions);

        return view('users.edit', compact('user', 'roles', 'permissions', 'userPermissions', 'availablePermissions', 'currentRole'));
    }

    public function update(Request $request, User $user)
    {

        $user->syncRoles($request->roles);
        $permissionAssigned = json_decode($request->assignedPermissions, true);
        $permissionrevoked = json_decode($request->revokedPermissions, true);
        $user->syncPermissions($permissionAssigned);
        $user->revokePermissionTo($permissionrevoked);
        // Limpiar la caché de permisos
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('admin.index');
    }
}
