<?php

namespace App\Http\Controllers;
use App\Models\tbl_insp_cali;
use Illuminate\Http\Request;

class InspectorController extends Controller
{
    public function index()
    {
        $inspectores = tbl_insp_cali::all();
       
        return view('inspectores.index', compact('inspectores'));
    }
}
