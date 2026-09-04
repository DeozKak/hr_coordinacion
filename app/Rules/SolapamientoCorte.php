<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Produccion\TblProduccionCorte;

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
        // Excluir el registro actual si está editando
    }
}
