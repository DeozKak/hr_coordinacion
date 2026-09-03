<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\URL;

class UserController extends Controller
{
    public function index(User $user)
    {
        $users = User::with('roles', 'permissions')->get();
        $userlogin = auth()->user();
        $roles = Role::all();
        $currentRole = $userlogin->roles->first();

        return view('users.index', compact('users', 'userlogin', 'roles', 'currentRole', 'user'));
    }

    public function update(Request $request, User $user)
    {

        $id = $request->input('id');
        $nombre = $request->input('nombres');
        $roles = $request->input('roles');
        $email = $request->input('email');
        $assignedPermissions = $request->input('assignedPermissions');
        $revokedPermissions = $request->input('revokedPermissions');
        $claveNueva = $request->input('claveNueva');
        $claveConfirmar = $request->input('claveConfirmar');

        if($nombre == "" || $email == "") {
            return response()->json([
                'status'=> 'warning',
                'message'=> 'Los campos son obligatorios'
            ]);
        }else{
            $user = User::find($id);

            if($claveNueva != null){
                $cambioClave = $this->updatePassword($request, $user, $claveNueva, $claveConfirmar);
                if(isset($cambioClave->original)){
                   return response()->json([
                        'status' => $cambioClave->original['status'],
                        'message' => $cambioClave->original['message'],
                    ]);
                }
            }else{
                $cambioClave = false;
            }

            if(!is_array($assignedPermissions) && !is_array( $revokedPermissions)){
                $permissionAssigned = json_decode($assignedPermissions, true);
                $permissionrevoked = json_decode($revokedPermissions, true);
                $flag = true;
            }else{
                $permissionAssigned = $assignedPermissions;
                $permissionrevoked = $revokedPermissions;
                $flag = false;
            }

            $user->syncRoles($roles);
            $user->syncPermissions($permissionAssigned);
            $user->revokePermissionTo($permissionrevoked);
            // Limpiar la caché de permisos
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $user->name = $nombre;
            $user->email = $email;
            $guardarUsuario = $user->save();

            if($flag){
                return redirect()->route('admin.index');
            }

            if($guardarUsuario && $cambioClave){
                return response()->json(data: [
                    'status'=> 'success',
                    'user'=> $user,
                    'message'=> 'Usuario y contraseña editado exitosamente'
                ]);
            }else if($guardarUsuario){
                return response()->json(data: [
                    'status'=> 'success',
                    'user'=> $user,
                    'message'=> 'Usuario editado exitosamente'
                ]);
            }else{
                return response()->json([
                    'status'=> 'error',
                    'message'=> 'Error al editar el usuario'
                ]);
            }
        }
    }

    public function changeStatus(User $user)
    {
        if ($user->state == 0){
            $user->state = 1;
            $user->save();

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'message' => 'El usuario se activó con éxito'
            ]);

        }else{
            $user->state = 0;
            $user->save();

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'message' => 'El usuario se desactivó con éxito'
            ]);
        }

    }

    public function changePassword(User $user)
    {
        return view('users.changePassword', compact('user'));
    }

    public function updatePassword(Request $request, User $user, $claveNueva = false, $claveConfirmar = false)
    {
        if($claveNueva != null){
            $newPassword = $claveNueva;
        }else{
            $newPassword = $request->new_password;
        }

        if($claveConfirmar != null){
            $confirmPassword = $claveConfirmar;
        }else{
            $confirmPassword = $request->conf_password;
        }

        $validator = Validator::make([
            'newPassword' => $newPassword,
        ], [
            'newPassword' => 'required|min:8',
        ]);


        if($newPassword != $confirmPassword){
            if($claveNueva != null){
                return response()->json([
                    'status'=> 'passwordDiff',
                    'message'=> 'Las contraseñas no coinciden'
                ]);
            }else{
                return redirect()->back()->with('error', 'Las contraseñas no coinciden')->withInput();
            }
        }

        if ($validator->fails()) {
            if($claveNueva != null){
                return response()->json([
                    'status'=> 'passowordLength',
                    'message'=> 'La contraseña debe ser de minimo 8 caracteres'
                ]);
            }else{
                return redirect()->back()->with('error', 'La contraseña debe ser de minimo 8 caracteres')->withInput();
            }
        }
        $user->password = bcrypt($newPassword);
        $user->save();
        if($claveNueva != null){
            return true;
        }else{
            $userlogin = auth()->user();
            if ($userlogin->hasRole('admin')){
                return redirect()->route('admin.index')->with('success', 'Contraseña actualizada correctamente');
            }else{
                return redirect()->route('home')->with('success', 'Contraseña actualizada correctamente');
            }
        }
    }

    public function showProfile()
    {
        $userlogin = auth()->user();
        $user = auth()->user();
        $currentRole = $user->roles->first();

        return view('users.show', compact('userlogin','user', 'currentRole'));
    }

    public function updateProfile(Request $request, User $user)
    {
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('home')->with('success', 'Perfil actualizado correctamente');
    }

    public function getDataPermissions(Request $request, ){
        $id = $request->input('id');

        $user = User::find($id);
        $permissions = Permission::all();
        $userPermissions = $user->permissions;
        $availablePermissions = $permissions->diff($userPermissions);

        return response()->json([
            'asignadas' => $userPermissions,
            'disponibles' => $availablePermissions
        ]);
    }

    /**
     * Genera el enlace con el que una persona puede registrarse.
     *
     * El registro dejó de ser público: la ruta exige una firma temporal, así que
     * este enlace es el único camino y caduca solo. La cuenta sigue creándose
     * inactiva (state = 0) y hace falta activarla desde esta misma pantalla.
     */
    public function enlaceRegistro(Request $request)
    {
        $dias = (int) $request->input('dias', 0);
        $dias = max(1, min($dias, 30));

        return response()->json([
            'url'     => URL::temporarySignedRoute('register', now()->addDays($dias)),
            'caduca'  => now()->addDays($dias)->format('d/m/Y'),
            'dias'    => $dias,
        ]);
    }
}
