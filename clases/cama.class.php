<?php
/**
*	Clase para el manejo de los estados en las camas
*/
include_once(SIH_PATH . 'funciones/funciones.php');
class Cama 
{
	function consultarservCama($CodiCama)
	{
		global $CodiInst;
		$sql="SELECT CodiServ
			  FROM   CodiCama
			  WHERE  CodiInst='$CodiInst' AND CodiCama='$CodiCama'";
		$row=query($sql,1);
		return($row[0]);
	}
	
	
	/**
	* ocuparCama($CodiCama) : metodo para registrar el uso de la cama
	* ENTRADA:
	* 	$CodiCama : Codigo de la cama almacenada en la bd
	* 	$ConsAdmi : Admision en la cual se quiere utilizar la cama
	*	$FechIngr : Fecha de Ingreso, enviado solo en el caso de la creacion de la admision
	*				Por defecto Fecha Actual
	*	$HoraIngr : Hora de Ingreso, enviado solo en el caso de la creacion de la admision
	*				Por defecto Hora Actual
	* GLOBALES
	*  	$CodiInst : Codigo de la intitucion
	* SALIDA
	*
	*/

	function ocuparCama($CodiCama,$ConsAdmi,$FechIngr="",$HoraIngr="")
	{
		global $CodiInst,$UsuaDigi;
		if($FechIngr=="")
			$FechIngr=date("Y-m-d");
		if($HoraIngr=="")
			$HoraIngr=date("H:i:s");
		
 		/** SE VERIFICA SI LA CAMA SE ENCUENTRA DISPONIBLE */
		$this->verificarCama($CodiCama,$ConsAdmi);

		/**
		*	VALDACION DE QUE LA FECHA DE LA OCUPACION DE LA CAMA
		*   NO SEA SUPERIOR O INFERIOR A LA FECHA DE INGRESO DE LA ADMISION
		*/
		$sql="SELECT FechIngr,HoraIngr
			  FROM   Admision
			  WHERE  CodiInst='$CodiInst' AND ConsAdmi='$ConsAdmi'";
		$row=query($sql,1);
		$FechAdmi=$row['FechIngr'];
		$HoraAdmi=$row['HoraIngr'];
		$FechActu=date('Y-m-d');
		$HoraActu=date('H:i:s');

		list ($anno1, $mes1,$dia1)=explode("-",$FechIngr);
		list ($anno2, $mes2,$dia2)=explode("-",$FechAdmi);
		list ($anno3, $mes3,$dia3)=explode("-",$FechActu);
		list ($hora1, $minuto1,$segundo1)=explode(":",$HoraIngr);
		list ($hora2, $minuto2,$segundo2)=explode(":",$HoraAdmi);
		list ($hora3, $minuto3,$segundo3)=explode(":",$HoraActu);
		$TimeIngr=mktime($hora1,$minuto1,$segundo1,$mes1,$dia1,$anno1); ## TIEMPO EN SEGUNDOS
		$TimeAdmi=mktime($hora2,$minuto2,$segundo2,$mes2,$dia2,$anno2); ## TIEMPO EN SEGUNDOS
		$TimeActu=mktime($hora3,$minuto3,$segundo3,$mes3,$dia3,$anno3); ## TIEMPO EN SEGUNDOS
		if($TimeIngr<$TimeAdmi)
			mensaje("La fecha del traslado no debe ser inferior a la fecha de la admision");
		if($TimeIngr>$TimeActu)
			mensaje("La fecha del traslado no debe ser superior a la fecha actual");

		$valor=$time2-$time1;

 		$sql="UPDATE CodiCama
			  SET    ConsAdmi='$ConsAdmi'
			  WHERE  CodiInst='$CodiInst' AND CodiCama='$CodiCama'";
		query($sql);

		$sql="SELECT MAX(ConsTras) MaxConsTras
			  FROM   TrasCama
			  WHERE  CodiInst='$CodiInst' AND ConsAdmi='$ConsAdmi'";
		$row=query($sql,1);

		$ConsTras=$row['MaxConsTras']+1;
		$ServCama=$this->consultarservCama($CodiCama);
		$sql="INSERT INTO TrasCama(ConsAdmi,CodiInst,ConsTras,CodiServ,CamaOrig,FechIngr,HoraIngr,FechDigi,HoraDigi,UsuaDigi,FechModi,HoraModi,UsuaModi)
              VALUES ('$ConsAdmi','$CodiInst',$ConsTras,'$ServCama','$CodiCama','$FechIngr','$HoraIngr',curdate(),curtime(),'$UsuaDigi',curdate(),curtime(),'$UsuaDigi')";
		query($sql);

		/** SELECCIONO EL SERVICIO DE LA CAMA, PARA ACTUALIZARLO EN LA ADMISION*/
		$sql="SELECT CodiServ
			  FROM   CodiCama
			  WHERE  CodiInst='$CodiInst' AND CodiCama='$CodiCama'";
		$row=query($sql,1);
		$ServEgre=$row[0];
		/** SE IDENTIFICA EL TRASLADO DE CAMA  Y EL PARAMETRO PARA REALIZAR SOLEMENTE ACTUALIZACION A LA CAMA ACTUAL*/
		$sqlstr="UPDATE Admision 
				 SET    ServEgre='$ServEgre', CamaActu='$CodiCama' ,FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
				 WHERE  CodiInst='$CodiInst' AND ConsAdmi='$ConsAdmi'";
		query($sqlstr);
		/** SE VERIFICA SI LA CAMA SE ENCUENTRA DISPONIBLE */
		$this->verificarCama($CodiCama,$ConsAdmi);
	}

	/**
	* ocuparCama($CodiCama) : metodo para realizar traslado entre camas
	* ENTRADA:
	* 	$CamaOrig : Codigo de la cama almacenada en la bd en estado ocupada
	* 	$CamaDest : Codigo de la cama que se requiere para realizar el traslado del paciente
	* 	$ConsAdmi : Admision en la cual se quiere utilizar la cama
	*	$FechIngr : Fecha de traslado
	*	$HoraIngr : Hora de traslado
	*				Por defecto Hora Actual
	* GLOBALES
	*  	$CodiInst : Codigo de la intitucion
	* SALIDA
	*
	*/

	function trasladarPaciente($CamaOrig,$CamaDest,$ConsAdmi,$FechTras="",$HoraTras="")
	{
		global $CodiInst,$UsuaDigi;
		if($FechTras=="")
			$FechTras=date("Y-m-d");
		if($HoraTras=="")
			$HoraTras=date("H:i:s");

		/**
		*  VALIDACION DE QUE LA FECHA Y HORA DE TRASLADO NO SEA SUPERIOR A LA FECHA Y HORA DE INGRESO
		*  EN ESA MISMA CAMA
		**/
		$sql = "SELECT  FechIngr,HoraIngr 
				  FROM  TrasCama
				 WHERE  CodiInst = '$CodiInst' AND ConsAdmi = '$ConsAdmi' AND CamaOrig = '$CamaOrig' AND 
				        ((FechIngr='$FechTras' AND HoraIngr>='$HoraTras') OR (FechIngr>'$FechTras')) 
			  ORDER BY  ConsTras DESC";
		$result = query($sql,1); 
		if($Filas==0)
		{
			if($CamaOrig=="")
				mensaje("La cama de origen no puede estar vacia");			
 			$this->liberarCama($CamaOrig,$ConsAdmi,$FechTras,$HoraTras,$CamaDest);
 			$this->ocuparCama($CamaDest,$ConsAdmi,$FechTras,$HoraTras);
 		}
		else
			mensaje("Atencion la fecha y hora de traslado es menor a la ultima fecha");
  	}
	
	/**
	* liberarCama($CamaOrig,$ConsAdmi,$FechSali="",$HoraSali="",$Opcion=1) :  metodo para verificar si una cama se encuentra disponible
	* ENTRADA:
	* 	$CamaOrig : Codigo de la cama almacenada en la bd en estado ocupada
	* 	$Opcion : Bandera que indica si se libera la cama elminando los traslados realizados
					1. Completa el registro de fecha de salida y dias de estancia en TrasCama
					2. Elimina todos los registros de trascama, no genera estadistica de estancia,
						en el caso de las liquidaciones que se generan a partir de una anulada
					XXXX. Cualquier otro indica el Codigo de la Cama de Destino en el caso de los traslados
	*	$FechSali:	Fecha de salida
	*	$HoraSali:  Hora de Salida
	* 	$Opcional : Admision en la cual se quiere utilizar la cama
	* GLOBALES
	*  	$CodiInst : Codigo de la intitucion
	* SALIDA
	*
	*/

	function liberarCama($CamaOrig,$ConsAdmi,$FechSali="",$HoraSali="",$Opcion=1)
	{
		global $CodiInst,$UsuaDigi,$Filas;
		/***/
		$sql="SELECT CodiCama
			  FROM   CodiCama
			  WHERE  CodiInst='$CodiInst' AND ConsAdmi='$ConsAdmi'";
		$row=query($sql,1); 
 		if($Filas==0)
			mensaje("Atencion la cama que se intenta liberar < $CamaOrig > no existe o se ha desocupado con anterioridad, verifique e intente de nuevo");
		
 		if($FechSali=="")
			$FechSali=date("Y-m-d");
		if($HoraSali=="")
			$HoraSali=date("H:i:s");
 		if($Opcion!=2)
		{
			$Tmp=$this->consultarTiempo($ConsAdmi,$CodiInst,$FechSali,$HoraSali);
			list($ConsTras,$Dias,$Horas)=explode("-",$Tmp);
			
			/** SI SE ENVIA LA CAMA DE DESTINO INDICA QUE ES UN TRASLADO DE CAMA */
			if($Opcion!=1)
				$CamaDest=$Opcion;	
			
			$ServCama=$this->consultarservCama($CamaDest);
			$sqlstr="UPDATE TrasCama 
					 SET	CamaDest='$CamaDest',ServEgre='$ServCama',FechSali='$FechSali',HoraSali='$HoraSali',Dias=$Dias,Horas=$Horas,
							FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
					 WHERE  CodiInst='".$CodiInst."' AND ConsAdmi='$ConsAdmi' AND  ConsTras=$ConsTras";
			query($sqlstr); //echo 'update: '.$sqlstr; exit();
		}
		else
		{
			$sql="DELETE FROM TrasCama WHERE CodiInst='".$CodiInst."' AND ConsAdmi='$ConsAdmi'";
			query($sql);
 		}
	
		$sqlstr="UPDATE CodiCama 
		 		 SET    ConsAdmi='',FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
		 		 WHERE  CodiInst='".$CodiInst."' AND CodiCama='$CamaOrig'";
		query($sqlstr);
 	}


		function consultarTiempo($ConsAdmi,$CodiInst,$FSali="",$HSali="")
		{
 			/**
			*   SE BUSCA LA FECHA Y HORA DEL ULTIMO REGISTRO EN TRASCAMA PARA LA ADMISION
			*   Y SE CALCULAN LAS HORAS DE ESTANCIA
			*/
			$sqlstr="SELECT ConsTras,FechIngr, HoraIngr 
					  FROM   TrasCama t 
					  WHERE  CodiInst='".$CodiInst."' AND ConsAdmi='$ConsAdmi' 
					  ORDER  BY ConsTras DESC";
			 $result=query($sqlstr); 
			 $row=mysql_fetch_array($result);
			 mysql_free_result($result);
			 $ConsTras=$row[0];
			 $FechIngr=$row[1];
			 $HoraIngr=$row[2];
			 
			 if($FechSali == '')
			 	$FechSali=$FSali;
			 
			 if($HoraSali == '')
			 	$HoraSali=$HSali;
							
							 
			if($HoraIngr!="" && $FechIngr!="")
			{
			  $horas=choras($HoraIngr,$HoraSali,"h",$FechIngr,$FechSali);
			  $tmp=explode(" ",$horas);
			  $horas=$tmp[0];
			}
			$Dias=sprintf("%d",($horas/24));
			$Horas=$horas%24;
			return($ConsTras."-".$Dias."-".$Horas);
		}

	/**
	* verificarCama($CodiCama) :  metodo para verificar si una cama se encuentra disponible
	* ENTRADA:
	* 	$CodiCama : Codigo de la cama almacenada en la bd
	* GLOBALES
	*  	$CodiInst : Codigo de la intitucion
	* SALIDA
	*
	*/
	function verificarCama($CodiCama,$ConsAdmi)
	{
		global $CodiInst,$UsuaDigi;
		$sql="SELECT ConsAdmi
			  FROM   CodiCama
			  WHERE  CodiInst='".$CodiInst."' AND CodiCama='$CodiCama'";
		$row=query($sql,1);
		if($row['ConsAdmi']!=$ConsAdmi && $row['ConsAdmi']!="")
			mensaje("La cama que ha seleccionado, ha sido ocupada por la admision ".$row['ConsAdmi']);
	}
	
	/**
	* verificarLiberacion($CodiCama) :  metodo para verificar si una cama se desocupo correctamente
	* ENTRADA:
	* 	$ConsAdmi : Codigo de la admision
	* GLOBALES
	*  	$CodiInst : Codigo de la intitucion
	*  	$UsuaDigi : Usuario en sesion
	* SALIDA
	*
	*/
	function verificarLiberacion($ConsAdmi)
	{
		global $CodiInst,$UsuaDigi;
		$sql="SELECT CodiCama
			  FROM   CodiCama
			  WHERE  CodiInst='".$CodiInst."' AND ConsAdmi='$ConsAdmi'";
		$row=query($sql,1);
		if($Filas>0)
			mensaje("La cama $row[0] no se ha liberado correctamente , por favor intentelo de nuevo.");
	}	
}
?>