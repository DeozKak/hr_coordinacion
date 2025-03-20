<?php

namespace App\Models\Bitacoras;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tbl_bitacoras_causal extends Model
{
    use HasFactory;

    protected $table = 'tbl_bitacoras_causales';

    public $timestamps = false;

}
