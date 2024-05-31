<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        
         foreach ($permissions as $permission) {
        if ($request->user() && $request->user()->hasPermissionTo($permission)) {
            return $next($request);
        }
    }

        return redirect()->route('home')->with('error', 'Acción no autorizada.');
    }
}
