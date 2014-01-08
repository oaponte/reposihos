<?php
class Fecha 
{
    var $fecha;
    function Fecha($a = 0, $m = 0, $d = 0) 
	{
        if ($a==0) $a = date("Y");
        if ($m==0) $m = date("m");
        if ($d==0) $d = date("d");
        $this -> fecha = date("Y-m-d", mktime(0,0,0,$m,$d,$a));
    }
    function SumaTiempo($a = 0, $m = 0, $d = 0) 
	{
        $array_date = explode("-", $this->fecha);
        $this->fecha = date("Y-m-d", mktime(0, 0, 0, $array_date[1] + $m, $array_date[2] + $d, $array_date[0] + $a));
    }
    function getFecha() 
	{ 
		return $this->fecha; 
	}
}

/*
//EJEMPLO DE IMPLEMENTACION
$fecha_tope = new Fecha();
$fecha_tope -> SumaTiempo(3, 2, -14);
$fecha_max = $fecha_tope -> getFecha();
*/
?>