<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $this->credentials($request);

        if (Auth::attempt($credentials)) {
            if (Auth::user()->state == 1) {
                return $this->loginResponse($request);
            } else {
                Auth::logout();
                return redirect()->back()->with('error', 'Tu cuenta está inactiva. Por favor contacta al administrador.');
            }
        }

        return $this->failedLogin($request);
    }

    public function loginResponse(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();
        
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        $user->login_attempts = 0;
        $user->save();

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended($this->redirectPath());
    }

    

    public function failedLogin(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();
        
        if ($user) {
            $user->increment('login_attempts');
            
            if ($user->login_attempts > 3) {
                
                $user->state = 0;
                $user->save();
                return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors(['email' => trans('Usuario Bloqueado.')]);
            }
        }

        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors(['email' => trans('Usuario o contraseña incorrectos.')]);

    }

}

