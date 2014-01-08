<?php
/**CLASE PARA INGRESOS EN LINEA, UTILIZADA EN CONTABILIDAD, RADICACION Y CAJA*/
include_once(SIH_PATH . 'funciones/funciones.php');

class ingresosenlinea{
	public $CodiDocu,$NumeDocu,$CodiInstSedePrin,$AnoInve,$MesInve;
	
	function ingresosenlinea(){
		global $CodiDocu,$NumeDocu,$CodiInstSedePrin,$AnoInve,$MesInve;
		$this->CodiDocu=$CodiDocu;
		$this->NumeDocu=$NumeDocu;
		$this->CodiInstSedePrin=$CodiInstSedePrin;
        $this->AnoInve=$AnoInve;
		$this->MesInve=$MesInve;
	}
	
	/***********************************************
	***************METODOS DE CONSULTA**************
	***********************************************/
	
	/**RETORNA UN ARREGLO CON LOS REGISTROS DE DETACONT PARA EL DOCUMENTO RECIBO/RADICACION */
	function consultaDetaCont(){
		$DocuApliDocu=docuapli($this->CodiDocu);
		if($DocuApliDocu == 83)
			$CRITCont=" AND CodiCont LIKE '14%' AND Valor > 0";	
		else	
			$CRITCont=" AND CodiCont LIKE '14%'";		
				
		$sqlD="SELECT TiDoRefe,NuDoRefe,TiDoTerc,NuDoTerc,CodiCont,Valor
			   FROM   DetaCont
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='".$this->CodiDocu."' AND 
			   		  NumeDocu='".$this->NumeDocu."' $CRITCont";
		$resD=query($sqlD);
		while($rowD=mysql_fetch_array($resD)){
			$TiDoRefe=$rowD['TiDoRefe'];
			$NuDoRefe=$rowD['NuDoRefe'];
			$TiDoTerc=$rowD['TiDoTerc'];
			$NuDoTerc=$rowD['NuDoTerc'];			
			$CodiCont=$rowD['CodiCont'];
			$Valor=$rowD['Valor'];
			
			$ArrayDetaCont[]="$TiDoRefe*$NuDoRefe*$TiDoTerc*$NuDoTerc*$CodiCont*$Valor";
		}
		return $ArrayDetaCont; 
	}	
	
	/**RETORNA UN ARREGLO CON LOS REGISTROS DE DETAPLAN PARA EL DOCUMENTO RECIBO/RADICACION */
	function consultaDetaPlan(){
		$sqlD="SELECT CodiPlan,Valor,CentCost,CodiDest,Concepto
			   FROM   DetaPlan
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
			   		  CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'";
		$resD=query($sqlD);
		while($rowD=mysql_fetch_array($resD)){
			$CodiPlan=$rowD['CodiPlan'];
			$Valor=$rowD['Valor'];
			$CentCost=$rowD['CentCost'];
			$CodiDest=$rowD['CodiDest'];			
			$Concepto=$rowD['Concepto'];
			
			$ArrayDetaPlan[]="$CodiPlan*$Valor*$CentCost*CodiDest*Concepto";
		}
		return $ArrayDetaPlan; 
	}
	
	/**RETORNA EL RUBRO DE DIFICIL COBRO PARAMETRIZADO*/
	function consultaRubrDiCo(){
		$sqlE="SELECT RubrDiCo
			   FROM   CodiInst
			   WHERE  CodiInst='".$this->CodiInstSedePrin."'";
		$resE=query($sqlE);
		$rowE=mysql_fetch_array($resE);
		return $rowE['RubrDiCo'];
	}
	
	/**RETORNA EL RUBRO DE COPAGOS PARAMETRIZADO*/
	function consultaRubrCopa(){
		$sqlE="SELECT RubrCopa
			   FROM   CodiInst
			   WHERE  CodiInst='".$this->CodiInstSedePrin."'";
		$resE=query($sqlE);
		$rowE=mysql_fetch_array($resE);
		return $rowE['RubrCopa'];
	}
				
	/**RETORNA UN ARREGLO CON LOS REGISTROS DE DETAPLAN PARA EL DOCUMENTO RECIBO/RADICACION */
	function consultaTipoUsuaXDetaCont($CoCoRefe,$EsVigeAnte=""){
		if($EsVigeAnte == 1)
			$CampoTipoUsua="CodiPla1";
		else
			$CampoTipoUsua="CodiPlan";
					
		$sqlT="SELECT $CampoTipoUsua
			   FROM   TipoUsua
 			   WHERE  CodiRadi='$CoCoRefe'";	
		$resT=query($sqlT);
		$rowT=mysql_fetch_array($resT);	   
		
		if($rowT[0] == '')
			$RubrCopa=$this->consultaRubrCopa();
		else
			$RubrCopa=$rowT[0];
			
		return $RubrCopa; 
	}	
	
	/**CONSULTA EL ESTADO DEL DOCUMENTO*/
	function consultaCausado(){
		$sql="SELECT Causado
			  FROM   EncaCont
			  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'";
		$res=query($sql);
		$row=mysql_fetch_array($res);	 
		$Caus=$row['Causado'];
		return $Caus;
	}
	
	/**RETORNA UN ARRAY CON LOS RUBROS A AFECTAR Y SU RESPECTIVO VALOR PARA INSERTARLOS EN DETAPLAN*/
	function construyeRubros(){
		$RubrDiCo=$this->consultaRubrDiCo();	
		
		$ArrayDeta=$this->consultaDetaCont();

		if(count($ArrayDeta) > 0){
			foreach($ArrayDeta as $key => $value){
				$DatosArray=explode("*",$value);
				$TiDoRefe=$DatosArray[0];
				$NuDoRefe=$DatosArray[1];
				$CoCoRefe=$DatosArray[4];
				$ValoRefe=$DatosArray[5];
				
				if($ValoRefe < 0)
					$ValoRefe=$ValoRefe*-1;
					
				$sqlE="SELECT FechDocu
					   FROM   EncaCont
					   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='$TiDoRefe' AND NumeDocu='$NuDoRefe'";
				$resE=query($sqlE);
				$rowE=mysql_fetch_array($resE);
				$AnoDocu=substr($rowE['FechDocu'],0,4);
				
				/** SI LA REFERENCIA FV NO ES DE LA VIGENCIA ACTUAL*/
				if($AnoDocu < $this->AnoInve){
					$DiferAno=$this->AnoInve - $AnoDocu;
				
					/** CONSULTO CUAL TIPO DE USUARIO TIENE LA CUENTA CONTABLE DE LA REFERENCIA*/
						$RubrVigeAnte=$this->consultaTipoUsuaXDetaCont($CoCoRefe,1);
					if($DiferAno == 1){/** VIGENCIA ANTERIOR*/
						$ArrayRubros[$RubrVigeAnte]+=$ValoRefe;
					}
					else if($DiferAno > 1){/** DIFICIL COBRO*/
						if($RubrDiCo == '')
							$RubrDiCo=$RubrVigeAnte;
						$ArrayRubros[$RubrDiCo]+=$ValoRefe;
					}
					
				}
				else{
					/** SI LA REFERENCIA FV PERTENECE A LA VIGENCIA ACTUAL*/
					$RubrVigeActu=$this->consultaTipoUsuaXDetaCont($CoCoRefe);
					$ArrayRubros[$RubrVigeActu]+=$ValoRefe;
				}
			}	
			asort($ArrayRubros);
			return $ArrayRubros;
		}
		else
			echo "No existen registros.<br>";
	}
	
	/***********************************************
	*******************CAUSACION********************
	***********************************************/

	/**METODO DE CAUSACION QUE UTILIZA 4 METODOS PARA REALIZAR LAS ACCIONES DE CAUSACION DE INGRESOS EN LINEA*/
	function causaIngresos(){
		$sqlE="SELECT ImpoPres
			   FROM   EncaCont
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'";
		$resE=query($sqlE);
		$rowE=mysql_fetch_array($resE);		
		if($rowE['ImpoPres'] != 1){
			$this->insertaDetaIngr();
			$this->insertaDetaPlan();
			$this->insertaMoviPac();
			$this->causaSaldPres();
			
			query("UPDATE EncaCont
				   SET    ImpoPres=1
				   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'");		
		}
	}

	/**INSERTA EL DETAINGR */
	function insertaDetaIngr(){
		global $Filas,$UsuaDigi;
		
		$ArrayDeta=$this->consultaDetaCont();
		if(count($ArrayDeta) > 0){
			foreach($ArrayDeta as $key => $value){
				$DatosArray=explode("*",$value);
				$TiDoRefe=$DatosArray[0];
				$NuDoRefe=$DatosArray[1];
				$Valor=$DatosArray[5];
				if($Valor < 0)
					$Valor=$Valor*-1;
				
				$sqlDetaIngr="SELECT NumeDocu
							  FROM   DetaIngr
							  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='".$this->CodiDocu."' AND 
							  		 NumeDocu='".$this->NumeDocu."' AND CodiRefe='$TiDoRefe' AND NumeRefe='$NuDoRefe'";
				$resultDetaIngr=query($sqlDetaIngr);
				if($Filas==0){
					$sqlDetaIngr="INSERT INTO 
								  DetaIngr (CodiInst,CodiDocu,NumeDocu,CodiAno,CodiRefe,NumeRefe,
											ValoTota,FechDigi,HoraDigi,UsuaDigi)
								  VALUES   ('".$this->CodiInstSedePrin."','".$this->CodiDocu."','".$this->NumeDocu."','".$this->AnoInve."','$TiDoRefe',
											'$NuDoRefe','$Valor',curdate(),curtime(),'$UsuaDigi')";
					query($sqlDetaIngr);
				}					
			}
		}
	}		
	
	/**INSERTA EL DETAPLAN */
	function insertaDetaPlan(){
		global $Filas,$UsuaDigi;
		
		$ArrayRubros=$this->construyeRubros();
		if(count($ArrayRubros) > 0){
			foreach($ArrayRubros as $CodiPlan => $ValoRubr){			
				$sql="SELECT CodiPlan,NombPlan,TipoRubr,PlanInic,Adicione,Reduccio,Creditos,ContCred,CompAcum
					  FROM   PlanPres
					  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
					  		 CodiPlan='$CodiPlan' AND ManeMovi='1'";
				$result=query($sql);
				if($row=mysql_fetch_array($result)){
					$CodiTipo=$row[2];
					$PlanInic=$row[3];
					$Adicione=$row[4];
					$Reduccio=$row[5];
					$Creditos=$row[6];
					$ContCred=$row[7];
					$CompAcum=$row[8];
				}
				
				$sql="SELECT MAX(ConsDeta),SUM(Valor)
					  FROM   DetaPlan
					  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiDocu='".$this->CodiDocu."' AND 
							 NumeDocu='".$this->NumeDocu."'";
				$result=query($sql);
				$row=mysql_fetch_array($result);
				$ConsDeta=$row[0]+1;
	
				$sql="INSERT INTO
					  DetaPlan (CodiInst,CodiAno,CodiDocu,NumeDocu,ConsDeta,CodiPlan,CentCost,
								Concepto,Valor,SaldPlan,TipoDoRe,NumeDoRe,FechDigi,HoraDigi,UsuaDigi)
					  VALUES   ('".$this->CodiInstSedePrin."','".$this->AnoInve."','".$this->CodiDocu."','".$this->NumeDocu."','$ConsDeta','$CodiPlan','0',
								'$Concepto','$ValoRubr','','','',curdate(),curtime(),'$UsuaDigi')";
				query($sql);
			}
		}
	}
		
	/**INSERTA EL PAC */
	function insertaMoviPac(){
		global $Filas,$UsuaDigi;
		
		$ArrayDeta=$this->consultaDetaPlan();
		if(count($ArrayDeta) > 0){
			foreach($ArrayDeta as $key => $value){
				$DatosArray=explode("*",$value);
				$CodiPlan=$DatosArray[0];
				$Valor=$DatosArray[1];
				$CentCost=$DatosArray[2];
				$CodiDest=$DatosArray[3];
				$Concepto=$DatosArray[4];
				
				$CadenaCompleta=completaCero($this->MesInve);
				
				$ELPac="Pac".$CadenaCompleta;
				
				$sqlM="SELECT MAX(ConsPac) + 1
					   FROM   MoviPac
					   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
					   		  CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'";	
				$rowM=query($sqlM,1);
				$ConsPac=$rowM[0];		
					
				query("INSERT INTO MoviPac(CodiInst,CodiAno,CodiDocu,NumeDocu,ConsPac,CodiPlan,
										  FechDocu,$ELPac,FechDigi,HoraDigi,UsuaDigi)
								   VALUES('".$this->CodiInstSedePrin."','".$this->AnoInve."','".$this->CodiDocu."','".$this->NumeDocu."','$ConsPac','$CodiPlan',
										  '$FecDocu','$Valor',curdate(),curtime(),'$UsuaDigi')");
			}
		}
	}	

	/**causaSaldPres()*/
	function causaSaldPres(){
		global $Filas,$UsuaDigi;
		
		$DocuApliCaus=docuapli($this->CodiDocu);
		if($DocuApliCaus == '83' || $DocuApliCaus == '28' || $DocuApliCaus == '59')
			$CampoSaldPres="MoviReco";
		else
			$CampoSaldPres="MoviAcum";
		
		$ArrayDeta=$this->consultaDetaPlan();
		if(count($ArrayDeta) > 0){
			foreach($ArrayDeta as $key => $value){
				$DatosArray=explode("*",$value);
				$CodiPlan=$DatosArray[0];
				$Valor=$DatosArray[1];
				$CentCost=$DatosArray[2];
				$CodiDest=$DatosArray[3];
				$Concepto=$DatosArray[4];				
	
				$sql="SELECT CodiTipo,PlanInic,Adicione,Reduccio,Creditos,ContCred,CompAcum,TipoRubr
					  FROM   PlanPres
					  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
					  		 CodiPlan='$CodiPlan' AND ManeMovi='1' ";
				$res=query($sql);
				$row=mysql_fetch_array($res);
				$CodiTipo=$row['TipoRubr'];
				$PlanInic=$row['PlanInic'];
				$Adicione=$row['Adicione'];
				$Reduccio=$row['Reduccio'];
				$Creditos=$row['Creditos'];
				$ContCred=$row['ContCred'];
				$CompAcum=$row['CompAcum'];
				$SaldAcum=$PlanInic+$Adicione-$Reduccio+$Creditos-$ContCred;
								
				$sql="UPDATE DetaPlan
					  SET    SaldPlan='$SaldAcum',FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
					  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiDocu='".$this->CodiDocu."' AND 
							 NumeDocu='".$this->NumeDocu."' AND CodiPlan='$CodiPlan' ";
				query($sql);								   
			
				$sql2="SELECT CodiTipo,CodiAux1,CodiAux2,CodiAux3,CodiAux4,CodiAux5,CodiAux6,CodiAux7,CodiAux8
					   FROM   PlanPres
					   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiPlan='$CodiPlan'";
				$result2=query($sql2);
				$row2=mysql_fetch_array($result2);
				for($i=0;$i<=9;$i++){
					if($row2[$i]==""){
						$i++;
						return;
					}
					else
						$CodiPlanTmp=$row2[$i];
					
					$sql="UPDATE PlanPres
						  SET    $CampoSaldPres=$CampoSaldPres+".$Valor.",FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
						  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiPlan='$CodiPlanTmp' ";
					query($sql);
	
					$sqlSaldPres="SELECT CodiPlan 
								  FROM   SaldPres
								  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
								  		 CodiMes='".$this->MesInve."' AND CodiPlan='$CodiPlanTmp' AND CentCost='$CentCost'";
					$resuSaldPres=query($sqlSaldPres) ;
					if($rowSaldPres=mysql_fetch_array($resuSaldPres)){
						$sql="UPDATE SaldPres
							  SET    $CampoSaldPres=($CampoSaldPres+$Valor),FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
							  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
							  		 CodiMes='".$this->MesInve."' AND CodiPlan='$CodiPlanTmp' AND CentCost='$CentCost'";
						query($sql);
					}
					else{
						$sql="INSERT INTO SaldPres(CodiInst,CentCost,CodiCent,CodiAno,CodiMes,CodiPlan,$CampoSaldPres,FechDigi,HoraDigi,UsuaDigi)
							  VALUES ('".$this->CodiInstSedePrin."','$CentCost','$CentroCosto','".$this->AnoInve."','".$this->MesInve."','$CodiPlanTmp','$Valor',curdate(),curtime(),'$UsuaDigi')";
						query($sql);
					}
				} 						  				
			}
		}
	}	
	
	/***********************************************
	*******************ANULACION********************
	***********************************************/
	
	/**METODO DE ANULACION/DESCAUSACION QUE UTILIZA 4 METODOS PARA REALIZAR LAS ACCIONES DE REVERSION DE INGRESOS EN LINEA*/
	function reversaIngresos(){
		global $Filas,$UsuaDigi;
		
		$sqlD="SELECT COUNT(*)
			   FROM   DetaPlan
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
			   		  CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'";
		$resD=query($sqlD);
		if($Filas > 0){
			$this->anulaSaldPres();
			$this->eliminaMoviPac();
			$this->eliminaDetaIngr();
			$this->eliminaDetaPlan();
			
			query("UPDATE EncaCont
				   SET    ImpoPres=0
				   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'");					
		}	   
	}	
	
	/**REVERSION DE CAUSASALDPRES*/
	function anulaSaldPres(){
		global $UsuaDigi,$Filas;
	
		$DocuApliCaus=docuapli($this->CodiDocu);
		if($DocuApliCaus == '83')
			$CampoSaldPres="MoviReco";
		else
			$CampoSaldPres="MoviAcum";
		
		$ArrayDeta=$this->consultaDetaPlan();
		if(count($ArrayDeta) > 0){
			foreach($ArrayDeta as $key => $value){
				$DatosArray=explode("*",$value);
				$CodiPlan=$DatosArray[0];
				$Valor=$DatosArray[1];
				$CentCost=$DatosArray[2];			
			
				$sql2="SELECT CodiTipo,CodiAux1,CodiAux2,CodiAux3,CodiAux4,CodiAux5,CodiAux6,CodiAux7,CodiAux8
					   FROM   PlanPres
					   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiPlan='$CodiPlan'";
				$result2=query($sql2);
				$row2=mysql_fetch_array($result2);
				for($i=0;$i<=9;$i++){
					if($row2[$i]==""){
						$i++;
						return;
					}
					else
						$CodiPlanTmp=$row2[$i];
					
					$sql="UPDATE PlanPres
						  SET    $CampoSaldPres=$CampoSaldPres-".$Valor.",FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
						  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiPlan='$CodiPlanTmp'";
					query($sql);
					
					$sqlSaldPres="SELECT CodiPlan 
								  FROM   SaldPres
								  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
								  		 CodiMes='".$this->MesInve."' AND CodiPlan='$CodiPlanTmp' AND CentCost='$CentCost'";
					$resuSaldPres=query($sqlSaldPres) ;
					if($rowSaldPres=mysql_fetch_array($resuSaldPres)){
						$sql="UPDATE SaldPres
							  SET    $CampoSaldPres=($CampoSaldPres-$Valor),FechModi=curdate(),HoraModi=curtime(),UsuaModi='$UsuaDigi'
							  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
							  		 CodiMes='".$this->MesInve."' AND CodiPlan='$CodiPlanTmp' AND CentCost='$CentCost'";
						query($sql);
					}
				}
			}
		}
	}	
	
	/**ELIMINA EL DETAINGR */
	function eliminaDetaIngr(){
		query("DELETE 
			   FROM   DetaIngr
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
			   		  CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'");	   
	}
	
	/**ELIMINA EL DETAPLAN */
	function eliminaDetaPlan(){
		query("DELETE 
			   FROM   DetaPlan
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
			   		  CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'");	   
	}		
	
	/**ELIMINA EL DETAINGR */
	function eliminaMoviPac(){
		query("DELETE 
			   FROM   MoviPac
			   WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND 
			   		  CodiDocu='".$this->CodiDocu."' AND NumeDocu='".$this->NumeDocu."'");	   
	}	
		
	
	/***********************************************
	*******************PINTAR***********************
	***********************************************/	
			
	/**pintaIngresos()*/
	function pintaIngresos(){
		$Caus=$this->consultaCausado();
		if($Caus == 1){
			$ArrayDeta=$this->consultaDetaPlan();
			$Etiqueta="Rubros Presupuestales";
		}
		else{
			echo "No Causado.";
			exit();

		}

		if(count($ArrayDeta) > 0){
			echo '<br><table align="center" border="0" cellpadding="1" cellspacing="1" class="formulario" width="100%">
				  <tr class="nuevaBarra" style="height:20px;">
				   <td>'.$Etiqueta.'</td>
				   <td>Valor</td>
				  </tr>';
			foreach($ArrayDeta as $key => $value){
				$DatosArray=explode("*",$value);
				$CodiPlan=$DatosArray[0];
				$Valor=$DatosArray[1];
				
				$sql="SELECT NombPlan
					  FROM   PlanPres
					  WHERE  CodiInst='".$this->CodiInstSedePrin."' AND CodiAno='".$this->AnoInve."' AND CodiPlan='$CodiPlan'";
				$res=query($sql);
				$row=mysql_fetch_array($res);
				$NombPlan=$row['NombPlan'];
					  
				echo '<tr class="mytabla2">
					   <td>
					   '.$CodiPlan.' - '.$NombPlan.'
					   </td>
					   <td align="right">
					   '.puntomilpes($Valor).'
					   </td>
					 </tr>';
			}
			echo '</table>';
		}
		else
			echo "No existen registros.<br>";					
	}		
}
?>
