<?php
include_once(SIH_PATH."clases/formulario.class.php");
/**
 historia:
  Clase para el manejo de la informacion correspondiente a los formularios 
  de historia clinica predefinidos
*/
class historia extends formulario
{
	public $TipoObje,$CodiInst,$ConsAdmi,$TipoDocu,$NumeUsua,$Activo,$Modulo,$UnidEdad,$ValoEdad,$SexoUsua;
	/** Constructor */
	function historia($Objeto)
	{
		global $CodiInst,$ConsAdmi,$TipoDocu,$NumeUsua,$Activo,$Modulo,$UnidEdad,$ValoEdad,$SexoUsua,$FechNaci;
		$this->CodiInst=$CodiInst;
		$this->ConsAdmi=$ConsAdmi;
		$this->TipoDocu=$TipoDocu;
        $this->NumeUsua=$NumeUsua;
        $this->Activo=$Activo;
        $this->Modulo=$Modulo;
		$FechaNaci=explode("-",$FechNaci);
		$Edad=calcedad($FechaNaci[2],$FechaNaci[1],$FechaNaci[0],0,0,0,"ano",0,1);
		/*
 		$this->UnidEdad=$UnidEdad;
        $this->ValoEdad=$ValoEdad;
		*/
        $this->ValoEdad=$Edad;
		$this->SexoUsua=$SexoUsua;
		$sql="SELECT TipoObje
			  FROM   Objetos
			  WHERE  CodiObje='$Objeto'";
		$row=query($sql,1);
        $this->TipoObje=$row[0];
	}

	/** CONSULTA ALGUNOS DATOS DE LA ADMISION*/
	function consultarAdmision()
	{
		$sql="SELECT CondUsua
			  FROM   Admision
			  WHERE  ConsAdmi='".$this->ConsAdmi."' AND CodiInst='".$this->CodiInst."'";
		$row=query($sql,1);
		$Array['CondUsua']=$row['CondUsua'];
		return($Array);
	}

	/**
	*  consultarNombre():  consulta el campo nombre de una tabla $Tabla
	*                      filtrando por el campo codigo $CampoId
	*  $Tabla   :  Nombre de la tabla
	*  $CampoId :  Identificador para filtrar la consulta y mostrar el nombre 
	*/
	function consultarNombre($Tabla,$CampoCodi,$CampoNomb,$CampoId="")
	{
 		$sql="SELECT $CampoNomb
			  FROM   $Tabla
			  WHERE  $CampoCodi='$CampoId'";
		$row=query($sql,1);
		return($row[0]);
	}
	
	/**
	* consultarPreguntas() : Consulta el contenido de las preguntas de un objeto de acuerdo al tipo y modulo
	* ENTRADA 
	* $ConsData  : Consecutivo almacenado en la base de datos, utilizado unicamente para visualizar la informacion
	* $Impresion :  Bandera que identifica si se esta en modo impresion
	*
	*/
	
	function consultarPreguntas($ConsData="",$Impresion=0)
	{
		if($Impresion==1)
		{
			$ClassTabla="";
			$ClassTitulo="headInterLeft";
			$ClassFila="texto";
			$WidthTabla="700";
		}
		else
		{
			$ClassTabla="tabla";
			$ClassTitulo="combo";
 			$ClassFila="cajastexton";
			$WidthTabla="100%";
		}
		$DatosAdmision=$this->consultarAdmision();
		$CondUsua=$DatosAdmision['CondUsua'];
		/** SE EVALUA SI ES IMPRESION PARA MOSTRAR LOS DATOS ALMACENADOS UNICAMENTE o VISUALIZACION */
		if($Impresion==1 /*|| $ConsData!=""*/)
		{
			$sql="SELECT CI.CodiItem,CI.CodiSecc,CI.Pregunta,CI.CodiSino,CI.Texto,CI.Valor1,CI.Valor2,CI.RangMini,CI.RangMaxi,DD.ConsData
				  FROM   CodiItem CI,DetaData DD
				  WHERE  DD.CodiItem=CI.CodiItem AND 
				  		 DD.ConsAdmi='".$this->ConsAdmi."' AND 
						 DD.CodiInst='".$this->CodiInst."' AND 
 						 DD.TipoObje='".$this->TipoObje."' AND 
						 DD.ConsData=$ConsData
				 ORDER   BY CI.CodiSecc ASC,CI.NumeOrde ASC";
		}
		else/** DE LO CONTRARIO INDICA QUE SE ESTA LLAMANDO DESDE EL FORMULARIO PARA SER ALMACENADO*/
		{
			$sql="SELECT CodiItem,CodiSecc,Pregunta,CodiSino,Texto,Valor1,Valor2,RangMini,RangMaxi,
						 DefeText,DefeVal1,DefeVal2,DefeSiNo
				  FROM   CodiItem
				  WHERE  CodiModu='".$this->Modulo."' AND TipoObje='".$this->TipoObje."'
						 AND CondUsua IN (0,'$CondUsua') AND (EdadMini<='".$this->ValoEdad."' AND EdadMaxi>='".$this->ValoEdad."')
						 AND Sexo IN ('X','".$this->SexoUsua."') AND Activo='1'
				  ORDER  BY CodiSecc ASC,NumeOrde ASC";
			/**Bandera para motrar grillado*/
			$banderaPintado=1;
		}
		$result=query($sql);
 		while($row=mysql_fetch_array($result))
		{
			$CodiItem=$row['CodiItem'];
			$CodiSecc=$row['CodiSecc'];
			$Pregunta=$row['Pregunta'];
			$CodiSino=$row['CodiSino'];
			$Texto=$row['Texto'];
			$Valor1=$row['Valor1'];
			$Valor2=$row['Valor2'];
			$RangMini=$row['RangMini'];
			$RangMaxi=$row['RangMaxi'];
			$DefeText=$row['DefeText'];
			$DefeVal1=$row['DefeVal1'];
			$DefeVal2=$row['DefeVal2'];
			$DefeSiNo=$row['DefeSiNo'];
			if($CodiSecc!=$CodiSeccOld)
			{
				$NombSecc=$this->consultarNombre("Seccion","CodiSecc","NombSecc",$CodiSecc);
				if( $i>0)
				echo '
				</tr>
				</table>
				';
				echo '
					 <table align="center" class="'.$ClassTabla.'" width="'.$WidthTabla.'"  cellpading="0" cellspacing="0" style="font-size:12px">
					 	<tr>
					  <tr>
					  	<td class="'.$ClassTitulo.'" colspan="2"><br><strong>'.$NombSecc.'</strong></td>
					  </tr>
					   <tr>';
					  $i=0;
					  $j=0;
			}

			/** SE EVALUA SI SE MUESTRAN DOS PREGUNTAS POR FILA
			*   SI HAN PASADO DOS PREGUNTAS, SE CIERRA LA FILA
			*   DE LO CONTRARIO SE CONTINUA LA FILA Y SE ABRE UNA NUEVA COLUMNA
			*/
			$ValoresPreguntas=$this->consultarValores($CodiItem,$ConsData);
			/**Si la respuesta a la pregunta se encuentra vacia no la mostramos*/
			if(($ValoresPreguntas['CodiSino']!=0 || $ValoresPreguntas['Texto']!='' || $ValoresPreguntas['Valor1']!=0 || $ValoresPreguntas['Valor2']!=0) || $banderaPintado==1)
			{
				if($i%2==0 && $i>0)
				echo '</tr>
					  <tr>
						<td class="'.$ClassFila.'" width="50%">';
				else
				echo '<td class="'.$ClassFila.'"  width="50%">';
				
				echo '
					<table align="center" width="100%" height="100%" border="0" style="font-size:12px">
					<tr>
						<td  class="'.$ClassFila.'">
						 '.($j+1).'. '.$Pregunta;
						$Propiedades=array("type"=>"hidden","name"=>'CodiItem[]');
						$this->pintarInput("$CodiItem",$Propiedades);
				echo '	</td>
					  </tr>';
				
				echo '<tr>';
				echo '<td  valign="bottom">';
				$this->mostrarOpciones($CodiItem,$CodiSecc,$CodiSino,$Texto,$Valor1,$Valor2,$RangMini,$RangMaxi,$DefeText,$DefeVal2,$DefeVal1,$DefeSiNo,$ClassTabla);
				echo '</td>
					</tr>
					</table>
					</td>';
			
				$i++;
			}
			$j++;
			$CodiSeccOld=$CodiSecc;
		}
		echo '</tr></table>';
	}
	
	/**
	*   mostrarOpciones() Metodo para evaluar las distintas opciones que se muestran en cada pregunta
	*   ENTRADA
	*   $CodiItem = Identificador de la pregunta
	*   $CodiSecc = Identificador de la seccion actual
	*   $CodiSino = Bandera 1, indica si se punta un combobox
	*   $Texto = Bandera 1, indica si se punta un textarea
	*   $Valor1 = Bandera 1, indica si se punta una caja de texto para digitar un numero 
	*   $Valor2 = Bandera 1, indica si se punta una caja de texto para digitar un numero 
	*   $RangMini = Restriccion del Rango minimo que puede tomar el $Valor 1 y 2
	*   $RangMaxi = Restriccion del Rango maximo que puede tomar el $Valor 1 y 2
	*   GLOBAL 
	*   $ConsData  : Consecutivo almacenado en la base de datos, utilizado unicamente para visualizar la informacion
	*   $Impresion :  Bandera que identifica si se esta en modo impresion
	*/
	
 	function mostrarOpciones($CodiItem,$CodiSecc,$CodiSino,$Texto,$Valor1,$Valor2,$RangMini,$RangMaxi,$DefeText,$DefeVal2,$DefeVal1,$DefeSiNo,$ClassTabla)
	{
		global $ConsData,$Impresion;
		if($ConsData!="")
		{
			$Valores=$this->consultarValores($CodiItem,$ConsData);
		}
		else
		{
			$Valores['Texto']=$DefeText;
			$Valores['Valor1']=$DefeVal1;
			$Valores['Valor2']=$DefeVal2;
			$Valores['CodiSino']=$DefeSiNo;	
		}
		
 		echo '<table class="'.$ClassTabla.'" align="center" style="width:100%;font-size:12px">
			  <tr>';
		if($CodiSino==1)
		{
			echo '<td valign="top">';
			/**  SI ES MODO IMPRESION UNICAMEN SE ESCRIBE EN PANTALLA SIN PINTAR COMBOBOX*/
			if($Impresion==1)
			{
				echo  $this->consultarNombre("CodiSino","CodiSino","NombSino",$Valores['CodiSino']);
			}
			else
			{
				$Propiedades=array("name"=>'CmbSino'.$CodiItem,
								   "class"=>"combo",
								   "defecto"=>$DefeSiNo
								   );
 
  				$this->pintarCombo("CodiSino","CodiSino","NombSino",$Valores['CodiSino'],$Propiedades);
			}
			echo '</td>';
		}
		if($Texto==1)
		{
			echo '<td  valign="top">';
			/**  SI ES MODO IMPRESION UNICAMEN SE ESCRIBE EN PANTALLA SIN PINTAR TextArea */
			if($Impresion==1)
			{
				echo $Valores['Texto'];
			}
			else
			{
				$Propiedades=array("name"=>'TxtTexto'.$CodiItem,
								   "cols"=>"40",
								   "rows"=>"3",
								   "class"=>"cajastexton",
								   "defecto"=>$DefeText,
								   "onkeypress"=>"ctrlMe(this,5000)");
 

				$this->pintarTextArea($Valores['Texto'],$Propiedades);
			}
			echo '</td>';
		}
		if($Valor1==1)
		{
			echo '<td valign="top">';
			/**  SI ES MODO IMPRESION UNICAMEN SE ESCRIBE EN PANTALLA SIN PINTAR text*/
			if($Impresion==1)
			{
				echo $Valores['Valor1'];
			}
			else
			{
				$Propiedades=array("type"=>"text",
								   "name"=>'TxtValor1'.$CodiItem,
								   "size"=>"10",
								   "maxlength"=>"13",
								   "class"=>"cajastexton",
								   "onkeypress"=>"numericop(this,2)",
								   "defecto"=>$DefeVal1,
								   "RangMini"=>$RangMini,
								   "RangMaxi"=>$RangMaxi);
 				$this->pintarInput($Valores['Valor1'],$Propiedades);
			}
			echo '</td>';
		}
		if($Valor2==1)
		{
			echo '<td valign="top">';
			/**  SI ES MODO IMPRESION UNICAMEN SE ESCRIBE EN PANTALLA SIN PINTAR text*/
			if($Impresion==1)
			{
				echo $Valores['Valor2'];
			}
			else
			{
				$Propiedades=array("type"=>"text",
								   "name"=>'TxtValor2'.$CodiItem,
								   "size"=>"10",
								   "maxlength"=>"13",
								   "class"=>"cajastexton",
								   "onkeypress"=>"numericop(this,2)",
								   "defecto"=>$DefeVal2,
								   "RangMini"=>$RangMini,
								   "RangMaxi"=>$RangMaxi);
 
				$this->pintarInput($Valores['Valor2'],$Propiedades);
			}
			echo '</td>';
		}
		echo '	</tr>
			  </table>';
	}
	/**
	* consultarValores ():  consulta los valores almacenados Bd, para ser mostrado en los campos por defecto
	*/
	function consultarValores($CodiItem,$ConsData)
	{
		$sql="SELECT CodiSino,Texto,Valor1,Valor2
			  FROM   DetaData
			  WHERE  ConsAdmi='".$this->ConsAdmi."'  AND CodiInst='".$this->CodiInst."' AND ConsData='".$ConsData."' AND CodiItem='".$CodiItem."'";
		$row=query($sql,1);
		return($row);
 	}
}
?>