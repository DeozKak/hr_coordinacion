<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PQRSImportController extends Controller
{
    public function index(){

        return view('pqrs.index');

    }
}
