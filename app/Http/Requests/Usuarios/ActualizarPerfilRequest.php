<?php

namespace App\Http\Requests\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición del perfil propio.
 *
 * La ruta es `PUT /profile/{user}` y sólo lleva el middleware `auth`, así que
 * el identificador de la URL no lo respaldaba nadie: cualquiera con sesión
 * podía apuntar al perfil de otra persona y cambiarle el correo. Como el login
 * resuelve por correo, eso no era cosmético.
 *
 * `showProfile()` siempre entrega `auth()->user()`, de modo que esta pantalla
 * nunca edita a un tercero: se exige exactamente eso. La gestión de otras
 * cuentas va por `admin/users`, que tiene su propio control de rol.
 */
class ActualizarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        $perfil = $this->route('user');

        return $perfil instanceof User && $this->user()?->is($perfil);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                /* Único, pero sin chocar consigo mismo al guardar sin cambiarlo. */
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ese correo ya pertenece a otra cuenta.',
        ];
    }
}
