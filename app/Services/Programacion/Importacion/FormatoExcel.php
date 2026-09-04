<?php

namespace App\Services\Programacion\Importacion;

/**
 * El formato que debe tener un Excel para poder importarse.
 *
 * Todas las subidas del módulo —base, programación masiva de técnicos y call
 * center— comparten el mismo problema: hay que mirar la primera fila para
 * saber si el archivo es el que toca. Cada una lo resolvía a su manera y una
 * de ellas mal; aquí el formato se declara una vez y se comprueba igual para
 * todos.
 */
class FormatoExcel
{
    /**
     * @param string $nombre Cómo se llama el formato de cara al usuario.
     * @param array<string, string> $cabeceras Letra de columna => cabecera esperada.
     */
    public function __construct(
        public readonly string $nombre,
        public readonly array $cabeceras
    ) {}

    /**
     * Columnas cuya cabecera no es la esperada.
     *
     * @param array<string, mixed> $primeraFila Letra de columna => valor leído.
     * @return array<string, array{esperaba: string, encontro: string}>
     */
    public function discrepancias(array $primeraFila): array
    {
        $fallos = [];

        foreach ($this->cabeceras as $columna => $esperada) {
            $encontrada = trim((string) ($primeraFila[$columna] ?? ''));

            if ($encontrada !== trim($esperada)) {
                $fallos[$columna] = ['esperaba' => $esperada, 'encontro' => $encontrada];
            }
        }

        return $fallos;
    }

    /** ¿El archivo tiene TODAS las cabeceras en su sitio? */
    public function coincide(array $primeraFila): bool
    {
        return $this->discrepancias($primeraFila) === [];
    }

    /**
     * Mensaje que le dice al usuario qué columna está mal.
     *
     * Antes todos los errores eran el mismo "el archivo no cumple el formato",
     * y con veinte columnas eso no ayuda a arreglar nada. Se nombran las tres
     * primeras que fallan: con más, la lista deja de leerse.
     */
    public function explicarError(array $primeraFila): string
    {
        $fallos = $this->discrepancias($primeraFila);

        if ($fallos === []) {
            return '';
        }

        $detalle = collect($fallos)
            ->take(3)
            ->map(fn (array $f, string $col) => $f['encontro'] === ''
                ? "la columna {$col} debería ser \"{$f['esperaba']}\" y está vacía"
                : "la columna {$col} debería ser \"{$f['esperaba']}\" y dice \"{$f['encontro']}\"")
            ->implode('; ');

        $resto = count($fallos) > 3 ? ' y ' . (count($fallos) - 3) . ' más' : '';

        return "El archivo no tiene el formato de {$this->nombre}: {$detalle}{$resto}.";
    }
}
