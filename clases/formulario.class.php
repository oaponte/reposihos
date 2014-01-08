<?php
/** formulario:
    Clase para el manejo de la informacion correspondiente a los formularios html y sus elementos
*/

class formulario
{
 
 	function formulario()
	{
		/**Constructor <Form> */
	}
 
 	/** pintarCombo : dibuja un combobox
	*   ENTRADA: 
	*   $Tabla    : Tabla de donde se va a mostrar la informacion del combo
	*   $CampoCodi : Campo identificador de la tabla
	*   $CampoNomb : Cambp de Nombre o descripcion de la tabla
	*   $Valor     : Valor por defecto que debe tomar el combo
	*  Array $Propiedades : Propiedades del combo, name,class,size,style....
	*
	*/
 	function pintarCombo($Tabla,$CampoCodi,$CampoNomb,$Contenido,$Propiedades="")
	{
		$sql="SELECT $CampoCodi,$CampoNomb
			  FROM   $Tabla";
		$result=query($sql);
		echo '<select ';
		if($Propiedades)
		{
			foreach($Propiedades as $Atributo=>$Valor)
			{
				echo ' '.$Atributo.'="'.$Valor.'"';
			}
		}
		echo '>';
		while($row=mysql_fetch_array($result))
		{
			if($Contenido!="" && !strcmp($Contenido,$row[0]))
				echo '<option selected value="'.$row[0].'">'.$row[1].'</option>';
			else
				echo '<option value="'.$row[0].'">'.$row[1].'</option>';
		}
	}
	
	/**
	*  function pintartextarea()
	*  Entrada
	*  $Valor
	*  Array $Propiedades : Propiedades del textarea, name,class,size,style....
	*/
	function pintarTextArea($Contenido="",$Propiedades="")
	{
		echo '<textarea ';
		if($Propiedades)
		{
			foreach($Propiedades as $Atributo=>$Valor)
			{
				echo ' '.$Atributo.'="'.$Valor.'"';
			}
		}
		echo '>'.$Contenido.'</textarea>';
 	}

	function pintarInput($Contenido="",$Propiedades="")
	{
		echo '<input ';
		if($Propiedades)
		{
			foreach($Propiedades as $Atributo=>$Valor)
			{
				echo ' '.$Atributo.'="'.$Valor.'"';
			}
		}
		echo ' value ="'.$Contenido.'">';
 	}
}

?>
