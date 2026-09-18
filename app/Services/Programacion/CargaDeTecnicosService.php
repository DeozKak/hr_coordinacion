<?php

namespace App\Services\Programacion;

use App\Models\TblInspCali;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Cuánto tiene agendado cada técnico, y dónde se pasa de lo que puede hacer.
 *
 * Alimenta el listado de la pantalla de ver programación: técnicos ordenados de
 * más a menos visitas, con una alerta cuando la carga no cabe. Se calcula sobre
 * las mismas filas que muestra la rejilla, para que listado y tabla no puedan
 * contar cosas distintas.
 */
class CargaDeTecnicosService
{
    /** Visitas que un técnico puede hacer en una jornada. */
    public const LIMITE_POR_JORNADA = 7;

    /** Y en el día: las dos jornadas llenas. */
    public const LIMITE_POR_DIA = 2 * self::LIMITE_POR_JORNADA;

    public const MANANA = 'manana';

    public const TARDE = 'tarde';

    public const TODO_EL_DIA = 'todo_el_dia';

    public const SIN_JORNADA = 'sin_jornada';

    /** @var list<string>|null ids de los comodines, resueltos una sola vez */
    private ?array $comodines = null;

    /**
     * El listado, de más a menos programaciones.
     *
     * Las alertas se calculan por técnico y por día: en un rango de fechas,
     * tener 8 por la mañana un martes es un problema aunque el resto de la
     * semana esté holgada, y sumar la semana entera lo escondería.
     *
     * @param  iterable<object|array>  $filas  con TECNICO, JORNADA y FECHA_AGENDAMIENTO
     * @param  list<string>|null  $comodines  ids que no son técnicos de campo; null los consulta
     * @return list<array{tecnico: ?string, total: int, manana: int, tarde: int, todoElDia: int, sinJornada: int, esComodin: bool, alertas: list<array>}>
     */
    public function resumir(iterable $filas, ?array $comodines = null): array
    {
        $this->comodines = $comodines ?? $this->comodines;

        $porTecnico = collect($filas)
            ->map(fn ($fila) => (array) $fila)
            ->groupBy(fn (array $fila) => $this->tecnico($fila['TECNICO'] ?? null) ?? '');

        return $porTecnico
            ->map(fn (Collection $suyas, string $tecnico) => $this->resumenDe($tecnico === '' ? null : $tecnico, $suyas))
            ->sort(function (array $a, array $b) {
                /* Abajo del todo lo que no es carga de nadie: primero los
                   comodines, después las filas sin técnico. Encabezando la lista
                   —«100. OFICINA» suele ser de las que más acumula— taparían a
                   quien sí tiene trabajo por delante. */
                $peso = fn (array $t) => match (true) {
                    $t['tecnico'] === null => 2,
                    $t['esComodin'] => 1,
                    default => 0,
                };

                if ($peso($a) !== $peso($b)) {
                    return $peso($a) <=> $peso($b);
                }

                return [$b['total'], $a['tecnico']] <=> [$a['total'], $b['tecnico']];
            })
            ->values()
            ->all();
    }

    /**
     * La jornada tal como se entiende, venga como venga escrita.
     *
     * En la base conviven «mañana» y «AM», «tarde» y «PM», y una docena de
     * formas de decir que da igual («AM/PM», «CUALQUIER MOMENTO», «AM 07:00 -
     * 12:00 Y PM 02:00 - 06:00», «MAÑANA/TARDE»…). Por eso son reglas y no una
     * lista: una variante nueva cae en su sitio sin tocar esto.
     */
    public function jornada(?string $valor): string
    {
        $texto = Str::of((string) $valor)->ascii()->lower()->squish()->toString();

        $manana = (bool) preg_match('/\b(am|manana)\b/', $texto);
        $tarde = (bool) preg_match('/\b(pm|tarde)\b/', $texto);

        return match (true) {
            ($manana && $tarde)
                || str_starts_with($texto, 'cualquier')
                || str_contains($texto, 'todo el dia') => self::TODO_EL_DIA,
            $manana => self::MANANA,
            $tarde => self::TARDE,
            default => self::SIN_JORNADA,
        };
    }

    private function tecnico(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    /**
     * ¿Es un comodín y no una persona?
     *
     * `100. OFICINA` marca las programaciones que no se van a ejecutar: sirve
     * para sacarlas de un técnico real. No es carga de nadie, así que ni alerta
     * ni compite por los primeros puestos del listado.
     *
     * Se reconocen por el id que encabeza el nombre («100. OFICINA OFICINA» y
     * «100. OFICINA» son el mismo), y los ids salen de la base: comodín es el
     * inspector sin cédula. Así, otro que creen mañana entra solo.
     */
    private function esComodin(?string $tecnico): bool
    {
        if ($tecnico === null) {
            return false;
        }

        preg_match('/^\s*(\d+)\s*\./', $tecnico, $coincidencia);

        return isset($coincidencia[1]) && in_array($coincidencia[1], $this->comodines(), true);
    }

    /** @return list<string> */
    private function comodines(): array
    {
        return $this->comodines ??= TblInspCali::query()
            ->where(fn ($q) => $q->whereIn('cedula', ['0', ''])->orWhereNull('cedula'))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function resumenDe(?string $tecnico, Collection $filas): array
    {
        $jornadas = $filas->map(fn (array $fila) => $this->jornada($fila['JORNADA'] ?? null));
        $comodin = $this->esComodin($tecnico);

        return [
            'tecnico' => $tecnico,
            'total' => $filas->count(),
            'manana' => $jornadas->filter(fn ($j) => $j === self::MANANA)->count(),
            'tarde' => $jornadas->filter(fn ($j) => $j === self::TARDE)->count(),
            'todoElDia' => $jornadas->filter(fn ($j) => $j === self::TODO_EL_DIA)->count(),
            'sinJornada' => $jornadas->filter(fn ($j) => $j === self::SIN_JORNADA)->count(),
            'esComodin' => $comodin,
            /* Sin técnico, o con un comodín, no hay a quién sobrecargar. */
            'alertas' => $tecnico === null || $comodin ? [] : $this->alertas($filas),
        ];
    }

    /**
     * Las visitas de «todo el día» no tienen hora fija: ocupan el hueco que
     * quede. Así que una jornada sólo se pasa del límite por sus visitas fijas,
     * y el día se pasa cuando ni repartiendo las flexibles caben las dos
     * jornadas llenas. Las que no dicen jornada cuentan para el día, que es lo
     * único seguro de ellas.
     */
    private function alertas(Collection $filas): array
    {
        return $filas
            ->groupBy(fn (array $fila) => substr((string) ($fila['FECHA_AGENDAMIENTO'] ?? ''), 0, 10))
            ->sortKeys()
            ->flatMap(function (Collection $delDia, string $fecha) {
                $jornadas = $delDia->map(fn (array $fila) => $this->jornada($fila['JORNADA'] ?? null));
                $alertas = [];

                foreach ([self::MANANA => 'Mañana', self::TARDE => 'Tarde'] as $clave => $nombre) {
                    $cantidad = $jornadas->filter(fn ($j) => $j === $clave)->count();

                    if ($cantidad > self::LIMITE_POR_JORNADA) {
                        $alertas[] = $this->alerta($fecha, $clave, $cantidad, self::LIMITE_POR_JORNADA,
                            "{$nombre}: {$cantidad} visitas (límite ".self::LIMITE_POR_JORNADA.')');
                    }
                }

                if ($delDia->count() > self::LIMITE_POR_DIA) {
                    $alertas[] = $this->alerta($fecha, 'dia', $delDia->count(), self::LIMITE_POR_DIA,
                        "No caben en el día: {$delDia->count()} visitas para ".self::LIMITE_POR_DIA.' cupos');
                }

                return $alertas;
            })
            ->values()
            ->all();
    }

    private function alerta(string $fecha, string $tipo, int $cantidad, int $limite, string $mensaje): array
    {
        return compact('fecha', 'tipo', 'cantidad', 'limite', 'mensaje');
    }
}
