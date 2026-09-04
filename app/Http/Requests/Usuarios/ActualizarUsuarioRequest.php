<?php

namespace App\Http\Requests\Usuarios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de una cuenta desde la pantalla de administración.
 *
 * Aquí el usuario a editar sí llega en el cuerpo (`id`) y es intencional: la
 * ruta está detrás de `CheckRole`, que exige rol admin y permiso
 * `gestion_usuarios`. Lo que faltaba era comprobar lo que entra —el correo, la
 * contraseña, los roles y los permisos iban directos a `syncRoles()` y a
 * `save()` sin una sola regla— y que `id` correspondiera a alguien: con un id
 * inexistente, `User::find()` devolvía null y el método reventaba.
 */
class ActualizarUsuarioRequest extends FormRequest
{
    /* La autorización la resuelve CheckRole en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:users,id'],
            'nombres' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->input('id')),
            ],

            /* Spatie acepta un nombre suelto o una lista; la pantalla manda uno. */
            'roles' => ['required'],
            'roles.*' => ['string', 'exists:roles,name'],

            /* Estos dos llegan como arreglo desde la pantalla nueva, pero el
               controlador todavía admite la cadena JSON de la vieja y decide
               con eso si responde JSON o redirige. Se validan sin convertirlos,
               para no alterar esa bifurcación. */
            'assignedPermissions' => ['nullable'],
            'assignedPermissions.*' => ['string', 'exists:permissions,name'],
            'revokedPermissions' => ['nullable'],
            'revokedPermissions.*' => ['string', 'exists:permissions,name'],

            /* Mismo mínimo que aplica updatePassword(), que es quien la guarda. */
            'claveNueva' => ['nullable', 'string', 'min:8'],
            'claveConfirmar' => ['nullable', 'string', 'same:claveNueva'],
        ];
    }

    /**
     * Un nombre de rol suelto no lo cubre `roles.*`, que sólo recorre arreglos.
     */
    public function withValidator($validator): void
    {
        $validator->sometimes('roles', ['string', 'exists:roles,name'], function ($datos) {
            return ! is_array($datos->roles);
        });
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Falta indicar qué usuario se edita.',
            'id.exists' => 'El usuario que se intenta editar no existe.',
            'nombres.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ese correo ya pertenece a otra cuenta.',
            'roles.required' => 'Hay que asignar un rol.',
            'roles.exists' => 'El rol indicado no existe.',
            'roles.*.exists' => 'Alguno de los roles indicados no existe.',
            'claveNueva.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'claveConfirmar.same' => 'La confirmación no coincide con la contraseña nueva.',
        ];
    }
}
