<?php

function conectarse()

{

$db_host="localhost"; // Host BD al que conectarse, habitualmente es localhost

$db_nombre="abordoec_absystem"; // Nombre de la Base de Datos que se desea utilizar

$db_user="root"; // Nombre del usuario con permisos para acceder a la BD

$db_pass=""; // Contrase帽a del usuario de la BD

// Ahora estamos realizando una conexi贸n y la llamamos $link
$link= mysqli_connect($db_host, $db_user, $db_pass);

// Seleccionamos la base de datos que nos interesa

mysqli_select_db($link, $db_nombre) or die("Error seleccionando la baseee de datos.");

// Retornamos $link  para hacer consultas a la BD.

return $link;

}
//$link=conectarse();

?>