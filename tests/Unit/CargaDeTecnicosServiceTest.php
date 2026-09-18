<?php

namespace Tests\Unit;

use App\Services\Programacion\CargaDeTecnicosService as Carga;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Carga por técnico del listado de ver programación.
 *
 * Límite: 7 visitas por jornada. Las de «todo el día» no tienen hora fija y
 * ocupan el hueco que quede, así que el día sólo se pasa cuando no caben ni
 * repartiéndolas (más de 14).
 */
class CargaDeTecnicosServiceTest extends TestCase
{
    private function carga(): Carga
    {
        return new Carga;
    }

    /** @return list<array{TECNICO: ?string, JORNADA: ?string, FECHA_AGENDAMIENTO: string}> */
    private function visitas(int $cuantas, ?string $jornada, string $tecnico = '105. BASTIDAS OSPINA IVAN', string $fecha = '2026-09-17'): array
    {
        return array_fill(0, $cuantas, ['TECNICO' => $tecnico, 'JORNADA' => $jornada, 'FECHA_AGENDAMIENTO' => $fecha]);
    }

    private function unico(array $filas): array
    {
        return $this->carga()->resumir($filas, comodines: [])[0];
    }

    public static function jornadasReales(): array
    {
        /* Todas las variantes que hay hoy en tbl_programacion_contratos. */
        return [
            ['mañana', Carga::MANANA], ['AM', Carga::MANANA],
            ['tarde', Carga::TARDE], ['PM', Carga::TARDE],
            ['todo el dia', Carga::TODO_EL_DIA], ['AM-PM', Carga::TODO_EL_DIA], ['AM y PM', Carga::TODO_EL_DIA],
            ['AM/PM', Carga::TODO_EL_DIA], ['AM - PM', Carga::TODO_EL_DIA], ['MAÑANA/TARDE', Carga::TODO_EL_DIA],
            ['AM 07:00 - 12:00 Y PM 02:00 - 06:00', Carga::TODO_EL_DIA],
            ['CUALQUIER', Carga::TODO_EL_DIA], ['CUALQUIERA', Carga::TODO_EL_DIA],
            ['CUALQUIER JORNADA', Carga::TODO_EL_DIA], ['CUALQUIER MOMENTO', Carga::TODO_EL_DIA],
            [null, Carga::SIN_JORNADA], ['', Carga::SIN_JORNADA], ['N/B', Carga::SIN_JORNADA],
        ];
    }

    #[DataProvider('jornadasReales')]
    public function test_la_jornada_se_entiende_venga_como_venga(?string $valor, string $esperada): void
    {
        $this->assertSame($esperada, $this->carga()->jornada($valor));
    }

    public function test_el_listado_va_de_mas_a_menos_programaciones_y_sin_tecnico_al_final(): void
    {
        $filas = array_merge(
            $this->visitas(3, 'mañana', '140. ARIZA'),
            $this->visitas(20, 'mañana', ''),                 // sin técnico, aunque sea el que más tiene
            $this->visitas(9, 'tarde', '115. LEAL'),
            $this->visitas(5, 'mañana', '183. CHANCI'),
        );

        $this->assertSame(
            ['115. LEAL', '183. CHANCI', '140. ARIZA', null],
            array_column($this->carga()->resumir($filas, comodines: []), 'tecnico')
        );
    }

    public function test_el_comodin_no_alerta_ni_encabeza_el_listado(): void
    {
        /* «100. OFICINA» aparta programaciones que no se van a ejecutar. Aunque
           acumule más que nadie, no es carga de ningún técnico. */
        $filas = array_merge(
            $this->visitas(30, 'mañana', '100. OFICINA OFICINA'),
            $this->visitas(12, 'mañana', '100. OFICINA'),        // el mismo comodín, escrito de otra forma
            $this->visitas(4, 'mañana', '115. LEAL'),
        );

        $listado = $this->carga()->resumir($filas, comodines: ['100']);

        $this->assertSame('115. LEAL', $listado[0]['tecnico'], 'el técnico real va primero');
        $this->assertFalse($listado[0]['esComodin']);

        $comodines = array_slice($listado, 1);
        $this->assertSame([true, true], array_column($comodines, 'esComodin'));
        $this->assertSame([[], []], array_column($comodines, 'alertas'), 'un comodín nunca alerta');
    }

    public function test_siete_por_la_manana_esta_en_el_limite_y_ocho_alerta(): void
    {
        $this->assertSame([], $this->unico($this->visitas(7, 'mañana'))['alertas']);

        $alertas = $this->unico($this->visitas(8, 'mañana'))['alertas'];
        $this->assertCount(1, $alertas);
        $this->assertSame('manana', $alertas[0]['tipo']);
        $this->assertSame(8, $alertas[0]['cantidad']);
        $this->assertSame('Mañana: 8 visitas (límite 7)', $alertas[0]['mensaje']);
    }

    public function test_la_tarde_tiene_su_propio_limite(): void
    {
        $alertas = $this->unico(array_merge($this->visitas(7, 'mañana'), $this->visitas(8, 'PM')))['alertas'];

        $this->assertContains('tarde', array_column($alertas, 'tipo'));
        $this->assertNotContains('manana', array_column($alertas, 'tipo'));
    }

    public function test_las_de_todo_el_dia_rellenan_el_hueco_y_no_suman_a_una_jornada(): void
    {
        // 7 + 2 fijas y 5 flexibles: 14, caben repartiendo.
        $cabe = array_merge($this->visitas(7, 'mañana'), $this->visitas(2, 'tarde'), $this->visitas(5, 'todo el dia'));
        $this->assertSame([], $this->unico($cabe)['alertas']);

        // Una más y ya no caben en el día, aunque ninguna jornada fija se pase.
        $alertas = $this->unico(array_merge($cabe, $this->visitas(1, 'CUALQUIER')))['alertas'];
        $this->assertSame(['dia'], array_column($alertas, 'tipo'));
        $this->assertSame('No caben en el día: 15 visitas para 14 cupos', $alertas[0]['mensaje']);
    }

    public function test_las_que_no_dicen_jornada_cuentan_para_el_dia(): void
    {
        $filas = array_merge($this->visitas(7, 'mañana'), $this->visitas(7, 'tarde'), $this->visitas(1, null));

        $resumen = $this->unico($filas);
        $this->assertSame(1, $resumen['sinJornada']);
        $this->assertSame(['dia'], array_column($resumen['alertas'], 'tipo'));
    }

    public function test_en_un_rango_el_limite_es_por_dia(): void
    {
        /* 4 por la mañana cada día son 8 en el rango, pero ningún día se pasa. */
        $filas = array_merge(
            $this->visitas(4, 'mañana', fecha: '2026-09-17'),
            $this->visitas(4, 'mañana', fecha: '2026-09-18'),
            $this->visitas(8, 'mañana', fecha: '2026-09-19'),
        );

        $resumen = $this->unico($filas);
        $this->assertSame(16, $resumen['total']);
        $this->assertSame(['2026-09-19'], array_column($resumen['alertas'], 'fecha'), 'sólo alerta el día que se pasa');
    }

    public function test_sin_tecnico_nunca_alerta(): void
    {
        $this->assertSame([], $this->unico($this->visitas(30, 'mañana', ''))['alertas']);
    }
}
