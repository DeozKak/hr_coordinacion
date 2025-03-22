<?php

namespace App\Http\Controllers;

use App\Models\Produccion\tbl_produccion_zona;
use App\Models\Zonificacion\tbl_localidades_sede;
use App\Models\Zonificacion\TblGrupo;
use App\Models\Zonificacion\TblGruposDetalle;
use App\Models\Zonificacion\TblSubgrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Zonificacion\tbl_localidades_municipio;
use App\Models\Zonificacion\TblBarrios;
use Illuminate\Support\Facades\Validator;
use App\Services\BarrioService;
use App\Services\MunicipioService;
use Illuminate\Support\Facades\Log;

class ZonificacionController extends Controller
{

    protected BarrioService $barrioService;
    protected MunicipioService $municipioService;

    public function __construct(BarrioService $barrioService, MunicipioService $municipioService)
    {
        $this->barrioService = $barrioService;
        $this->municipioService = $municipioService;
    }

    public function index()
    {
        //consulta Municipios sin grupos o subgrupos asignados
        $mun_sin_grupo = $this->municipioService->VerificarGrupo();

        //consultas de todos los registros con  sus relaciones
        $municipios = tbl_localidades_municipio::all();
        $barrios = TblBarrios::with('municipios')->get();
        $grupos = TblGrupo::all();
        $subgrupos = TblSubgrupo::all();
        $sedes = tbl_localidades_sede::all();
        $zonas = tbl_produccion_zona::all();

        if (!empty($mun_sin_grupo))
        {
            session()->flash('warning', 'Existen municipios sin grupo o sub grupo relacionado. ');
            return view('zonas.index', compact('municipios', 'sedes', 'zonas', 'barrios', 'grupos', 'subgrupos', 'mun_sin_grupo'));
        }


        return view('zonas.index', compact('municipios', 'sedes', 'zonas', 'barrios', 'grupos', 'subgrupos'));
    }

// ------------------- CRUD TABLA tbl_localidades_municipios ----------------------------------
    public function storeMunicipio(Request $request)
    {
        // validar el nombre de la causal
        $sqlMunicipio = tbl_localidades_municipio::where('nombre', '=', $request->nombre)
            ->where('id_sede', '=', $request->sede)
            ->where('id_zona', '=', $request->zona)
            ->first();

        if ($sqlMunicipio) {
            return response()->json([
                'status' => 'exist',
                'message' => 'El municipio ya existe con la misma sede y zona.',
            ]);
        }

        $municipio = new tbl_localidades_municipio();
        $municipio->nombre = $request->nombre;
        $municipio->id_sede = $request->sede;
        $municipio->id_zona = $request->zona;
        $municipio->save();

        $sqlMun = tbl_localidades_municipio::with(['sede', 'zona'])
            ->where('id', $municipio->id)->first();

        return response()->json(['success' => $sqlMun]);
    }

    public function editMunicipio($id)
    {
        $municipio = tbl_localidades_municipio::find($id);
        return response()->json([$municipio]);
    }

    public function updateMunicipio(Request $request, $id)
    {
        $sqlMunicipio = tbl_localidades_municipio::where('nombre', '=', $request->nombre)
            ->where('id_sede', '=', $request->sede)
            ->where('id_zona', '=', $request->zona)
            ->first();

        if ($sqlMunicipio) {
            return response()->json([
                'status' => 'exist',
                'message' => 'El municipio ya existe con la misma sede y zona.',
            ]);
        }

        if ($sqlMunicipio != null && $sqlMunicipio['id'] != $id) {
            return response()->json([
                'status' => 'exist',
                'message' => 'El municipio ya existe.',
            ]);
        }

        $municipio = tbl_localidades_municipio::find($id);
        $municipio->nombre = $request->nombre;
        $municipio->id_sede = $request->sede;
        $municipio->id_zona = $request->zona;
        $municipio->save();

        $sqlMun = tbl_localidades_municipio::with(['sede', 'zona'])
            ->where('id', $municipio->id)->first();

        return response()->json(['success' => $sqlMun]);
    }

    public function changeStatusMunicipio(Request $request)
    {
        $id = $request->input('id');
        $municipio = tbl_localidades_municipio::find($id);

        if ($municipio->status == 1) {
            $municipio->status = 0;
        } else {
            $municipio->status = 1;
        }

        $municipio->save();
        return response()->json(['success' => $municipio]);
    }

    //-----------------------------------------------------------------------------------------


    //------------------------- CRUD TABLA BARRIOS -----------------------------------------------

    public function storeBarrio(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barrio' => 'required|string|max:255',
            'municipio' => 'required|int'
        ], [
            'barrio.required' => 'Por favor ingrese el nombre del barrio.',
            'barrio.string' => 'El nombre del barrio debe ser una cadena de texto.',
            'municipio.required' => 'Por favor ingrese el nombre del municipio.',
            'municipio.int' => 'El nombre del municipio debe ser un entero.',
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            //preparar transacción de datos
            DB::beginTransaction();
            //guardar datos en la tabla barrio
            $barrio = new TblBarrios();
            $barrio->barrio = $request->barrio;
            $barrio->save();

            $duplicado = $this->barrioService->duplicado($request->municipio,$request->barrio);

            if ($duplicado) {
                DB::rollBack();
                return response()->json(['error' => 'El barrio ya existe en el municipio seleccionado.'], 422);
            }

            //guardar relaciones en a tabla detalles
            $detalle = new TblGruposDetalle();
            $detalle->id_barrio = $barrio->id;
            $detalle->id_mun = $request->municipio;
            $detalle->save();

            //confirmar transacción
            DB::commit();

            $barrio->load('municipios');

            return response()->json([
                'ok' => $barrio,
                'success' => 'Guardado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            //devuelve cambios hechos
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()],500);
        }
    }

    public function editBarrio($id): \Illuminate\Http\JsonResponse
    {
        try {
            $barrio = TblBarrios::with('municipios')->find($id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json([$barrio]);
    }

    public function updateBarrio(Request $request, $id): \Illuminate\Http\JsonResponse
    {
       // dd($request->all());
        //valida campos con los tipo de dato correcto
        $validator = Validator::make($request->all(), [
            'barrio' => 'required|string|max:255',
            'municipio' => 'required|int'
        ], [
            'barrio.required' => 'Por favor ingrese el nombre del barrio.',
            'barrio.string' => 'El nombre del barrio debe ser una cadena de texto.',
            'municipio.required' => 'Por favor ingrese el nombre del municipio.',
            'municipio.int' => 'El nombre del municipio debe ser un entero.'
        ]);
        //devuelve en caso de que no se cumpla la validacion
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            //comienza transacción
            DB::beginTransaction();
            $barrio = TblBarrios::find($id);
            $barrio->barrio = $request->barrio;
            $barrio->save();

            //verifica duplicados devuelve un bool
            $duplicado = $this->barrioService->duplicado($request->municipio,null,$id);

            if ($duplicado) {
                DB::rollBack();
                return response()->json(['error' => 'El barrio ya existe en el municipio seleccionado.'], 422);
            }
            $detalle = TblGruposDetalle::where('id_barrio', $id)->first();
            $detalle->id_mun = $request->municipio;
            $detalle->save();
            //confirma
            DB::commit();
            $barrio->load('municipios');

            return response()->json([
                'ok' => $barrio,
                'success' => 'Registro actualizado exitosamente'
            ],200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /* public function changeStatusMunicipio(Request $request)
     {
         $id = $request->input('id');
         $municipio = tbl_localidades_municipio::find($id);

         if ($municipio->status == 1) {
             $municipio->status = 0;
         } else {
             $municipio->status = 1;
         }

         $municipio->save();
         return response()->json(['success' => $municipio]);
     }*/

    //------------------------------------------------------------------------------------------

    //-------------------------- CRUD TABLA GRUPOS ---------------------------------------------
    public function storeGrupo(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grupo' => 'required|string|max:255',
            'id_sede' => 'required|int|max:20'
        ], [
            'grupo.required' => 'Por favor ingrese el nombre del grupo.',
            'grupo.string' => 'El nombre del grupo debe ser una cadena de texto.',
            'id_sede.required' => 'Por favor Seleccione la sede.',
            'id_sede.int' => 'la sede debe ser un numero entero.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $exist = TblGrupo::where('grupo',$request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if($exist){
                return response()->json(['error' => 'El grupo ya existe en la sede seleccionada.'], 422);
            }
            //preparar transacción de datos
            DB::beginTransaction();
            //guardar datos en la tabla barrio
            $grupo = new TblGrupo();
            $grupo->grupo = $request->grupo;
            $grupo->id_sede = $request->id_sede;
            $grupo->save();
            //confirmar transacción
            DB::commit();
        } catch (\Exception $e) {
            //devuelve cambios hechos
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(),],500);
        }

        return response()->json([
            'ok' => $grupo,
            'success' => 'Guardado exitosamente'
        ], 201);
    }

    public function editGrupo($id): \Illuminate\Http\JsonResponse
    {
        try {
            $grupo = TblGrupo::find($id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json([$grupo]);
    }

    public function updateGrupo(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        //valida campos con los tipo de dato correcto
        $validator = Validator::make($request->all(), [
            'grupo' => 'required|string|max:255',
            'municipio' => 'required|int|max:20'
        ], [
            'grupo.required' => 'Por favor ingrese el nombre del grupo.',
            'grupo.string' => 'El nombre del grupo debe ser una cadena de texto.',
            'id_sede.required' => 'Por favor Seleccione la sede.',
            'id_sede.int' => 'la sede debe ser un numero entero.',
        ]);
        //devuelve en caso de que no se cumpla la validacion
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $exist = TblGrupo::where('grupo',$request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if($exist){
                return response()->json(['error' => 'El grupo ya existe en la sede seleccionada.'], 422);
            }
            //comienza transacción
            DB::beginTransaction();
            $grupo = TblGrupo::find($id);
            $grupo->grupo = $request->grupo;
            $grupo->id_sede = $request->id_sede;
            $grupo->save();

            //confirma
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }


        return response()->json([
            'ok' => $grupo,
            'success' => 'Actualizado exitosamente'
        ],200);
    }
    //------------------------------------------------------------------------------------------

    //-------------------------- CRUD TABLA SubGrupos ---------------------------------------------
    public function storeSubGrupo(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subgrupo' => 'required|string|max:255',
            'id_sede_sub' => 'required|int|max:20'
        ], [
            'subgrupo.required' => 'Por favor ingrese el nombre del subgrupo.',
            'subgrupo.string' => 'El nombre del subgrupo debe ser una cadena de texto.',
            'id_sede_sub.required' => 'Por favor Seleccione la sede.',
            'id_sede_sub.int' => 'la sede debe ser un numero entero.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $exist = TblSubgrupo::where('subgrupo',$request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if($exist){
                return response()->json(['error' => 'El Subgrupo ya existe en la sede seleccionada.'], 422);
            }
            //preparar transacción de datos
            DB::beginTransaction();
            //guardar datos en la tabla barrio
            $sub_grupo = new TblSubgrupo();
            $sub_grupo->subgrupo = $request->subgrupo;
            $sub_grupo->id_sede = $request->id_sede;
            $sub_grupo->save();
            //confirmar transacción
            DB::commit();
        } catch (\Exception $e) {
            //devuelve cambios hechos
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => $sub_grupo,
            'success' => 'Guardado exitosamente'
        ], 201);
    }

    public function editSubGrupo($id): \Illuminate\Http\JsonResponse
    {
        try {
            $sub_grupo = TblSubgrupo::find($id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json([$sub_grupo]);
    }

    public function updateSubGrupo(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        //valida campos con los tipo de dato correcto
        $validator = Validator::make($request->all(), [
            'subgrupo' => 'required|string|max:255',
            'municipio' => 'required|int|max:20'
        ], [
            'subgrupo.required' => 'Por favor ingrese el nombre del subgrupo.',
            'subgrupo.string' => 'El nombre del grupo debe ser una cadena de texto.',
            'id_sede.required' => 'Por favor Seleccione la sede.',
            'id_sede.int' => 'la sede debe ser un numero entero.',
        ]);
        //devuelve en caso de que no se cumpla la validacion
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $exist = TblSubgrupo::where('grupo',$request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if($exist){
                return response()->json(['error' => 'El grupo ya existe en la sede seleccionada.'], 422);
            }
            //comienza transacción
            DB::beginTransaction();
            $sub_grupo = TblSubgrupo::find($id);
            $sub_grupo->subgrupo = $request->subgrupo;
            $sub_grupo->id_sede = $request->id_sede;
            $sub_grupo->save();

            //confirma
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }


        return response()->json([
            'ok' => $sub_grupo,
            'success' => 'Actualizado exitosamente'
        ],200);
    }
    //------------------------------------------------------------------------------------------

}
