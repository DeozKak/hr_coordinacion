<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        try{
        if ($user->hasRole('admin') === false || $user->hasPermissionTo('gestion_usuarios') === false){
            return redirect('/home')->with('error', 'Acción no autorizada.');
        }
        }catch(\Exception $e){
            return redirect('/home')->with('error', 'Acción no autorizada.');
        }
        return $next($request);
    }
}
