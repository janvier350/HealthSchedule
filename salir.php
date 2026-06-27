<?php
 @session_start(); //Iniciar una nueva sesi�n o reanudar la existente
    session_destroy(); //Destruye la sesi�n
//**** Redireccionar p�gina web *****
header ("Location: index.php"); 
exit();
?>
