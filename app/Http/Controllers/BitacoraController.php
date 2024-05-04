<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tbl_insp_cali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BitacoraController extends Controller
{
    public function ver()
    {
        $supervisores = Auth::user();
        if ($supervisores->hasRole('Supervisor')) {
            return view('bitacoras.generar', compact('supervisores'));
        }
        $supervisores = User::role('Supervisor')->get();


        return view('bitacoras.generar', compact('supervisores'));
    }

    public function generar_bitacora(Request $request)
    {
        $supervisor = User::find($request->supervisor);

        $nombreArchivo = $request->archivo->getClientOriginalName() . $supervisor->name . ".xls";

        $request->archivo->storeAs('uploads', $nombreArchivo);

        $rutaDestino = storage_path('app/uploads/') . $nombreArchivo;
        
        $excelFilePath = $rutaDestino;

       return $this->procesarArchivoExcel($supervisor->name, $supervisor->id, $excelFilePath);
    }

    public function procesarArchivoExcel($nom_super, $id_super, $excelFilePath)
    {

        session(['nom_archivo' => basename($excelFilePath)]);
        session(['super' => $nom_super]);

        $inspectores = Tbl_insp_cali::where('SUPERVISOR', $id_super)
            ->where('state', 1)
            ->get();

        $nombres = array();
        $ids = array();

        foreach ($inspectores as $inspector) {
            $nombres[] = $inspector->apellidos . ' ' . $inspector->nombres;
            $ids[$inspector->cedula] = $inspector->id;
        }
        
        session(['ids_inspectores' => $ids]);

        $spreadsheet = IOFactory::load($excelFilePath);

        if (!$spreadsheet->sheetNameExists('4.08 Bitacora Valle')) {
            throw new \Exception();
        }

        unlink($excelFilePath);
        
        return view('bitacoras.tabla', compact('nombres', 'spreadsheet'));
    }
}
