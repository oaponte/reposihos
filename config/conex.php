<?php
//include_once("/var/www/html/cabezacomprime.php");
//include_once("/var/www/html/piecomprime.php");

/*
######################################################################################
######################################################################################
#######################  MODIFICADO OSCAR0028  #######################################
######################################################################################
######################################################################################
*/


$dbActualSihos='';
$dbUserSihos='';
$dbAccessSihos='';

/*Conexion a postgres*/
function pgconectar(){
	$link=pg_connect("host=localhost port=5432 user=postgres password='' dbname=reservas");
	if(!$link){
		echo "Error conectando a la base de datos.";
		exit();
	}
	return $link;
}

/*Conexion a MySQL*/
function mysqlconectar($Ubica="localhost",$log="root",$pas="desarrollo2014",$db="sihosdesarrollo"){
	global $dbActualSihos,$dbUserSihos,$dbAccessSihos;
	$dbActualSihos=$db;
	$dbUserSihos=$log;
	$dbAccessSihos=$pas;
	
	$link=mysql_connect($Ubica,$log,$pas);
	if(!$link){
		echo "Error conectando a la base de datos Ubicada en $Ubica .";
		exit();
	}
	if (!mysql_select_db($db,$link)){
		echo "ERROR Seleccionando la base de datos $db.";
		exit();
	}
	return $link;
}

/*Conexion odbc*/
function odbcconectar($nombre){
	$link=odbc_connect($nombre,"","");
	if(!$link){
		echo "Error conectando a la base de datos. $nombre";
		exit();
	}
	return $link;
}

/*Conexion a DBase*/
function dbaseconectar($ruta,$par){
	$link=dbase_open($ruta,$par);
	if(!$link){
		echo "Error conectando a la base de datos.";
		exit();
	}
	return $link;
}
 
/** ESTABLECE LINK DE CONEXION A UNA BASE DE DATOS INFORMIX*/
function informixconectar($BD="finan_cont",$Login="inforweb",$Password="inforweb",$Host="comfaboy2",$Server="comfaboy2"){
	$linkinf = new PDO("informix:host=$Host; service=sqlexec; " .
	   				   "database=$BD; server=$Server; protocol=onsoctcp;" ,
	   				   "$Login", "$Password");
	return($linkinf);
}

/** RETORNA EL RESULTADO SE UNA CONSULTA SQL EN UN ARREGLO */
function query_ifx($sqlstr,$linkinf){
	$sth = $linkinf->prepare($sqlstr);
	if(!$sth)
		echo "Error en ".$sqlstr;
	else{
		$sth->execute();
		$row = $sth->fetchAll();
		return($row);
	}
}
  
/*FUNCION PARA CONECTAR POR MS_SQL SERVER PITALITO
  LA BASE DE DATOS DE PRODUCCION ES DGEMPRES50
  LA BASE DE DATOS DE PRUEBAS ES DGEMPRES30*/
function mssql_conectar($server='10.10.1.1:1433',$user='sa',$pwd='admin',$DB="DGEMPRES50",$newConex=true)
{
	$link_ms=mssql_connect($server,$user,$pwd,$newConex) or die ("No se pudo conectar con SQLSERVER");
	if(!mssql_select_db($DB,$link_ms)){
		echo "ERROR Seleccionando la base de datos $DB. SQL SERVER";
		exit();
	}
	return ($link_ms);
}
?>