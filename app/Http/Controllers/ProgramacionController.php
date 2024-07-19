<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;


class ProgramacionController extends Controller
{
    
    public function index()
    {
        return view('programacion.index');
    }

}
