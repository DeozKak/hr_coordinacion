<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Usuarios\ActualizarPerfilRequest;
use App\Http\Requests\Usuarios\ActualizarUsuarioRequest;
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

    public function update(ActualizarUsuarioRequest $request, User $user)
    {

        $id = $request->input('id');
        $nombre = $request->input('nombres');
        $roles = $request->input('roles');
        $email = $request->input('email');
        $assignedPermissions = $request->input('assignedPermissions');
        $revokedPermissions = $request->input('revokedPermissions');
        $claveNueva = $request->input('claveNueva');
        $claveConfirmar = $request->input('claveConfirmar');

        /* `id` viene validado con exists:users,id, así que find() no da null. */
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
        /* Este método tiene dos entradas. Desde update() llega con la clave ya
           en los argumentos, y esa vía está tras CheckRole: un administrador
           puede cambiar la de cualquiera. Por la ruta `uptadePassword/{user}`
           llega sin ellos, y esa ruta sólo lleva `auth`: el usuario sale de la
           URL y nada comprobaba de quién era, así que cualquiera con sesión
           podía apoderarse de otra cuenta, la de un administrador incluida.
           La pantalla sólo la usa para la cuenta propia (el enlace del navbar
           va con auth()->id()), así que se exige exactamente eso. */
        $vieneDeLaRuta = $claveNueva === false;

        if ($vieneDeLaRuta && ! $request->user()?->is($user)) {
            abort(403);
        }

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

    public function updateProfile(ActualizarPerfilRequest $request, User $user)
    {
        /* Sólo `name` y `email`: el resto del perfil no se edita desde aquí. */
        $user->fill($request->safe()->only(['name', 'email']));
        $user->save();

        return redirect()->route('home')->with('success', 'Perfil actualizado correctamente');
    }

    /**
     * Permisos de una cuenta, para la pantalla de administración.
     *
     * La ruta queda abierta a cualquiera con sesión, para que cada quien pueda
     * consultar los suyos. Pedir los de otra persona exige administrar
     * usuarios: el identificador viaja en el cuerpo, así que sin esa distinción
     * cualquiera podía enumerar los permisos de cualquier cuenta —los de un
     * administrador incluidos—, que es el reconocimiento previo a una escalada.
     *
     * Antes tampoco se comprobaba que el id existiera: `find()` devolvía null y
     * leer `$user->permissions` terminaba en un 500.
     */
    public function getDataPermissions(Request $request)
    {
        $usuario = $request->user();

        if ($usuario->can('gestion_usuarios')) {
            $usuario = User::find($request->input('id')) ?? $usuario;
        }

        $asignados = $usuario->permissions;

        return response()->json([
            'asignadas' => $asignados,
            'disponibles' => Permission::all()->diff($asignados),
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
        /* Una semana por omisión: el enlace se manda por fuera (correo, WhatsApp)
           y la persona no siempre lo abre el mismo día. El rango se limita
           igualmente, porque el valor puede venir del formulario. */
        $dias = (int) $request->input('dias', 7);
        $dias = max(1, min($dias, 30));

        return response()->json([
            'url'     => URL::temporarySignedRoute('register', now()->addDays($dias)),
            'caduca'  => now()->addDays($dias)->format('d/m/Y'),
            'dias'    => $dias,
        ]);
    }
}
