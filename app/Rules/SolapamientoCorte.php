<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Produccion\tbl_produccion_corte;

class SolapamientoCorte implements ValidationRule
{
    private $corte;
    public function __construct($corte){
        $this->corte=$corte;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 3. Validar que el rango de fechas no se solape con otro existente
        $solapamiento = tbl_produccion_corte::where(function ($query) use ($this->corte) {
            $query->whereBetween('fecha_inicio', [$this->corte->fecha_inicio, $this->corte->fecha_fin])
                  ->orWhereBetween('fecha_fin', [$this->corte->fecha_inicio, $this->corte->fecha_fin])
                  ->orWhere(function ($q) use ($this->corte) {
                    $q->where('fecha_inicio', '<', $this->corte->fecha_inicio)
                      ->where('fecha_fin', '>', $this->corte->fecha_fin);
                  });
          })->where('id', '!=', $this->corte->id)->first(); // Excluir el registro actual si está editando
    }
}
