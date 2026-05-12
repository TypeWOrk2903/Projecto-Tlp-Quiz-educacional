<?php 
define("CONF_DB_HOST","localhost");
define("CONF_DB_USER","root");
define("CONF_DB_PASS","");
define("CONF_DB_BASE","quiz_tics");

$conectar=new mysqli(CONF_DB_HOST,CONF_DB_USER,CONF_DB_PASS,CONF_DB_BASE);
if($conectar->connect_error){
   die("Conexão perdida");
}