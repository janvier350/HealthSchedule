<?php

function conectarse()

{

$db_host="localhost"; // Host BD al que conectarse, habitualmente es localhost

$db_nombre="srossnut_agenda"; // Nombre de la Base de Datos que se desea utilizar

$db_user="srossnut_agenda"; // Nombre del usuario con permisos para acceder a la BD

$db_pass="b3JsNL25rkq746W77mrn"; // Contrase


//$db_nombre="overcloc_agenda"; // Nombre de la Base de Datos que se desea utilizar

//$db_user="overcloc_agenda"; // Nombre del usuario con permisos para acceder a la BD

//$db_pass="G4Yg_O_d_ejg"; // Contrase



// Ahora estamos realizando una conexión y la llamamos $link

$link= mysqli_connect($db_host, $db_user, $db_pass);

// Seleccionamos la base de datos que nos interesa

mysqli_select_db($link, $db_nombre) or die("Error seleccionando la base de datos.");

// Retornamos $link  para hacer consultas a la BD.

return $link;

}
//$link=conectarse();

?>