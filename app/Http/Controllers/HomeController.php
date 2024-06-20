<?php

namespace App\Http\Controllers;
use App\Models\tbl_dv_insp;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function dvClean(){

        tbl_dv_insp::where('activado', 1)
         ->where('gestionado', 1)
         ->update(['activado' => 0]);

         return response()->json(['success' => 'Data is successfully updated']);
    }
}
