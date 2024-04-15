<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
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
        $userlogin = auth()->user();
        $roles = Role::all();
        $currentRole = $user->roles->first();
        $permissions = Permission::all();
        $userPermissions = $user->permissions;
        $availablePermissions = $permissions->diff($userPermissions);

        return view('users.edit', compact('userlogin' ,'user', 'roles', 'permissions', 'userPermissions', 'availablePermissions', 'currentRole'));
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

    public function changeStatus(User $user)
    {
        if ($user->state == 0){
            $user->state = 1;
            $user->save();
        }else{
            $user->state = 0;
            $user->save();}

        return redirect()->route('admin.index');
    }

    public function changePassword(User $user)
    {
        return view('users.changePassword', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:8',
        ]);

        if($request->new_password != $request->conf_password){
            return redirect()->back()->with('error', 'Las contraseñas no coinciden')->withInput();
        }
        
        if ($validator->fails()) {
            return redirect()->back()->with('error', 'La contraseña debe ser de minimo 8 caracteres')->withInput();
        }
        $user->password = bcrypt($request->new_password);
        $user->save();
        $userlogin = auth()->user();
        if ($userlogin->hasRole('admin')){
        return redirect()->route('admin.index')->with('success', 'Contraseña actualizada correctamente');
        }else{
            return redirect()->route('home')->with('success', 'Contraseña actualizada correctamente');
        }
    }

    public function showProfile()
    {
        $userlogin = auth()->user();
        $user = auth()->user();
        $currentRole = $user->roles->first();
       
        return view('users.show', compact('userlogin','user', 'currentRole'));
    }

    public function editProfile(){
        $userlogin = auth()->user();
        $user = auth()->user();
        $roles = Role::all();
        $currentRole = $user->roles->first();
        $permissions = Permission::all();
        $userPermissions = $user->permissions;
        $availablePermissions = $permissions->diff($userPermissions);
        return view('users.edit', compact('userlogin','user', 'roles', 'permissions', 'userPermissions', 'availablePermissions', 'currentRole'));
    }

    public function updateProfile(Request $request, User $user)
    {
       
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('home')->with('success', 'Perfil actualizado correctamente');
    }
}
