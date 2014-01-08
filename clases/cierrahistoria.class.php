<?php
/*
######################################################################################
######################################################################################
#######################  MODIFICADO OSCAR0028  #######################################
######################################################################################
######################################################################################
*/

/*
######################################################################################
######################################################################################
#######################  MODIFICADO OTRAVEZ OSCAR0028  ###############################
######################################################################################
######################################################################################
*/


/**Archivo: cierrahisto.php
*Autor: Deivis Hoyos Bolivar
*Fecha de Creacion: 2009-09-10 11:06am
*Descripcion: Archivo para cerrar la historia clinica del paciente
*en los modulos asistenciales
*
*Fecha de Ultima Modificacion: 2009-09-10 11:06am
*/

/**Clase que cierra la historia clinica del paciente*/
class CerrarHistoria
{
	/**Definimos las variables que manipulara nuestra clase*/
	private $CodiInst;
	private $Modulo;
	private $ConsAdmi;
	private $CodiModu;
	private $UsuaDigi;
	private $Result;
	private $AnoActu;
	private $Errores;
	
	/**Definimos el constructor de nuestra clase*/
	function __construct($CodiInst="",$Modulo="",$ConsAdmi="",$CodiModu="",$UsuaDigi="")
	{
			$this->CodiInst=$CodiInst;
			$this->Modulo=$Modulo;
			$this->ConsAdmi=$ConsAdmi;
			$this->CodiModu=$CodiModu;
			$this->UsuaDigi=$UsuaDigi;
			$this->AnoActu=date("Y");
	}
	/**metodo para consultar si una atencion fue creada a partir de una cita*/
	public function consultaEstaCita()
	{
		global $Filas;
		/**Verificamos si la consulta tiene relacion con una cita*/
		$sql="SELECT ConsCita
			  FROM   RipsCons
			  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi' AND ConsCita<>''";
		$rowEstaHist=query($sql,1);
		if($Filas>0)
		{
			//Actualizamos el estado de la cita
			$sql = "UPDATE DetaCita
					SET    EstaCita=3,FechModi=CURDATE(),HoraModi=CURTIME(),UsuaModi='$this->UsuaDigi'
					WHERE  CodiInst='$this->CodiInst' AND Consecut='$rowEstaHist[ConsCita]'";
			query($sql);
		}
		else
		{
			/**Verificamos si la consulta tiene relacion con una cita*/
			$sql="SELECT ConsCita
			  	  FROM   Admision
			  	  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi' AND ConsCita<>''";
			$rowEstaHist=query($sql,1);
			if($Filas>0)
			{
				//Actualizamos el estado de la cita
				$sql = "UPDATE DetaCita
					    SET    EstaCita=3,FechModi=CURDATE(),HoraModi=CURTIME(),UsuaModi='$this->UsuaDigi'
					    WHERE  CodiInst='$this->CodiInst' AND Consecut='$rowEstaHist[ConsCita]'";
				query($sql);
			}

		}
	}	
	
	/**metodo para realizar proceso del cierre de la historia en un modulo determinado*/
	public function cambiarEstado()
	{
		/**Validamos que los datos minimos requeridos por el metodo no sean vacios*/
		if($this->CodiInst!='' && $this->Modulo!='' && $this->ConsAdmi!='' && $this->CodiModu!='')
		{
			$this->Result = $this->validaDatosModulo();
			if($this->Result!='')
				return $this->Result;
			else
			{
				/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
				global $Filas;
				/**Actualizamos EncaFact y DetaFact si la Admision ya ha sido liquidada*/
				$this->updateEncaDetaFact();
				/**Consultamos si la atencion fue creada a partir de una cita*/
				$this->consultaEstaCita();
				/**Se procede a intentar realizar el cambio de estado de la historia clinica del paciente*/
				$sql="UPDATE Admision 
					  SET    Cerrado=1,FechCier=CURDATE(),HoraCier=CURTIME(),UsuaCier='$this->UsuaDigi',
					  	     FechModi=CURDATE(),HoraModi=CURTIME(),UsuaModi='$this->UsuaDigi'
					  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
				if(query($sql))
					return "Exito";
				else
					return "Ha ocurrido algun error al intentar cerrar la historia electronica, por favor intentelo nuevamente, si el problema persiste contacte al administrador del sistema, Gracias.";
			}
		}
		else
			return "Ha ocurrido algun error al intentar cerrar la historia electronica, por favor intentelo nuevamente, si el problema persiste contacte al administrador del sistema, Gracias.";
	}
	/**Metodo que actualiza EncaFact y DetaFact si la Atencion ha sido liquidada previamente*/
	private function updateEncaDetaFact()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		global $Filas,$CodiInstSedePrin;
		
		//Consultamos si el RipsCons de la Atencion actual tiene relacion con una liquidacion
		$sql = "SELECT TipoDiag,CodiDiag,CodiRel1,CodiRel2,CodiRel3,CodiDocu,NumeLiqu,ConsDeFa
				FROM   RipsCons
				WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
		$resulRips=query($sql);
		while($rowRips=mysql_fetch_array($resulRips))
		{
			//Cambiamos el estado de la consulta del RipsCons a Realizada, unicamente en los
			//modulos asistenciales que no manejan multiconsulta
			$sql="UPDATE RipsCons
				  SET    EstaReal=1
				  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
			query($sql);
			
			//Actualizamos el Detalle de la Liquidacion
			$sql="UPDATE DetaFact
				  SET    TipoDiag='$rowRips[TipoDiag]',CodiDiag='$rowRips[CodiDiag]',CodiRel1='$rowRips[CodiRel1]',
				  	     CodiRel2='$rowRips[CodiRel2]',CodiRel3='$rowRips[CodiRel3]',CantReal=1,FechModi=CURDATE(),
					     HoraModi=CURTIME(),UsuaModi='$this->UsuaDigi'
				  WHERE  CodiInst='$CodiInstSedePrin' AND CodiDocu='$rowRips[CodiDocu]' AND NumeFact='$rowRips[NumeLiqu]' AND 
				  		 ConsDeFa='$rowRips[ConsDeFa]'";
			query($sql);
			
			//Consultamos la causa externa de la Atencion
			$sql = "SELECT CausExte
					FROM   Admision
					WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
			$rowCausAdmi=query($sql,1);
			$CausExteAdmi=$rowCausAdmi[0];
			//Actualizamos la causa externa en EncaFact segun la admision proporcionada
			$sql = "UPDATE EncaFact
					SET    CausExte='$CausExteAdmi'
					WHERE  CodiInst='$CodiInstSedePrin' AND CodiDocu='$rowRips[CodiDocu]' AND NumeFact='$rowRips[NumeLiqu]'";
			query($sql);
		}
		//Consultamos si la Hoja de Procedimientos tiene relacion con cargos facturados de Liquidacion
		$sql = "SELECT TipoDiag,DiagPrin,DiagRela,CodiDocu,NumeLiqu,ConsDeFa,DiagComp
				FROM   HojaProc
				WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
		$resulHojaProc=query($sql);
		while($rowProc=mysql_fetch_array($resulHojaProc))
		{
			//Actualizamos el Detalle de la Liquidacion
			$sql="UPDATE DetaFact
				  SET    TipoDiag='$rowProc[TipoDiag]',CodiDiag='$rowProc[DiagPrin]',CodiRel1='$rowProc[DiagRela]',
				  	     CantReal=1,FechModi=CURDATE(),HoraModi=CURTIME(),UsuaModi='$this->UsuaDigi',DiagComp='$rowProc[DiagComp]'
				  WHERE  CodiInst='$CodiInstSedePrin' AND CodiDocu='$rowProc[CodiDocu]' AND NumeFact='$rowProc[NumeLiqu]' AND 
				  		 ConsDeFa='$rowProc[ConsDeFa]'";
			query($sql);
		}
	}
	
	/**Metodo para validar los datos obligatorios que deben encontrarse registrados en la Atencion del Usuario*/
	private function validaDatosModulo()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		global $Filas;
		$this->Errores='';
		//Validamos que la historia clinica no haya sido cerrada aun
		$CierreHisto=$this->validaCerrado();
		if($CierreHisto==1)
			$this->Errores.="Cerrada";
		else
		{
			//Si el modulo es Consulta General
			if($this->Modulo==5)
			{
				$this->validaRipsCons();
				$this->validaSignVita();
			}
			else if($this->Modulo==6) //Si el modulo es Urgencias
			{
				$this->validaTriage();
				$sql="SELECT ConsAdmi,ClasTria
					  FROM   Triage
					  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
				$rowValiTri=query($sql,1);
				if($rowValiTri[1]<2)
				{
					$this->validaRipsCons();
					$this->validaSignVita();
				}
				$this->validaMultiConsulta();				
			}
			else if($this->Modulo==8) //Si el modulo es Internacion
			{
				//Consultamos el parametro HISTCLIN para ver si obligamos al cerrar la historia que la consulta
				//tenga RipsCons completo
				$HistClin=parametros("HistClin",2);
				//Si Existe un Acto Quirurgico no Obligamos a Completar Anamnesis
				$sql="SELECT ConsAdmi
					  FROM   ActoQuir
					  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
				query($sql,1);
				if($HistClin!='2')
				{
					if($Filas==0)
						$this->validaRipsCons();
					$this->validaSignVita();
					$this->validaMultiConsulta();
				}
			}
			/*Validacion para los medicamentos NoPos que se han preescrito y no tienen diligenciado el formulario NoPos*/
			$ValoParaNoPos = parametros('ManeNPos',2);
			if($ValoParaNoPos==1)
				$this->validaNOPOS();
			//Validamos si el servicio de Egreso es hospitalizacion o Urgencias Observacion para verificar si ha sido
			//completada la salida de internacion			
			$this->validaSaliInte();
			//Validamos que desde cualquier modulo no se pueda cerrar la admision si esta posee un ripscons y no se le ha completado el diagnostico
			//principal
			$sql="SELECT ConsAdmi,CodiDiag,ConsCons
			  	  FROM   RipsCons
			  	  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";	
			$resulRipsCons=query($sql);
			if($Filas>0)
			{
				while($rowRipsCons=mysql_fetch_array($resulRipsCons))
				{
					//Si el Diagnostico principal no ha sido completado
					if($rowRipsCons[1]=='')
						$this->Errores.="<li>El Diagnostico principal de la Atencion[$rowRipsCons[ConsCons]] debe ser completado.</li>";
				}
				$this->validaMultiConsulta();
			}
		}
		return $this->Errores;
	}
	/**metodo para validacion de formulacion de medicamentos NO POS desde el Area Observacion Internacion*/
	private function validaNOPOS()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		 global $Filas;
		 /** buscamos los encapres de la admision para determinar si existen medicamentos del NOPOS
		     que no se han registrado en el formulario NOPOS*/
		 $error = 0;	 
		 $sqlEncaPres= "SELECT  ConsPres 
		          		  FROM  EncaPres
				         WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
		 $resuEncaPres = query($sqlEncaPres);
		 while ($rowEncaPres = mysql_fetch_array($resuEncaPres))
		 {   
		     /** SELECCIONAMOS TODOS LOS DETAPRES */
			 $sqlDetaPres = "SELECT   N.Consecut AS Consecut, D.CodiSumi AS CodiSumi,C.NombSumi AS NombSumi
							   FROM   DetaPres D LEFT JOIN NoPos N On(D.CodiInst = N.CodiInst AND D.ConsAdmi = N.ConsAdmi AND D.ConsPres = N.ConsPres AND D.Item = N.Item),
									  CodiSumi C
							   WHERE  D.CodiSumi=C.CodiSumi AND D.ConsAdmi='$this->ConsAdmi' AND D.CodiInst='$this->CodiInst' AND 
							          D.ConsPres='$rowEncaPres[ConsPres]' AND C.TipoServ = 29 AND D.UsuaSusp = ''
							ORDER BY  D.Item";
			 $resuDetaPres = query($sqlDetaPres);			 
			 /** SELECCIONAMOS TODOS LOS SUMINISTROS DE EL DETAPRES*/	  
			 while($rowDetaPres = mysql_fetch_array($resuDetaPres))			 
			 {
				 if($rowDetaPres['Consecut']==NULL)
				 {
				 	$error ++;
					$ErroSumi[$rowDetaPres['CodiSumi']]=$rowDetaPres['NombSumi'];
				 }				 				 				 
			 } /** CIERRA EL WHILE DE DETAPRES*/					  
		 } /** CIERRA EL WHILE DE ENCAPRES */	
		 /** MOSTRAMOS LOS ERRORES*/
		 if ($error >= 1)
		 {
			 $this->Errores.="<li>Los siguientes medicamentos deben ser registrados en el NOPOS:</li>";
			 if(!empty($ErroSumi))
			 {
		 		  foreach ($ErroSumi as $keyErroSumi=>$NombSumi)
 				  {  
				  	 $this->Errores.= "<div style=\"padding-left:17px;\">".Recorte($NombSumi,25,2)." (".$keyErroSumi.")</div>";
		  	   		 next($ErroSumi);
			  	  }
			 }				 			 		 
		 }
	}/** CIERRA FUNCION validaNOPOS() */
	
	/**metodo para validacion de cierre de historia previo*/
	private function validaCerrado()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		global $Filas;
		/**Consultamos el estado de apertura de la atencion en Admision*/
		$sql="SELECT ConsAdmi
			  FROM   Admision
			  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi' AND Cerrado=1";
		$rowEstaHist=query($sql,1);
		if($Filas>0)
			return 1;
		else
			return 0;
	}
	
	/**Metodo para validacion de RipsCons*/
	private function validaRipsCons()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		global $Filas;
		/*VARIABLE PARA DETERMINAR SI SE EVALUAR QUE EL PACIETE TENGA UNA CONSULTA EN LA ADMISION*/
		$Valida=1;
		/*SI SE ENCUENTRA EN EL MODULO DE INTERNACION*/
		if($this->Modulo==8)
		{
			$sqlAdmi="SELECT  ValoEdad,UnidEdad,TipoDocu,Numeusua
		           		FROM  Admision
				   	   WHERE  ConsAdmi='$this->ConsAdmi'";
			$ResuAdmi=query($sqlAdmi);
			$rowAdmi=mysql_fetch_array($ResuAdmi);
			$UnidEdadAdmi=$rowAdmi['UnidEdad'];
			$ValoEdadAdmi=$rowAdmi['ValoEdad'];		   			
			/*SI EL PACIENTE EN SU PRIMERA ADMISION ES MENOR DE UN MES QUIERE DECIR QUE ES UN RECIEN NACIDO*/
			if (($UnidEdadAdmi=="D" && $ValoEdadAdmi<="30") or ($UnidEdadAdmi=="M" && $ValoEdadAdmi<="1"))
			{
				$TipoDocuAdmi=$rowAdmi['TipoDocu'];
				$NumeUsuaAdmi=$rowAdmi['NumeUsua'];		   
				/* AHORA SE BUSCAN CUANTAS ADMISIONES TIENE EL PACIENTE*/
				$sqlCantAdmi="SELECT  COUNT(*)
								FROM  Admision
							   WHERE  TipoDocu='$TipoDocuAdmi' AND NumeUsua='$NumeUsuaAdmi'";
				$resuCantAdmi=query($sqlCantAdmi,1);
				if($Filas==1)			
					$Valida=0;			
			}
		}
		if ($Valida=="1")
		{	
			$sql="SELECT ConsAdmi,CodiDiag,ConsCons
				  FROM   RipsCons
				  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";	
			$resulRipsCons=query($sql);
			if($Filas==0)
			{
				$this->Errores.="<li>La Amnanesis del Usuario se encuentra incompleta.</li>";
				$this->Errores.="<li>El Diagnostico principal de la Atencion debe ser completado.</li>";
			}
			else
			{
				while($rowRipsCons=mysql_fetch_array($resulRipsCons))
				{
					//Si el Diagnostico principal no ha sido completado
					if($rowRipsCons[1]=='')
						$this->Errores.="<li>El Diagnostico principal de la Atencion[$rowRipsCons[ConsCons]] debe ser completado.</li>";
				}
			}
		}
	}
	/**Metodo para validacion de Signos Vitales*/
	private function validaSignVita()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		global $Filas;
		//Signos Vitales segun parametro
		$sql="SELECT ValoPara
			  FROM   Parametr
			  WHERE  CodiInst='$this->CodiInst' AND NombPara='ObliSign'";
		$paraSign=query($sql,1);
		if($paraSign[0]==1)
		{
			$sql="SELECT ConsAdmi,ConsCons
				  FROM   SignVita
				  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi' AND CodiModu='$this->CodiModu'";
			query($sql);
			if($Filas==0)
				$this->Errores.="<li>Los Signos Vitales del Usuario se encuentran incompletos.</li>";
		}
	}
	/**Metodo para validacion de Salida de Internacion*/
	private function validaSaliInte()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		 global $Filas;
		//Consultamos el servicio de egreso de la Atencion
		$sql = "SELECT ServEgre
				FROM   Admision
				WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
		$rowServicio=query($sql,1);
		$ServEgre=$rowServicio[0];
		$sql = "SELECT TipoAten,EsObserv
				FROM   CodiServ
				WHERE  CodiServ='$ServEgre'";
		$rowTipoAten=query($sql,1);
		$TipoAtencion=$rowTipoAten['TipoAten'];
		$EsObservacion=$rowTipoAten['EsObserv'];
		//Si el tipo de Atencion es hospitalizacion o urgencias observacion, obligamos a completar la salida de internacion
		if($TipoAtencion==2 || ($TipoAtencion==3 && $EsObservacion==1))
		{
			$sql="SELECT ConsAdmi
				  FROM   SaliInte
				  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
			query($sql);
			if($Filas==0)
				$this->Errores.="<li>El Egreso no ha sido completado.</li>";
		}
	}
	/**Metodo para validacion del Examen de Optometria*/
	private function validaOptometr()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		 global $Filas;
		$sql="SELECT ConsAdmi
			  FROM   Optometr
			  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
		query($sql,1);
		if($Filas==0)
			$this->Errores.="<li>Debe completar el examen de Optometria.</li>";
	}
	/**Metodo para validar el Triage de Urgencias*/
	private function validaTriage()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		 global $Filas;
		$sql="SELECT ConsAdmi
			  FROM   Triage
			  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi'";
		query($sql,1);
		if($Filas==0)
			$this->Errores.="<li>Debe completar el Triage de la Atencion.</li>";
	}
	/**Metodo que verifica que todas las multiconsultas se encuentren cerradas*/
	private function validaMultiConsulta()
	{
		/**Invocamos la variable Filas como global para poder trabajar con su resultado*/
		global $Filas;
		$sql="SELECT ConsAdmi
			  FROM   RipsCons
			  WHERE  CodiInst='$this->CodiInst' AND ConsAdmi='$this->ConsAdmi' AND EstaReal=0 AND CodiModu NOT IN(5,9)";
		query($sql);
		if($Filas>0)
			$this->Errores.="<li>Debe cerrar todas las consultas de la Atencion.</li>";
	}
}
?>