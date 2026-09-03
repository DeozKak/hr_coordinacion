<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Causales que cuentan como legalización.
 *
 * Hasta ahora bastaba con estar en tbl_cerradas para dar una orden por
 * legalizada, y eso no es cierto: el mismo archivo trae cierres que no
 * legalizan nada —"APLAZADO POR EL USUARIO", "CASA SOLA"— y esos contratos
 * desaparecían de los pendientes sin haberse legalizado.
 *
 * La lista va en base de datos y no escrita en el código porque GDO añade
 * causales cada tanto y quien las conoce es coordinación, no el programador.
 *
 * `clave` es la causal normalizada —sin tildes, en mayúsculas y con un solo
 * espacio— y es por donde se compara: la misma causal llega escrita de formas
 * distintas según el export.
 */
return new class extends Migration
{
    /** Las nueve con las que arranca, tal como vienen en el archivo de GDO. */
    private const INICIALES = [
        'CERTIFICADA',
        'INSTALACION CERTIFICADA',
        'CERTIFICADA CON NOVEDAD',
        'PENDIENTE POR CERTIFICAR DEFECTOS CRITICOS ACEPTA TRABAJOS',
        'DEFECTO CRITICO Y NO ACEPTA REPARACIONES',
        'TRABAJOS HECHOS POR TERCEROS CON DEFECTOS',
        'PENDIENTE POR CERTIFICAR ACEPTA TRABAJOS',
        'PENDIENTE POR CERTIFICAR NO ACEPTA TRABAJOS',
        'OT. CUMPLIDA INSTALACIÓN CON DEFECTO',
    ];

    public function up(): void
    {
        Schema::create('tbl_causales_legalizacion', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('causal');                 // como se escribe y se muestra
            $tabla->string('clave')->unique();        // como se compara
            $tabla->boolean('activa')->default(true);
            $tabla->unsignedBigInteger('creado_por')->nullable();
            $tabla->timestamps();

            $tabla->index('activa');
        });

        $ahora = now();

        DB::table('tbl_causales_legalizacion')->insert(
            array_map(fn (string $causal) => [
                'causal'     => $causal,
                'clave'      => \App\Models\CausalLegalizacion::normalizar($causal),
                'activa'     => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], self::INICIALES)
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_causales_legalizacion');
    }
};
