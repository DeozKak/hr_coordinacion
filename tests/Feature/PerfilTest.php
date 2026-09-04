<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * `PUT /profile/{user}` sólo lleva el middleware `auth`, así que durante un
 * tiempo cualquiera con sesión pudo apuntar al perfil de otra persona y
 * cambiarle el correo. Como el login resuelve por correo, eso permitía
 * quedarse con la identidad de otro. Estas pruebas fijan que no vuelva.
 */
class PerfilTest extends TestCase
{
    use DatabaseTransactions;

    private function crearUsuario(string $sufijo): User
    {
        return User::create([
            'name' => "Persona {$sufijo}",
            'email' => "persona.{$sufijo}.".uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);
    }

    public function test_nadie_puede_editar_el_perfil_de_otra_persona(): void
    {
        $propia = $this->crearUsuario('propia');
        $ajena = $this->crearUsuario('ajena');
        $correoOriginal = $ajena->email;

        $this->actingAs($propia)
            ->put(route('update', $ajena), [
                'name' => 'Nombre impuesto',
                'email' => 'secuestrado@eyc.com.co',
            ])
            ->assertForbidden();

        $this->assertSame(
            $correoOriginal,
            $ajena->fresh()->email,
            'el correo de la otra cuenta no debe cambiar'
        );
    }

    public function test_cada_quien_edita_su_propio_perfil(): void
    {
        $usuario = $this->crearUsuario('propia');
        $nuevo = 'nuevo.'.uniqid().'@eyc.com.co';

        $this->actingAs($usuario)
            ->put(route('update', $usuario), ['name' => 'Nombre nuevo', 'email' => $nuevo])
            ->assertRedirect(route('home'));

        $usuario->refresh();
        $this->assertSame('Nombre nuevo', $usuario->name);
        $this->assertSame($nuevo, $usuario->email);
    }

    public function test_el_correo_tiene_que_ser_valido(): void
    {
        $usuario = $this->crearUsuario('propia');
        $original = $usuario->email;

        $this->actingAs($usuario)
            ->put(route('update', $usuario), ['name' => 'Alguien', 'email' => 'esto-no-es-un-correo'])
            ->assertSessionHasErrors('email');

        $this->assertSame($original, $usuario->fresh()->email);
    }

    public function test_no_se_puede_robar_el_correo_de_otra_cuenta(): void
    {
        $usuario = $this->crearUsuario('propia');
        $otra = $this->crearUsuario('otra');

        $this->actingAs($usuario)
            ->put(route('update', $usuario), ['name' => 'Alguien', 'email' => $otra->email])
            ->assertSessionHasErrors('email');
    }

    public function test_el_nombre_es_obligatorio(): void
    {
        $usuario = $this->crearUsuario('propia');

        $this->actingAs($usuario)
            ->put(route('update', $usuario), ['name' => '', 'email' => $usuario->email])
            ->assertSessionHasErrors('name');
    }
}
