<?php

namespace App\Services\Programacion;

use RuntimeException;

/**
 * Una fila que no se puede exportar a GDW.
 *
 * Lleva el mensaje que ve el usuario con el número de fila incluido: en una
 * tabla de doscientas líneas, saber cuál falla es la mitad del arreglo.
 */
class PlantillaInvalida extends RuntimeException
{
}
