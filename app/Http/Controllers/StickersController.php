<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StickersController extends Controller
{
    //

    public function index(){
        return view('stickers.index');
    }
}
