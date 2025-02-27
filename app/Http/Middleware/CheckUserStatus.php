<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (  Auth::check() && Auth::user()->state == 0) {
            //middleware encargado de chekear si el usuario esta activo
            Auth::logout();
            return redirect('/login')->with('error', 'Tu cuenta se encuentra inactiva. Por favor contacta al administrador.');
        }
        return $next($request);
    }
}
