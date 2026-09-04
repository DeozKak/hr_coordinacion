<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TmpRegistroTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Antes se usaba `$this->administrador()`, que ata la prueba a los datos de una base
     * concreta: en una instalación limpia devuelve null y actingAs revienta.
     */
    private function administrador(): User
    {
        $admin = User::create([
            'name' => 'Admin de prueba',
            'email' => 'admin.'.uniqid().'@eyc.com.co',
            'password' => Hash::make('secreto123'),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => 1,
        ]);

        $admin->assignRole('admin');
        $admin->givePermissionTo(Permission::where('name', 'gestion_usuarios')->firstOrFail());

        return $admin;
    }

    public function test_el_registro_ya_no_es_publico(): void
    {
        $this->get('/register')->assertRedirect(route('login'));
        $this->followingRedirects()->get('/register')->assertSee('ya caducó', false);

        // Tampoco por la puerta de atrás: un POST directo no crea nada.
        $antes = User::count();
        $this->post('/register', [
            'name' => 'Intruso', 'email' => 'intruso@x.co', 'type_id' => 'CC',
            'identification' => '999', 'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('login'));
        $this->assertEquals($antes, User::count(), 'no se creó ninguna cuenta');
    }

    public function test_con_el_enlace_firmado_si_se_puede(): void
    {
        $url = URL::temporarySignedRoute('register', now()->addDays(7));
        $correo = 'invitada+'.uniqid().'@eyc.com.co';
        $cedula = (string) random_int(900000000, 999999999);

        $this->get($url)->assertOk()->assertSee('Crear cuenta');

        $antes = User::count();
        $this->post($url, [
            'name' => 'Persona Invitada', 'email' => $correo, 'type_id' => 'CC',
            'identification' => $cedula, 'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $this->assertEquals($antes + 1, User::count());
        $nueva = User::where('email', $correo)->first();
        $this->assertNotNull($nueva);
        // Sigue naciendo inactiva: el administrador la activa después.
        $this->assertEquals(0, $nueva->state);
    }

    public function test_un_enlace_caducado_no_sirve(): void
    {
        $url = URL::temporarySignedRoute('register', now()->subMinute());
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_una_firma_manipulada_tampoco(): void
    {
        $url = URL::temporarySignedRoute('register', now()->addDays(7));
        $this->get($url.'x')->assertRedirect(route('login'));
    }

    public function test_solo_un_administrador_genera_el_enlace(): void
    {
        $this->post(route('admin.enlaceRegistro'))->assertRedirect(route('login'));

        $r = $this->actingAs($this->administrador())->postJson(route('admin.enlaceRegistro'));
        $r->assertOk();
        $datos = $r->json();
        $this->assertStringContainsString('/register?', $datos['url']);
        $this->assertStringContainsString('signature=', $datos['url']);
        $this->assertEquals(7, $datos['dias']);

        /* Y el enlace sirve de verdad, visto por quien lo recibe: sin sesión,
           porque el registro exige ser invitado (middleware `guest`). */
        auth()->logout();
        $this->get($datos['url'])->assertOk()->assertSee('Crear cuenta');
    }

    public function test_la_caducidad_se_mantiene_entre_1_y_30_dias(): void
    {
        foreach ([['dias' => 0, 'esperado' => 1], ['dias' => 999, 'esperado' => 30],
            ['dias' => 14, 'esperado' => 14]] as $caso) {
            $r = $this->actingAs($this->administrador())
                ->postJson(route('admin.enlaceRegistro'), ['dias' => $caso['dias']]);
            $this->assertEquals($caso['esperado'], $r->json('dias'));
        }
    }
}
