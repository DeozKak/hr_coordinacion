<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Zonificacion\tbl_localidades_municipio;
class UniqueMunicipio implements ValidationRule
{
    protected $sede;
    protected $zona;

    public function __construct($sede, $zona){
        $this->sede = $sede;
        $this->zona = $zona;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existingMunicipio = tbl_localidades_municipio::where('nombre', $value)
            ->where('id_sede', $this->sede)
            ->where('id_zona', $this->zona)
            ->exists();

        if ($existingMunicipio) {
            $fail('El municipio ya existe con la misma sede y zona.');
        }

    }
}
