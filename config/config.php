<?php
/**
 * config.php
 *
 * Copyright (c) 2004-2005 Sinergia
 *
 * Archivo de Configuracion del Sihos
 * 
 */

global $version;

/** Archivo necesario para la conexion a las bases de datos **/
 include_once('conex.php');
//session_regenerate_id(true);
/** Link directo con la base de datos **/
 $link=mysqlconectar();
/**Obligamos al Navegador que no Olvide el Dominio donde se encuentra*/
 header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM" ');
 $config['sess_match_useragent'] = FALSE;
/** Verificamos los datos de la Institucion Activa o seleccionada al Logearse
    pero primero debemos saber si una sesion ha sido inicializada o no o de lo
    contrario la inicializamos **/
 $sessid = session_id();
 if(empty($sessid))
 {
    session_start();
 }

 if(sqsession_is_registered('CodiInst'))
 {
  sqgetGlobalVar('CodiInst', $CodiInst, SIH_SESSION);
  $sqlstr="select CodiInst,NombInst,NitInsti,DireInst,TeleInst,RutaLogo,MailInst,CeduGere,NombGere
           from CodiInst
           where CodiInst='$CodiInst'";
 }
 else
 {
  $sqlstr="SELECT CodiInst,NombInst,NitInsti,DireInst,TeleInst,RutaLogo,MailInst,CeduGere,NombGere
           FROM   CodiInst 
           WHERE  Activa=1";
 }
 $result=mysql_query($sqlstr,$link);
 if($row=mysql_fetch_array($result))
 {
  $org_codi      = $row[0];
  $org_name      = $row[1];
  $org_nit       = $row[2];
  $org_dir       = $row[3];
  $org_tel       = $row[4];
  $org_logo      = $row[5];
  $org_mail      = $row[6];
  $org_cedgere   = $row[7];
  $org_nombgere  = $row[8];

  //$org_logo      = 'http://192.168.2.1/sihos/images/'.$org_codi.'/'.$row[5];
  $org_title     = "SIHOS $version";
 }
 mysql_free_result($result);
 mysql_close($link);

 $men_ini = "Bienvenidos al Sistema de Informaci&oacute;n Integrado para IPS SIHOS";

 $sihos_default_language = 'es_ES';

 $default_charset        = 'iso-8859-1';

 $force_username_lowercase = false;

 $use_sihos_fox          = false;

 $theme_css = '';
 $theme_img = '';
 $theme_default = 0;
 $theme[0]['PATH'] = SIH_PATH . 'temas/default/index.css';
 $theme[0]['NAME'] = 'Default';
 $theme[1]['PATH'] = SIH_PATH . 'temas/7303001018/index.css';
 $theme[1]['NAME'] = 'Ambalema';
 $session_name = 'SIHSESSID';
 function ObtenerNavegador($user_agent)
 {   
        $navegadores = array(   
            'Opera' => 'Opera',   
            'Mozilla Firefox'=> '(Firebird)|(Firefox)',   
            'Galeon' => 'Galeon',   
            'Mozilla'=>'Gecko',   
            'MyIE'=>'MyIE',   
            'Lynx' => 'Lynx',   
            'Netscape' => '(Mozilla/4\.75)|(Netscape6)|(Mozilla/4\.08)|(Mozilla/4\.5)|(Mozilla/4\.6)|(Mozilla/4\.79)',   
            'Konqueror'=>'Konqueror',   
            'Internet Explorer 8' => '(MSIE 8\.[0-9]+)',              
            'Internet Explorer 7' => '(MSIE 7\.[0-9]+)',   
            'Internet Explorer 6' => '(MSIE 6\.[0-9]+)',   
            'Internet Explorer 5' => '(MSIE 5\.[0-9]+)',   
            'Internet Explorer 4' => '(MSIE 4\.[0-9]+)',   
 	  );   
	 foreach($navegadores as $navegador=>$pattern)
	 {
		if (eregi($pattern, $user_agent))
			return $navegador;   
	 }   
	 return '';   
}
//$_SERVER['HTTP_USER_AGENT']="(MSIE 7\.[0-9]+)";
//$nav = ObtenerNavegador($_SERVER['HTTP_USER_AGENT']); 
//echo $nav;
?>