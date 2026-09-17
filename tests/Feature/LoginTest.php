<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Inicio de sesión: freno de intentos y bloqueo de cuentas.
 *
 * El login propio sustituye al de AuthenticatesUsers y durante un tiempo se
 * quedó sin su limitador, con un bloqueo permanente en su lugar: a los cuatro
 * fallos la cuenta pasaba a inactiva, los provocara quien los provocara. Con
 * conocer el correo de alguien bastaba para dejarlo fuera. Estas pruebas fijan
 * el comportamiento que lo sustituye: freno temporal por correo + IP y la
 * cuenta intacta.
 */
class LoginTest extends TestCase
{
    use DatabaseTransactions;

    private const CLAVE = 'Clave-Correcta-2026';

    private function usuario(int $estado = 1): User
    {
        return User::create([
            'name' => 'Prueba login',
            'email' => 'login.'.uniqid().'@eyc.com.co',
            'password' => Hash::make(self::CLAVE),
            'type_id' => 'CC',
            'identification' => (string) random_int(100000, 999999),
            'state' => $estado,
        ]);
    }

    private function intentar(User $usuario, string $clave, string $ip = '10.0.0.1', bool $recordar = false)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from(route('login'))
            ->post(route('login'), array_filter([
                'email' => $usuario->email,
                'password' => $clave,
                'remember' => $recordar ? 'on' : null,
            ]));
    }

    /** Nombre de la cookie de «Recordarme» del guard web. */
    private function cookieDeRecuerdo(): string
    {
        return Auth::guard('web')->getRecallerName();
    }

    /**
     * Otra visita sin sesión, con sólo la cookie de recuerdo: como volver al
     * día siguiente con el navegador cerrado.
     */
    private function volverConLaCookie(string $valor)
    {
        $this->flushSession();
        Auth::forgetGuards();

        return $this->withCookie($this->cookieDeRecuerdo(), $valor)->get('/home');
    }

    private function fallar(User $usuario, int $veces, string $ip = '10.0.0.1'): void
    {
        for ($i = 0; $i < $veces; $i++) {
            $this->intentar($usuario, 'incorrecta', $ip);
        }
    }

    public function test_tras_cinco_fallos_se_frena_aunque_la_clave_sea_correcta(): void
    {
        $usuario = $this->usuario();
        $this->fallar($usuario, 5);

        /* El freno se comprueba antes de mirar la contraseña: si no, adivinar
           la correcta en el sexto intento lo saltaría. */
        $this->intentar($usuario, self::CLAVE)->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertStringContainsString(
            'Demasiados intentos',
            session('errors')->first('email')
        );
    }

    public function test_los_fallos_no_desactivan_la_cuenta(): void
    {
        $usuario = $this->usuario();
        $this->fallar($usuario, 12);

        $this->assertSame(1, (int) $usuario->fresh()->state, 'nadie puede desactivar una cuenta ajena a base de fallos');
    }

    public function test_el_dueno_entra_desde_su_ip_aunque_otro_haya_provocado_el_freno(): void
    {
        $usuario = $this->usuario();
        $this->fallar($usuario, 6, '203.0.113.50');   // quien prueba contraseñas

        $this->intentar($usuario, self::CLAVE, '198.51.100.7')->assertRedirect('/home');
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_acceso_correcto_reinicia_el_contador(): void
    {
        $usuario = $this->usuario();
        $this->fallar($usuario, 4);

        $this->intentar($usuario, self::CLAVE)->assertRedirect('/home');
        $this->post(route('logout'));

        /* Tras entrar, los cuatro fallos de antes ya no cuentan: hacen falta
           otros cinco para volver a frenar. */
        $this->fallar($usuario, 4);
        $this->intentar($usuario, self::CLAVE)->assertRedirect('/home');
    }

    public function test_cuenta_inactiva_con_clave_correcta_no_entra_ni_suma_intentos(): void
    {
        $usuario = $this->usuario(estado: 0);

        for ($i = 0; $i < 6; $i++) {
            $this->intentar($usuario, self::CLAVE)->assertSessionHas('error');
        }
        $this->assertGuest();

        /* Seis intentos con la clave correcta no la frenaron: al reactivarla,
           entra a la primera. */
        $usuario->update(['state' => 1]);
        $this->intentar($usuario, self::CLAVE)->assertRedirect('/home');
    }

    public function test_correo_inexistente_y_clave_incorrecta_dan_el_mismo_mensaje(): void
    {
        $existente = $this->usuario();
        $this->intentar($existente, 'incorrecta');
        $conCuenta = session('errors')->first('email');

        $inexistente = new User(['email' => 'nadie.'.uniqid().'@eyc.com.co']);
        $this->intentar($inexistente, 'incorrecta');

        $this->assertSame($conCuenta, session('errors')->first('email'), 'no se revela qué correos existen');
    }

    public function test_sin_marcar_recordarme_no_se_deja_cookie_de_recuerdo(): void
    {
        $this->intentar($this->usuario(), self::CLAVE)->assertCookieMissing($this->cookieDeRecuerdo());
    }

    public function test_recordarme_mantiene_la_sesion_sin_volver_a_entrar(): void
    {
        $usuario = $this->usuario();
        $respuesta = $this->intentar($usuario, self::CLAVE, recordar: true);

        $respuesta->assertCookie($this->cookieDeRecuerdo());
        $this->assertNotNull($usuario->fresh()->remember_token);

        /* 300 días, fijados en config/auth.php. Se deja un margen de un minuto
           por el tiempo que tarda la propia petición. */
        $caduca = $respuesta->getCookie($this->cookieDeRecuerdo())->getExpiresTime();
        $this->assertEqualsWithDelta(now()->addDays(300)->getTimestamp(), $caduca, 60, 'la sesión recordada dura 300 días');

        $this->volverConLaCookie($respuesta->getCookie($this->cookieDeRecuerdo())->getValue())->assertOk();
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_cambiar_la_contrasena_invalida_las_sesiones_recordadas(): void
    {
        /* El caso que importa: se cambia la contraseña porque alguien más la
           conoce. La cookie que ese alguien ya tuviera no debe seguir valiendo.

           Hoy lo garantiza Laravel —la cookie lleva un HMAC del hash de la
           contraseña—, no código del proyecto. La prueba está para que un
           cambio de guard, de proveedor o de versión no lo rompa en silencio. */
        $usuario = $this->usuario();
        $cookie = $this->intentar($usuario, self::CLAVE, recordar: true)
            ->getCookie($this->cookieDeRecuerdo())->getValue();

        $this->actingAs($usuario)->put(route('updatePassword', $usuario), [
            'current_password' => self::CLAVE,
            'new_password' => 'Clave-Nueva-2026',
            'conf_password' => 'Clave-Nueva-2026',
        ])->assertSessionHas('success');

        $this->volverConLaCookie($cookie)->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
