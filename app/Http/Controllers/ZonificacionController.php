<?php

namespace App\Http\Controllers;

use App\Models\Produccion\tbl_produccion_zona;
use App\Models\tbl_insp_cali;
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
use App\Rules\UniqueMunicipio;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ZonificacionController extends Controller
{

    protected BarrioService $barrioService;
    protected MunicipioService $municipioService;

    public function __construct(BarrioService $barrioService, MunicipioService $municipioService)
    {
        $this->barrioService = $barrioService;
        $this->municipioService = $municipioService;
    }

    public function datosAsignador(): \Illuminate\Http\JsonResponse
    {
        $municipios_sin_grupo = $this->municipioService->MunicipiosSinGrupo();
        $municipios = tbl_localidades_municipio::all();
        $barrios_disponibles = TblBarrios::whereDoesntHave('detalle')->get();
        $barrios_asignados = TblBarrios::with('municipios')->get();
        $grupos = TblGrupo::all();
        $subgrupos = TblSubgrupo::all();
        $sedes = tbl_localidades_sede::all();
        $zonas = tbl_produccion_zona::all();

        return response()->json(
            [
                'municipios_sin_grupo' => $municipios_sin_grupo,
                'municipios' => $municipios,
                'barrios_d' => $barrios_disponibles,
                'barrios_a' => $barrios_asignados,
                'grupos' => $grupos,
                'subgrupos' => $subgrupos,
                'sedes' => $sedes,
                'zonas' => $zonas,
            ]);
    }

    public function asignar(Request $request): \Illuminate\Http\JsonResponse
    {
        //validación de datos
        $validator = Validator::make($request->all(), [
            'asignaciones' => 'required|array',
            'asignaciones.*.id' => 'required|integer',
            'asignaciones.*.municipio' => 'required|integer',
            'asignaciones.*.grupo' => 'required|integer',
            'asignaciones.*.subgrupo' => 'required|integer',
            'asignaciones.*.barrio' => 'nullable|integer',
        ],
            [
                'asignaciones.required' => 'Por favor, ingrese las asignaciones.',
                'asignaciones.array' => 'El dato ingresado debe ser un array.',
                'asignaciones.*.id.required' => 'El campo ID es requerido.',
                'asignaciones.*.id.integer' => 'El campo ID debe ser un número entero.',
                'asignaciones.*.municipio.required' => 'El campo Municipio es requerido.',
                'asignaciones.*.municipio.integer' => 'El campo Municipio debe ser un número entero.',
                'asignaciones.*.grupo.required' => 'El campo Grupo es requerido.',
                'asignaciones.*.grupo.integer' => 'El campo Grupo debe ser un número entero.',
                'asignaciones.*.subgrupo.required' => 'El campo Subgrupo es requerido.',
                'asignaciones.*.subgrupo.integer' => 'El campo Subgrupo debe ser un número entero.',
                'asignaciones.*.barrio.integer' => 'El campo Barrio debe ser un número entero.',
            ]);
        // genera mensaje si los requisitos no se cumplen
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
        //asigna a una variable los datos del formulario
        $asignaciones = $request->asignaciones;
        // $ids = array_column($asignaciones, 'id'); // Obtener un array solo con los IDs
        try {
            //comienza transacción Base de datos
            DB::beginTransaction();
            //guarda en otro array los valores para asignación masiva
            foreach ($asignaciones as $asignacion) {
                $updates[$asignacion['id']] = [
                    'id_mun' => $asignacion['municipio'],
                    'id_grupo' => $asignacion['grupo'],
                    'id_subGrupo' => $asignacion['subgrupo'],
                    'id_barrio' => empty($asignacion['barrio']) ? null : $asignacion['barrio'],
                ];
            }
            //asignación masiva
            foreach ($updates as $id => $data) {
                TblGruposDetalle::where('id', $id)
                    ->update($data);
            }
            DB::commit();
            return response()->json(['success' => 'Asignaciones realizadas exitosamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function buscador(Request $request): \Illuminate\Http\JsonResponse
    {
        $barrios_disponibles = TblBarrios::whereDoesntHave('detalle')->get();

        $validator = Validator::make($request->all(), [
            'municipio' => 'sometimes|nullable|integer',
            'barrio' => 'sometimes|nullable|integer',
            'grupo' => 'sometimes|nullable|integer',
            'subgrupo' => 'sometimes|nullable|integer',
        ], [
            'required' => 'Debe proporcionar al menos un valor para municipio, barrio, grupo o subgrupo.',
        ]);

        $validator->sometimes(['municipio', 'barrio', 'grupo', 'subgrupo'], 'required', function ($input) {
            return empty($input->municipio) && empty($input->barrio) && empty($input->grupo) && empty($input->subgrupo);
        });

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }


        $municipio = $request->input('municipio');
        $barrio = $request->input('barrio');
        $grupo = $request->input('grupo');
        $subgrupo = $request->input('subgrupo');
        try {
            $busqueda = TblgruposDetalle::with(['tbl_grupo', 'tbl_subgrupo',
                'tbl_barrios', 'tbl_localidades_municipio']);
            //$barrio = "PARQUE DE LA CAÑA";
            if ($municipio) {
                $busqueda->where('id_mun', $municipio); // Filtra por el nombre del municipio
            }

            if ($barrio) {
                $busqueda->where('id_barrio', $barrio);
            }
            //$grupo = "CO";
            if ($grupo) {
                $busqueda->where('id_grupo', $grupo);
            }
            //$subgrupo = "CE2";
            if ($subgrupo) {
                $busqueda->where('id_subGrupo', $subgrupo);
            }

            $resultados = $busqueda->get();
            return response()->json(['data' => $resultados, 'barrios' => $barrios_disponibles]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function index(): object
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

        if ($mun_sin_grupo) {
            session()->flash('warning', 'Existen municipios sin grupo o sub grupo relacionado. ');
        }

        return view('zonas.index', compact('municipios', 'sedes', 'zonas', 'barrios', 'grupos', 'subgrupos'));
    }

    // ------------------- CRUD TABLA tbl_localidades_municipios ----------------------------------
    public function storeMunicipio(Request $request): \Illuminate\Http\JsonResponse
    {
        //validador de formulario
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:100', new UniqueMunicipio($request->sede, $request->zona)],
            'sede' => 'required|integer',
            'zona' => 'required|integer',
        ],
            [
                'nombre.required' => 'Por favor ingrese el nombre del municipio.',
                'nombre.string' => 'El nombre del municipio debe ser una cadena de texto.',
                'sede.required' => 'Por favor Seleccione la sede.',
                'sede.integer' => 'la sede debe ser un numero entero.',
                'zona.required' => 'Por favor Seleccione la zona.',
                'zona.integer' => 'la zona debe ser un numero entero.',
            ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
        try {
            DB::beginTransaction();
            $municipio = new tbl_localidades_municipio();
            $municipio->nombre = $request->nombre;
            $municipio->id_sede = $request->sede;
            $municipio->id_zona = $request->zona;
            $municipio->save();

            $municipio->load('sede', 'zona');

            DB::commit();
            return response()->json([
                'ok' => $municipio,
                'success' => 'Municipio creado exitosamente.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function editMunicipio($id): \Illuminate\Http\JsonResponse
    {
        try {
            $municipio = tbl_localidades_municipio::find($id);
            return response()->json([$municipio]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function updateMunicipio(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:100', new UniqueMunicipio($request->sede, $request->zona)],
            'sede' => 'required|integer',
            'zona' => 'required|integer',
        ],
            [
                'nombre.required' => 'Por favor ingrese el nombre del municipio.',
                'nombre.string' => 'El nombre del municipio debe ser una cadena de texto.',
                'sede.required' => 'Por favor Seleccione la sede.',
                'sede.integer' => 'la sede debe ser un numero entero.',
                'zona.required' => 'Por favor Seleccione la zona.',
                'zona.integer' => 'la zona debe ser un numero entero.',
            ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();
            $municipio = tbl_localidades_municipio::find($id);
            $municipio->nombre = $request->nombre;
            $municipio->id_sede = $request->sede;
            $municipio->id_zona = $request->zona;
            $municipio->save();

            $municipio->load('sede', 'zona');
            DB::commit();
            return response()->json([
                'ok' => $municipio,
                'success' => 'Municipio actualizado exitosamente.',
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function changeStatusTable(Request $request): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'table' => 'required|string',
        ],
            [
                'id.required' => 'Por favor se requiere id del registro',
                'table.required' => 'Por favor se requiere tabla del registro',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $id = $request->input('id');
        $name_table = $request->input('table');
        try {
            DB::beginTransaction();

            $validTables = [
                'tbl_localidades_municipios',
                'tbl_grupos',
                'tbl_subgrupos', // Agrega aquí las tablas permitidas
            ];

            // Verifica si la tabla es válida
            if (!in_array($name_table, $validTables)) {
                return response()->json(['error' => 'La tabla especificada no es válida.'], 400);
            }

            $table = DB::table($name_table)->find($id);

            if (!$table) {
                return response()->json(['error' => 'Registro no encontrado.'], 404);
            }
            // Determina el nuevo valor del campo `status`
            if ($table->status === 1) {
                $newStatus = 0;
            } else {
                $newStatus = 1;
            }
            // Actualiza el campo `status` del registro
            DB::table($name_table)->where('id', $id)->update(['status' => $newStatus]);
            DB::commit();
            $registro = DB::table($name_table)->find($id);
            return response()->json(['success' => $registro]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    //-----------------------------------------------------------------------------------------


    //------------------------- CRUD TABLA BARRIOS -----------------------------------------------

    public function storeBarrio(Request $request): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'barrio' => 'required|string|max:255',
        ], [
            'barrio.required' => 'Por favor ingrese el nombre del barrio.',
            'barrio.string' => 'El nombre del barrio debe ser una cadena de texto.',
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
            return response()->json(['error' => $e->getMessage()], 500);
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
        //valida campos con los tipo de dato correcto
        $validator = Validator::make($request->all(), [
            'barrio' => 'required|string|max:255',
        ], [
            'barrio.required' => 'Por favor ingrese el nombre del barrio.',
            'barrio.string' => 'El nombre del barrio debe ser una cadena de texto.',
        ]);
        //devuelve en caso de que no se cumpla la validación
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            //comienza transacción
            DB::beginTransaction();
            $barrio = TblBarrios::find($id);
            $barrio->barrio = $request->barrio;
            $barrio->save();

            //confirma
            DB::commit();
            $barrio->load('municipios');

            return response()->json([
                'ok' => $barrio,
                'success' => 'Registro actualizado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }


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
            $exist = TblGrupo::where('grupo', $request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if ($exist) {
                return response()->json(['error' => 'El grupo ya existe en la sede seleccionada.'], 422);
            }
            //preparar transacción de datos
            DB::beginTransaction();
            //guardar datos en la tabla barrio
            $grupo = new TblGrupo();
            $grupo->grupo = $request->grupo;
            $grupo->id_sede = $request->id_sede;
            $grupo->save();

            //consulta nombre sede para insertar en la tabla
            $sede = tbl_localidades_sede::find($request->id_sede);
            //confirmar transacción
            DB::commit();
        } catch (\Exception $e) {
            //devuelve cambios hechos
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(),], 500);
        }

        return response()->json([
            'ok' => $grupo,
            'success' => 'Guardado exitosamente',
            'nom_sede' => $sede->nombre,
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
            'id_sede' => 'required|int|max:20'
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
            $exist = TblGrupo::where('grupo', $request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if ($exist) {
                return response()->json(['error' => 'El grupo ya existe en la sede seleccionada.'], 422);
            }
            //comienza transacción
            DB::beginTransaction();
            $grupo = TblGrupo::find($id);
            $grupo->grupo = $request->grupo;
            $grupo->id_sede = $request->id_sede;
            $grupo->save();

            //consulta nombre sede para insertar en la tabla
            $sede = tbl_localidades_sede::find($request->id_sede);

            //confirma
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }


        return response()->json([
            'ok' => $grupo,
            'success' => 'Actualizado exitosamente',
            'nom_sede' => $sede->nombre,
        ], 200);
    }
    //------------------------------------------------------------------------------------------

    //-------------------------- CRUD TABLA SubGrupos ---------------------------------------------
    public function storeSubGrupo(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subgrupo' => 'required|string|max:255',
            'id_sede' => 'required|int|max:20'
        ], [
            'subgrupo.required' => 'Por favor ingrese el nombre del subgrupo.',
            'subgrupo.string' => 'El nombre del subgrupo debe ser una cadena de texto.',
            'id_sede.required' => 'Por favor Seleccione la sede.',
            'id_sede.int' => 'la sede debe ser un numero entero.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $exist = TblSubgrupo::where('subgrupo', $request->grupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if ($exist) {
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

            //consulta nombre sede para insertar en la tabla
            $sede = tbl_localidades_sede::find($request->id_sede);
            DB::commit();
        } catch (\Exception $e) {
            //devuelve cambios hechos
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => $sub_grupo,
            'success' => 'Guardado exitosamente',
            'nom_sede' => $sede->nombre
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
            'id_sede' => 'required|int|max:20'
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
            $exist = TblSubgrupo::where('subgrupo', $request->subgrupo)
                ->where('id_sede', $request->id_sede)
                ->exists();

            if ($exist) {
                return response()->json(['error' => 'El sub grupo ya existe en la sede seleccionada.'], 422);
            }
            //comienza transacción
            DB::beginTransaction();
            $sub_grupo = TblSubgrupo::find($id);
            $sub_grupo->subgrupo = $request->subgrupo;
            $sub_grupo->id_sede = $request->id_sede;
            $sub_grupo->save();

            //consulta nombre sede para insertar en la tabla
            $sede = tbl_localidades_sede::find($request->id_sede);

            //confirma
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }


        return response()->json([
            'ok' => $sub_grupo,
            'success' => 'Actualizado exitosamente',
            'nom_sede' => $sede->nombre,
        ], 200);
    }
    //------------------------------------------------------------------------------------------

    //------------------------ FUNCIONES RELACIONADOS AL BUSADOR ------------------------------
    public function asignarBarrio(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'barrio' => 'required|string|max:255',
            'id' => 'required|int'
        ], [
            'id.required' => 'No se recibió el id del registro.',
            'id.int' => 'El id debe ser un numero entero.',
            'barrio.required' => 'Por favor ingrese un barrio.',
            'barrio.string' => 'El barrio debe ser una cadena de texto.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $id_resgistro = $request->input('id');
            $nom_barrio = $request->input('barrio');
            // Dividir la cadena por el primer punto
            $partes = explode('.', $nom_barrio);

            // El número estará en la primera parte del arreglo si existe
            $id_barrio = isset($partes[0]) ? trim($partes[0]) : null;

            DB::beginTransaction();

            $detalle = TblGruposDetalle::find($id_resgistro);
            $detalle->id_barrio = $id_barrio;
            $detalle->save();

            DB::commit();
            return response()->json(['ok' => 'Actualizado exitosamente.'], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function UpdateSelects(Request $request): \Illuminate\Http\JsonResponse
    {

        $municipio = $request->input('municipio');
        $barrio = $request->input('barrio');
        $grupo = $request->input('grupo');
        $subgrupo = $request->input('subgrupo');
        try {
            $busqueda = TblgruposDetalle::with(['tbl_grupo', 'tbl_subgrupo',
                'tbl_barrios', 'tbl_localidades_municipio']);
            //$barrio = "PARQUE DE LA CAÑA";
            if ($municipio) {
                $busqueda->where('id_mun', $municipio); // Filtra por el nombre del municipio
            }

            if ($barrio) {
                $busqueda->where('id_barrio', $barrio);
            }
            //$grupo = "CO";
            if ($grupo) {
                $busqueda->where('id_grupo', $grupo);
            }
            //$subgrupo = "CE2";
            if ($subgrupo) {
                $busqueda->where('id_subGrupo', $subgrupo);
            }

            $resultados = $busqueda->get();
            return response()->json(['data' => $resultados]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    // -------------------------------------------------------------------------------------------

    // ---------------- FUNCIONES RELACIONADOS CON EL ASIGNADOR DE INSPECTORES -------------------
    public function responsablesForm(): \Illuminate\Http\JsonResponse
    {
        $grupos = TblGrupo::where('status', 1)->get();
        $subgrupos = TblSubgrupo::where('status', 1)->get();

        return response()->json([
            'html' => view('zonas.partials.modal_inspectores', compact('grupos', 'subgrupos'))->render(),
        ]);
    }

    public function inspectoresPorGrupo(Request $request)
    {
        $grupo_id = $request->input('grupo');
        $subgrupo_id = $request->input('subgrupo');


        // Obtén el detalle usando el grupo y subgrupo (ajusta según tu lógica de negocio)
        $detalle = TblGruposDetalle::where('id_grupo', $grupo_id)
            ->where('id_subGrupo', $subgrupo_id)
            ->with('inspectores') // relación inspectores
            ->first();

        $asignados = $detalle ? $detalle->inspectores->pluck('id')->toArray() : [];

        // Todos los inspectores activos
        $inspectores = tbl_insp_cali::where('state', 1)->get();

        // Divide asignados y disponibles
        $inspectores_asignados = $inspectores->whereIn('id', $asignados)->values();
        $inspectores_disponibles = $inspectores->whereNotIn('id', $asignados)->values();

        return response()->json([
            'asignados' => $inspectores_asignados,
            'disponibles' => $inspectores_disponibles,
        ]);
    }

    public function responsablesStore(Request $request, $id_subgrupo, $id_grupo): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'inspectores' => 'array',
        ], [
                'inspectores.array' => 'El campo de inspectores debe ser un arreglo.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
        try {
            $detalles = TblGruposDetalle::where('id_grupo', $id_grupo)
                ->where('id_subGrupo', $id_subgrupo)
                ->get();
            $inspectores = $request->input('inspectores');

            foreach ($detalles as $detalle) {
                $detalle->inspectores()->sync($inspectores);
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json(['success' => 'Inspectores asignados correctamente'], 200);
    }

    public function responsablesInsp($id): \Illuminate\Http\JsonResponse
    {
        $detalle = TblGruposDetalle::findOrFail($id);
        $subgrupo = TblSubgrupo::findOrFail($detalle->id_subGrupo);
        $grupo = TblGrupo::findOrFail($detalle->id_grupo);
        return response()->json([
            'inspectores' => $detalle->inspectores,
            'subgrupo' => $subgrupo,
            'grupo' => $grupo,
        ]);

    }

    // -------------------------------------------------------------------------------------------

    public function recepcionMasiva(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xlsx',
        ],
            [
                'archivo.required' => 'Por favor seleccione un archivo.',
                'archivo.file' => 'La entrada debe ser un archivo.',
                'archivo.mimes' => 'El archivo debe ser un archivo de tipo xlsx.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $archivo = $request->file('archivo');

        $spreadsheet = IOFactory::load($archivo);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $validacion = $this->ValidacionMasiva($rows[0]);

        if (!$validacion) {
            return response()->json(['error' => 'El archivo no cumple con los criterios requeridos'], 422);
        }

        $indicador = $this->InsercionMasiva($rows);

        if ($indicador === true) {
            return response()->json(['success' => 'Datos insertados correctamente'], 200);
        } else {
            return response()->json(['error' => $indicador], 422);
        }
    }

    private function ValidacionMasiva($encabezados)
    {
        try {
            $indicador = true;
            foreach ($encabezados as $index => $encabezado) {

                switch ($index) {
                    case 0:
                        $indicador = $encabezado === "id_mun";
                        break;
                    case 1:
                        $indicador = $encabezado === "grupo";
                        break;
                    case 2:
                        $indicador = $encabezado === "sub_grupo";
                        break;
                    case 3:
                        $indicador = $encabezado === "barrio";
                        break;
                }

            }

            return $indicador;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    private function InsercionMasiva($datos)
    {
        try {
            DB::beginTransaction();
            $registros = [];
            foreach ($datos as $index => $dato) {
                if ($index == 0) {
                    continue; // Saltar cabecera si hay
                }

                $fila = []; // Guardar datos de la fila

                foreach ($dato as $index2 => $dato2) {
                    switch ($index2) {
                        case 0: // ID de municipio
                            $exist = tbl_localidades_municipio::where('id', $dato2)->exists();
                            if ($exist) {
                                $fila['id_mun'] = $dato2;
                            } else {
                                return 'el id del municipio no existe. revise columna A fila ' . ($index + 1);
                            }
                            break;

                        case 1: // grupo
                            $grupoRegistro = TblGrupo::where('grupo', $dato2)->first();
                            if ($grupoRegistro) {
                                $fila['id_grupo'] = $grupoRegistro->id;
                            } else {
                                return 'el grupo no existe. revise columna B fila ' . ($index + 1);
                            }
                            break;

                        case 2: // subgrupo
                            $subgrupoRegistro = TblSubgrupo::where('subgrupo', $dato2)->first();
                            if ($subgrupoRegistro) {
                                $fila['id_subGrupo'] = $subgrupoRegistro->id;
                            } else {
                                return 'el subgrupo no existe. revise columna C fila ' . ($index + 1);
                            }
                            break;

                        case 3: // barrio (puede ser null)
                            if ($dato2 == "") {
                                $fila['id_barrio'] = null;
                            } else {
                                $valor = new TblBarrios();
                                $valor->barrio = $dato2;
                                $valor->save(); // Guardar nuevo barrio y tomar el ID
                                $fila['id_barrio'] = $valor->id;
                            }
                            break;
                    }
                }

                $registros[] = $fila; // Añadir fila para inserción
            }

            // Si tienes una tabla intermedia llamada TblGruposDetalle por ejemplo:
            if (!empty($registros)) {
                TblGruposDetalle::insert($registros); // Inserta todos de una sola vez
                DB::commit();
                return true;
            }
            return 'No hay registros para insertar.';
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return 'Ocurrió un error al insertar los datos. ' . $e->getMessage();
        }
    }
}
