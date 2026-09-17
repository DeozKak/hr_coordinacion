<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Intentos fallidos antes de frenar, y minutos que dura el freno.
     *
     * Los lee ThrottlesLogins, que viene con AuthenticatesUsers. El contador va
     * por correo + IP y vive en la caché (base de datos), así que un bloqueo
     * sólo afecta a quien lo provoca desde su conexión: el dueño de la cuenta
     * sigue entrando desde la suya.
     */
    protected $maxAttempts = 5;

    protected $decayMinutes = 15;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Inicio de sesión con freno de intentos.
     *
     * Este método sustituye al de AuthenticatesUsers y, al hacerlo, se había
     * quedado sin su limitador: nada frenaba probar contraseñas salvo el
     * `throttle:60,1` por IP de las rutas. En su lugar había un bloqueo
     * permanente —a los 4 fallos la cuenta pasaba a `state = 0`— que contaba
     * los fallos de cualquiera: bastaba conocer el correo de alguien para
     * dejarlo sin acceso hasta que un administrador lo reactivara.
     *
     * Ahora se usa el limitador de Laravel, temporal y por correo + IP, y el
     * estado de la cuenta sólo lo cambia un administrador.
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        /* El segundo argumento es la casilla «Recordarme». Hasta ahora no se
           pasaba y la casilla no hacía nada. La sesión recordada dura 300 días
           (config/auth.php) y Laravel la corta sola al cerrar sesión o al
           cambiar la contraseña: la cookie lleva un HMAC del hash de la
           contraseña y deja de valer en cuanto éste cambia. Si la cuenta se
           desactiva, la corta CheckUserStatus en la siguiente petición. */
        if (Auth::attempt($this->credentials($request), $request->boolean('remember'))) {
            if (Auth::user()->state == 1) {
                return $this->loginResponse($request);
            }

            /* Contraseña correcta, cuenta inactiva: no es un intento fallido y
               no suma al contador. */
            Auth::logout();

            return redirect()->back()->with('error', 'Tu cuenta está inactiva. Por favor contacta al administrador.');
        }

        $this->incrementLoginAttempts($request);

        return $this->failedLogin($request);
    }

    public function loginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended($this->redirectPath());
    }

    /* El mismo mensaje exista o no el correo: distinguirlos le diría a quien
       prueba qué cuentas existen. */
    public function failedLogin(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([$this->username() => 'Usuario o contraseña incorrectos.']);
    }
}
