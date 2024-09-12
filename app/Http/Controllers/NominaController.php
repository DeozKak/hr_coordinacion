<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NominaController extends Controller
{
    public function diario()
    {
        return view('nomina.diario');
    }

    public function showAll()
    {
        $data = $this->getDataForAllMonths();
        return response()->json($data);
    }

    public function showMes($mes)
    {
        $data = $this->getDataForMonth($mes);
        return response()->json($data);
    }
    public function editar()
    {
        $data = [];
        return response()->json($data);

    }

    public function showEnero()
    {
        return $this->showMes('enero');
    }

    public function showFebrero()
    {
        return $this->showMes('febrero');
    }

    public function showMarzo()
    {
        return $this->showMes('marzo');
    }

    public function showAbril()
    {
        return $this->showMes('abril');
    }

    public function showMayo()
    {
        return $this->showMes('mayo');
    }

    public function showJunio()
    {
        return $this->showMes('junio');
    }

    public function showJulio()
    {
        return $this->showMes('julio');
    }

    public function showAgosto()
    {
        return $this->showMes('agosto');
    }

    public function showSeptiembre()
    {
        return $this->showMes('septiembre');
    }

    public function showOctubre()
    {
        return $this->showMes('octubre');
    }

    public function showNoviembre()
    {
        return $this->showMes('noviembre');
    }

    public function showDiciembre()
    {
        return $this->showMes('diciembre');
    }

    private function getDataForAllMonths()
    {
        return [
            ['Enero', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Febrero', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Marzo', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Abril', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Mayo', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Junio', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Julio', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Agosto', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Septiembre', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Octubre', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Noviembre', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Diciembre', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ];
    }
    private function getDataForMonth($mes)
    {
        $months = [
            'enero' => '01',
            'febrero' => '02',
            'marzo' => '03',
            'abril' => '04',
            'mayo' => '05',
            'junio' => '06',
            'julio' => '07',
            'agosto' => '08',
            'septiembre' => '09',
            'octubre' => '10',
            'noviembre' => '11',
            'diciembre' => '12',
        ];

        $monthNumber = $months[strtolower($mes)] ?? '01'; // Default to January if month is not found
        $year = Carbon::now()->year; // Use the current year
        $startDate = Carbon::create($year, $monthNumber, 1);
        $numDays = $startDate->daysInMonth;

        $data = [];

        for ($day = 1; $day <= $numDays; $day++) {
            $date = Carbon::create($year, $monthNumber, $day)->format('d/m/Y');
            $data[] = [$date, '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
        }

        return $data;
    }
}
