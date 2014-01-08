<?php
/**
*	Clase para el manejo de la liquidacion
*/
include_once(SIH_PATH . 'funciones/funciones.php');

class liquidacion
{
	public $CodiDocu,$NumeFact,$CodiInstSedePrin,$ConsAdmi,$AnoInve,$Modulo;
	/** CONSTRUCTOR*/
	function liquidacion()
	{
		global $CodiDocu,$NumeFact,$CodiInstSedePrin,$ConsAdmi,$AnoInve,$Modulo,$CentroCosto,$CodiInst;
		$this->CodiDocu=$CodiDocu;
		$this->NumeFact=$NumeFact;
		$this->CodiInst=$CodiInstSedePrin;
        $this->ConsAdmi=$ConsAdmi;
        $this->AnoInve=$AnoInve;
		$this->Modulo=$Modulo;
		$this->CentroCosto=$CentroCosto;
		$this->CInst=$CodiInst;
	}
	
	/**consultarAfiliacion() CONSULTA LOS DATOS DE LA AFILICACION DEL PACIENTE EN LA PRESENTE LIQUIDACION
	*  
	*  SALIDA
	*	$Array : 	Arreglo con los datos correspondientes a administradora,contrato,tipo de afliacion,
	*				Tipo de usuario,categoria,Tipo de Atencion(En base a la Admision)
	*/
	function consultarAfiliacion()
	{
		/** SI EL SERVICIO DE EGRESO ES VACIO, LO IGUALO AL CODISERV AL IGUAL QUE LA CAMA*/
		$sqlS="SELECT ServEgre
			   FROM   EncaFact
			   WHERE  CodiInst='".$this->CodiInst."' AND CodiDocu='".$this->CodiDocu."' AND 
					  NumeFact='".$this->NumeFact."'";
		$rowS=query($sqlS,1);
		if($rowS['ServEgre'] == '')
		{
			$sqlU="UPDATE EncaFact
				   SET    ServEgre=CodiServ,CamaActu=CodiCama
				   WHERE  CodiInst='".$this->CodiInst."' AND CodiDocu='".$this->CodiDocu."' AND 
						  NumeFact='".$this->NumeFact."'";
			query($sqlU);
		}			  
		
		$sql="SELECT  e.FechFact,e.CodiAdmi,e.NumeCont,e.TipoUsua,e.TipoAfil,e.CodiEstr,c.TipoAten TipoAten,c.EsObserv,
					  e.ConsCita,e.NumeAuto,e.NumePoli,e.FechIngr,e.HoraIngr,e.ServEgre CodiServ,c.NombServ,e.CamaActu CodiCama,
					  e.ConsAdmi,e.FechEgre,e.HoraEgre
			  FROM    EncaFact e,CodiServ c 
			  WHERE   e.CodiInst='".$this->CodiInst."' AND e.CodiDocu='".$this->CodiDocu."' AND 
					  e.NumeFact='".$this->NumeFact."' AND e.ServEgre=c.CodiServ";
		$result=query($sql);
		$row=mysql_fetch_array($result);
		$Array['FechFact']=$row['FechFact'];
		$Array['CodiAdmi']=$row['CodiAdmi'];
		$Array['NumeCont']=$row['NumeCont'];
		$Array['TipoUsua']=$row['TipoUsua'];
		$Array['TipoAfil']=$row['TipoAfil'];
		$Array['CodiEstr']=$row['CodiEstr'];
		$Array['TipoAten']=$row['TipoAten'];
		$Array['EsObserv']=$row['EsObserv'];
		$Array['ConsCita']=$row['ConsCita'];
		$Array['NumeAuto']=$row['NumeAuto'];
		$Array['NumePoli']=$row['NumePoli'];			
		$Array['FechIngr']=$row['FechIngr'];
		$Array['HoraIngr']=$row['HoraIngr'];
		$Array['CodiServ']=$row['CodiServ'];
		$Array['NombServ']=$row['NombServ'];
		$Array['CodiCama']=$row['CodiCama'];
		$Array['ConsAdmi']=$row['ConsAdmi'];
		$Array['FechEgre']=$row['FechEgre'];
		$Array['HoraEgre']=$row['HoraEgre'];

		return($Array);
	}
	
	/** CONSULTA EL MAXIMO CONSECUTIVO Y LE SUMA 1
	*   ENTRADA 
	*		$CodiDocu : Codigo del Documento
	*		$Tabla	  :
	*		$Numero	  :
	*/
	function consultarConsecutivo($CodiDocu="",$Tabla="EncaFact",$Numero="NumeFact")
	{
		if($CodiDocu=="")
		{
			$CodiDocu=$this->consultarCodiDocu(29);
		}
		
		/** DEPENDIENDO DEL PARAMETRO MONOFINANCIERO, UBICO CODIINST EN EL MAX AL NUMEDOCU*/
		$MonoFinanciero=parametros("MonoFinanciero",2);
		if($MonoFinanciero == 1)
			$CRITINSTITUCION="";	
		else
			$CRITINSTITUCION=" AND CodiInst='".$this->CodiInst."'";
		
		$sql="SELECT Max($Numero)
			  FROM   $Tabla
			  WHERE  CodiDocu='$CodiDocu' $CRITINSTITUCION";
		$row=query($sql,1);
		$NumeFact=$row[0]+1;
		return($NumeFact);
	}
	
	function consultarCodiDocu($DocuApli)
	{
		$sql="SELECT CodiDocu
			  FROM   MaesDocu
			  WHERE  DocuApli='".$DocuApli."'";
			$row=query($sql,1);
			$CodiDocu=$row[0];
		return($CodiDocu);
	}

	function consultarCondUsua()
	{
		$sql="SELECT CondUsua 
			  FROM   EncaFact 
			  WHERE  CodiInst='".$this->CodiInst."' AND CodiDocu='".$this->CodiDocu."' AND 
					 NumeFact='".$this->NumeFact."'";			
		$row=query($sql,1);
		$CondUsua=$row[0];
		return($CondUsua);
	}
	
	/** CREA EL ENCABEZADO DE LA LIQUIDACION CUANDO ES
	*	NECESARIO HACERLO AUTOMATICAMENTE EN BASE EN LA ADMISION
	*	ENTRADA:
	*		$ConsAdmi= Numero de la Admision
	*/
	function crearEncaFact($Consecut,$Tabla="",$CodInsTmp='')
	{
		global $UsuaDigi,$fechaLiquidacion;
		if($CodInsTmp=='')
			$CodIns=$this->CodiInst;
		else
			$CodIns=$CodInsTmp;
		//Si el EncaFact es Creado a partir de la Cita
		if($Tabla=="")
		{
			$sql="SELECT TipoDocu,NumeUsua,CodiAdmi,NumeCont,TipoUsua,CodiEstr,TipoAfil,TipoServ
				  FROM   DetaCita
				  WHERE  Consecut='$Consecut' AND CodiInst='$CodIns'";				
			
			$row=query($sql,1);
			$TipoDocu=$row['TipoDocu'];
			$NumeUsua=$row['NumeUsua'];
			$FechIngr=date("Y-m-d");
			$HoraIngr=date("H:i:s");
			$CodiAdmi=$row['CodiAdmi'];
			$NumeCont=$row['NumeCont'];
			$TipoUsua=$row['TipoUsua'];
			$CodiEstr=$row['CodiEstr'];
			$TipoAfil=$row['TipoAfil'];
			$TipoServ=$row['TipoServ'];
			$GrupoAte='O';
			$CodiServ=$row['CodiServ'];
			$ConsAdmi='';
			$FechSali=date("Y-m-d");
			$HoraSali=date("H:i:s");
			$DestSali='';
			$DiagEgre='';
			$DiagRel1='';
			$DiagRel2='';
			$DiagRel3='';
			$DiagComp='';
			$EstaSali='';
			$DiagMuer='';
			$FechMuer='';
			$HoraMuer='';
			$sql="SELECT GrupoAte
				  FROM Paciente
				  WHERE TipoDocu='$TipoDocu' AND NumeUsua='$NumeUsua'";
			$rowGrupAten=query($sql,1);
			$GrupoAte=$rowGrupAten[0];
			//Consultamos el Servicio del TipoServ
			$sql="SELECT CodiServ
				  FROM TipoServ
				  WHERE CodiTipo='$TipoServ'";
			$rowServ=query($sql,1);
			$CodiServ=$rowServ[0];
			$Servicios="'$CodiServ','$CodiServ','','',";
		}
		else //Si el EncaFact es creado a partir de la Admision
		{
			$sql="SELECT FechIngr,HoraIngr,TipoDocu,NumeUsua,CodiAdmi,NumeCont,TipoUsua,CodiEstr,TipoAfil,GrupoAte,
						 CodiServ,CodiCama,ServEgre,CamaActu,CondUsua
				  FROM   Admision
				  WHERE  ConsAdmi='$Consecut' AND CodiInst='".$this->CInst."'";				
			//debug_ajax(array($sql));
			$row=query($sql,1);
			$TipoDocu=$row['TipoDocu'];
			$NumeUsua=$row['NumeUsua'];
			$FechIngr=$row['FechIngr'];
			$HoraIngr=$row['HoraIngr'];
			$CodiAdmi=$row['CodiAdmi'];
			$NumeCont=$row['NumeCont'];
			$TipoUsua=$row['TipoUsua'];
			$CodiEstr=$row['CodiEstr'];
			$TipoAfil=$row['TipoAfil'];
			$GrupoAte=$row['GrupoAte'];
			$CodiServ=$row['CodiServ'];
			$CodiCama=$row['CodiCama'];
			$ServEgre=$row['ServEgre'];
			$CamaActu=$row['CamaActu'];
			$CondUsua=$row['CondUsua'];
			$ConsAdmi=$Consecut;
			
			//Seleccionamos los Datos del Egreso del Usuario
			$sql="SELECT FechSali,HoraSali,DestSali,DiagEgre,DiagRel1,DiagRel2,DiagRel3,DiagComp,EstaSali,
						 DiagMuer,FechMuer,HoraMuer
				  FROM   SaliInte
				  WHERE  ConsAdmi='$ConsAdmi' AND CodiInst='".$this->CInst."'";
			$resDestSali=query($sql,1);
			$FechSali="$resDestSali[0]";
			$HoraSali="$resDestSali[1]";
			$DestSali="$resDestSali[2]";
			$DiagEgre="$resDestSali[3]";
			$DiagRel1="$resDestSali[4]";
			$DiagRel2="$resDestSali[5]";
			$DiagRel3="$resDestSali[6]";
			$DiagComp="$resDestSali[7]";
			$EstaSali="$resDestSali[8]";
			$DiagMuer="$resDestSali[9]";
			$FechMuer="$resDestSali[10]";
			$HoraMuer="$resDestSali[11]";
			$Servicios="'$CodiServ','$ServEgre','$CodiCama','$CamaActu',";
		}
		
		$sql="SELECT CodiManu,CodiPlan,TipoCont
			  FROM   Contrato
			  WHERE  CodiAdmi='$CodiAdmi' AND NumeCont='$NumeCont' AND CodiInst='".$this->CodiInst."'";
		$row=query($sql,1);
		$CodiManu=$row['CodiManu'];
		$CodiPlan=$row['CodiPlan'];
		$TipoCont=$row['TipoCont'];
		$CodiDocu=$this->consultarCodiDocu(29);
 		$NumeFact=$this->consultarConsecutivo($CodiDocu);
 		## SI NO EXISTE INSERTO 
		
		/**CALCULO LA EDAD DEL PACIENTE PARA INSERTARLA AL ENCAFACT*/
		$sqlEd="SELECT FechNaci
				FROM   Paciente
				WHERE  TipoDocu='$TipoDocu' AND NumeUsua='$NumeUsua'";
		$resEd=query($sqlEd);
		$rowEd=mysql_fetch_array($resEd);
		$FeNac = explode("-",$rowEd[0]);
		$EdadU = calcedad($FeNac[2],$FeNac[1],$FeNac[0],0,0,0,"mes");		
		$EdadU=explode(" ",$EdadU);
		$ValEd=$EdadU[0];
		$UniEd=$EdadU[1];		
		
		if($fechaLiquidacion=='')
			$fechaLiquidacion=date("Y-m-d");
			
		/** VERIFICO QUE EL MODULO QUE VA A ALMACENAR SEA EL CORRECTO*/
		$ModuloReal=$this->Modulo;
		if($ModuloReal != '13' || $ModuloReal != '20')
			$ModuloReal=13;
			
		$sql="INSERT INTO EncaFact(CodiInst,CodiAno,CodiDocu,NumeFact,CodiCent,CodiModu,TipoDocu,NumeUsua,ValoEdad,UnidEdad,CondUsua,
								   FechFact,HoraFact,ConsCita,ConsAdmi,FechEgre,HoraEgre,DestSali,CodiDiag,
								   DiagRel1,DiagRel2,DiagRel3,DiagComp,EstaSali,DiagMuer,FechMuer,HoraMuer,
								   CodiAdmi,NumeCont,TipoUsua,TipoAfil,CodiEstr,CodiManu,CodiPlan,CodiServ,ServEgre,CodiCama,CamaActu,TipoCont,GrupoAte,
								   FechIngr,HoraIngr,FechDigi,HoraDigi,UsuaDigi)
				 VALUES('".$this->CodiInst."','".$this->AnoInve."','$CodiDocu',$NumeFact,'".$this->CentroCosto."','".$ModuloReal."','$TipoDocu','$NumeUsua','$ValEd','$UniEd','$CondUsua',
						'$fechaLiquidacion',curtime(),'$Consecut','$ConsAdmi','$FechSali','$HoraSali','$DestSali','$DiagEgre',
						'$DiagRel1','$DiagRel2','$DiagRel3','$DiagComp','$EstaSali','$DiagMuer','$FechMuer','$HoraMuer',
						'$CodiAdmi','$NumeCont','$TipoUsua','$TipoAfil','$CodiEstr','$CodiManu','$CodiPlan',$Servicios '$TipoCont','$GrupoAte',
						'$FechIngr','$HoraIngr',curdate(),curtime(),'$UsuaDigi')";
		query($sql);
		
		return($NumeFact);
	}
	
	/** CONSULTO LOS DATOS NECESARIOS DEL CONTRATO PARA LAS TARIFAS*/
	function consulContrato($CoIn,$CoAd,$NuCo)
	{
		$sql="SELECT CodiManu,CodiPlan,CodiMaSu
			  FROM   Contrato 
			  WHERE  CodiInst='$CoIn' AND CodiAdmi='$CoAd' and NumeCont='$NuCo'";
		$res=query($sql);
		$row=mysql_fetch_array($res);
		$Array['CodiManu']=$row[0];
		$Array['CodiPlan']=$row[1];
		$Array['CodiMaSu']=$row['CodiMaSu'];
		
		if($row['CodiMaSu'] == '')
			$Array['CodiMaSu']=$row['CodiManu'];
			
		mysql_free_result($res);
		return $Array;	
	}
	
	/** CONSULTO EL VALOR DE LA ACTIVIDAD*/
	function consulValorTarifa($Codi,$Manu,$Plan,$Tabl,$CampoVal,$CampWhere)
	{	
		$sqlVa="SELECT $CampoVal
				FROM   $Tabl 
				WHERE  $CampWhere='$Codi' and CodiManu='$Manu' and CodiPlan='$Plan'";
		$resVa=query($sqlVa);
		$rowVa=mysql_fetch_array($resVa);
		return $ValUni=$rowVa[0];
	}
	
	/** METODO DE CONSULTA DE DATOS DE INSTITUCION, AMINISTRADORA Y CONTRATO PARA AL GENERACION DE RIPS*/
	function consulData($CoIn,$CoAd,$CoPl,$Usua,$CoSu)
	{
		/** SI SE VA A CONSULTAR LA INSTITUCION*/
		if($CoIn != '')
		{
			$sql="SELECT NombInst,TipoIden,NitInsti
				  FROM   CodiInst
				  WHERE  CodiInst='$CoIn'";
			$res=query($sql);
			$row=mysql_fetch_array($res);
			$Array['NombInst']=$row['NombInst'];
			$Array['TipoIden']=$row['TipoIden'];
			$Array['NitInsti']=$row['NitInsti'];
			mysql_free_result($res);
		}
		
		/** SI SE VA A CONSULTAR LA ADMINISTRADORA*/
		if($CoAd != '')
		{
			$sql="SELECT NombAdmi
				  FROM   CodiAdmi
				  WHERE  CodiAdmi='$CoAd'";
			$res=query($sql);
			$row=mysql_fetch_array($res);
			$Array['NombAdmi']=$row['NombAdmi'];
			mysql_free_result($res);				  
		}
		
		/** SI SE VA A CONSULTAR EL SUMINISTRO*/
		if($CoSu != '')
		{
			$sql="SELECT NombSumi,FormFarm,UnidMedi,ConcMedi,CodiRips
				  FROM   CodiSumi
				  WHERE  CodiSumi='$CoSu'";	
			$res=query($sql);
			$row=mysql_fetch_array($res);
			$Array['NombSumi']=$row['NombSumi'];
			$Array['FormFarm']=$row['FormFarm'];
			$Array['UnidMedi']=$row['UnidMedi'];
			$Array['ConcMedi']=$row['ConcMedi'];
			$Array['CodiRips']=$row['CodiRips'];
			mysql_free_result($res);
		}		

		/** SI SE VA A CONSULTAR EL PLAN*/
		if($CoPl != '')
		{
			$sql="SELECT NombPlan
				  FROM   CodiPlan
				  WHERE  CodiPlan='$CoPl'";	
			$res=query($sql);
			$row=mysql_fetch_array($res);
			$Array['NombPlan']=$row['NombPlan'];
			mysql_free_result($res);
		}	
		
		/** SI SE VAN A CONSULTAR LOS DATOS DEL USUARIO*/
		if($Usua != '')
		{
			$Usua=explode(";",$Usua);
			$TiDo=$Usua[0];
			$NuUs=$Usua[1];
			
			$sql="SELECT NombUsua,NombUsu1,Ape1Usua,Ape2Usua,SexoUsua,ResiDepa,ResiMuni,ResiZona
				  FROM   Paciente
				  WHERE  TipoDocu='$TiDo' AND NumeUsua='$NuUs'";
			$res=query($sql);
			$row=mysql_fetch_array($res);
			
			$Array['NombUsua']=$row['NombUsua'];
			$Array['NombUsu1']=$row['NombUsu1'];
			$Array['Ape1Usua']=$row['Ape1Usua'];
			$Array['Ape2Usua']=$row['Ape2Usua'];
			$Array['SexoUsua']=$row['SexoUsua'];
			$Array['ResiDepa']=$row['ResiDepa'];
			$Array['ResiMuni']=$row['ResiMuni'];
			$Array['ResiZona']=$row['ResiZona'];
			mysql_free_result($res);
		}
		/** RETORNO EL ARREGLO CON LOS RESULTADOS*/
 	    return($Array);		
	}
	
	/** FUNCION QUE VALIDA EL INSERT A DETAFACT DESDE CARGAR POR ANULADAS Y CARGOS EN LIQUIDACION
	  * $CodiServ -> SERVICIO $NombServ -> NOMBRE SERVICIO $TipoServ -> TIPO SERVICIO
	  *	$CodiAdmi -> ADMINISTRADORA $NumeCont -> CONTRATO $CodSer -> ACTIVIDAD O SUMINISTRO $TipoDeta -> TIPO DETALLE DETAFACT
	  * $inserta -> VARIABLE QUE ME DICE SI SE INSERTA O NO (1) SI, (0) NO
	  */
	function validaInsertDetaFact($CodiServ,$NombServ,$TipoServ,$CodiAdmi,$NumeCont,$CodSer,$TipoDeta,$inserta)
	{
		global $Filas,$men;
		/**********************SEGMENTO DE CONSULTAS*************************/
		/** CONSULTO EL NOMBRE DE LA ACTIVIDAD O SUMINISTRO*/
		if($TipoDeta == 1 || $TipoDeta == 3)/**PROCEDIMIENTOS*/
		{
			$sql="SELECT NombProc
				  FROM   CodiProc
				  WHERE  CodiProc='$CodSer'";
		}
		else if($TipoDeta == 2)/**SUMINISTROS*/
		{
			$sql="SELECT NombSumi
				  FROM   CodiSumi
				  WHERE  CodiSumi='$CodSer'";			
		}
		$row=query($sql,1);
		$NomSer=substr($row[0],0,38).".";
		
		/** CONSULTO EL NOMBRE DEL TIPO DE SERVICIO PARA SER MOSTRADO EN LOS MENSAJES*/
		$sql="SELECT NombTipo
			  FROM   TipoServ
			  WHERE  CodiTipo='$TipoServ'";
		$row=query($sql,1);
		$NombTipo=$row[0];	
				
		/** APLICAR RESTRICCIONES SOLO PARA TIPOATEN AMBULATORIO*/
		$sql="SELECT CodiServ 
			  FROM   CodiServ 
			  WHERE  CodiServ='$CodiServ' AND TipoAten IN (2,3)";
		$res=query($sql);
		$EsUrgen=$Filas;
		/********************* FIN SEGMENTO DE CONSULTAS***************************/
		
		/***********************SEGMENTO DE VALIDACIONES***************************/
		/** VALIDO QUE SOLO EXISTA UNA CONSULTA*/
		$sql="SELECT CodiServ 
			  FROM   DetaFact
		      WHERE  CodiInst='".$this->CodiInst."' AND CodiDocu='".$this->CodiDocu."' AND 
					 NumeFact='".$this->NumeFact."' AND CodiServ='$CodSer'";
		$res=query($sql);
		if($Filas > 0 && ($TipoServ<6 || ($TipoServ>=35 && $TipoServ<=37)))
		{
			
			$men[]="Solo es permitido Cargar una Consulta del mismo tipo. No se Cargara la Actividad: $CodSer - $NomSer";	
			$inserta=0;
		}
		
		/** VALIDACION DE RELACION ENTRE EL SERVICIO Y TIPO DE SERVICIO*/
		$sql="SELECT TipoServ
			  FROM   ServTipo
			  WHERE  CodiServ='$CodiServ' AND TipoServ='$TipoServ'";
		$res=query($sql);
		if($Filas == 0)
		{			
			$men[]="No se puede Cargar la Actividad o Suministro: $CodSer - $NomSer. El Tipo de Servicio: $NombTipo no tiene relacion con el Servicio de la Liquidacion: $NombServ";
			$inserta=0;	  
		}	

		/**VALIDAMOS QUE EL CONTRATO CUBRA EL TIPO DE SERVICIO - EXCEPTO URGENCIAS*/
		if($inserta != 0)
		{
			if($EsUrgen == 0)
			{  
				$sqlval="SELECT TipoServ 
						 FROM   ContServ 
						 WHERE  CodiAdmi='$CodiAdmi' AND NumeCont='$NumeCont' AND TipoServ='$TipoServ'";
				$resuval=query($sqlval);
				if($Filas == 0)
				{
					$men[]="No se puede Cargar la Actividad o Suministro: $CodSer - $NomSer. El Tipo de Servicio: $NombTipo no esta cubierto en la parametrizacion del contrato.";
					$inserta=0;	 
				}
			}
		}
		/**********************FIN SEGMENTO VALIDACIONES*********************/
		
		/** RETORNAMOS LA VARIABLE QUE INDICA SI SE INSERTA O NO AL DETAFACT*/
		return ($inserta);		
	}/** FIN FUNCION*/
	
	function CalculaIVA($Codi,$tipo,$ValoUnit,$Cant,$ConTota)
	{
		/** SI ES SUMINISTROS*/
		if($tipo == 1)
			$Camp='CodiProc';
		else/** SI ES PROCEDIMIENTOS*/
			$Camp='CodiSumi';		
		
		/** CONSULTO DE LA TABLA, EL TIPOIVA*/
		$sqlIVA="SELECT TipoIVA
			     FROM   $Camp
			     WHERE  $Camp='$Codi'";
		$rowIVA=query($sqlIVA,1);
		$TipoIVA=$rowIVA[0];
			  	
		/** CONSULTO EL VALOR DEL IVA*/
		$sqlIVA="SELECT NombTipo
				 FROM   TipoIVA
				 WHERE  CodiTipo='$TipoIVA'";	
		$rowIVA=query($sqlIVA,1);	
		
		/** SI EL TIPO IVA ES ENTERO, SI NO ES QUE NO APLICA*/
	//	if(is_numeric($rowIVA[0]))
	//		$PorcIVA=$rowIVA[0];
	//	else
		$PorcIVA=0;	
		
		$ValoTota=$ValoUnit*$Cant;/** EL VALOR TOTAL ES IGUAL A EL UNITARIO POR LA CANTIDAD*/
		
		if($ConTota == 1)
			$ValoIVA=round((($PorcIVA/100)*$ValoTota),2);/** CALCULO EL VALOR DEL IVA*/
		else
			$ValoIVA=round((($PorcIVA/100)*$ValoUnit),2);/** CALCULO EL VALOR DEL IVA*/
		
		/** DEFINO UN ARREGLO CON LAS VARIABLES A RETORNAR*/
		$Array['ValoTota']=$ValoTota;
		$Array['ValoIVA']=$ValoIVA;
		$Array['TipoIVA']=$TipoIVA;

		/** RETORNO EL ARREGLO*/
		return ($Array);	
	}
}
?>
