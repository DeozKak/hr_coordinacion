<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BitacoraController extends Controller
{
    public function generar()
    {
        $supervisores = Auth::user();
        if ($supervisores->hasRole('Supervisor')) {
            return view('bitacoras.generar',compact('supervisores'));
        }
        $supervisores = User::role('Supervisor')->get();
      

        return view('bitacoras.generar',compact('supervisores'));
    }
}
