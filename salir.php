<?php
// Cierre de sesión. Registra el cierre en la bitácora antes de destruir la sesión.
@session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once("class/auditoria.php");

if (isset($_SESSION['iduser'])) {
    $conexion = conectarse();
    if ($conexion) {
        @$conexion->set_charset('utf8mb4');
        auditar($conexion, 'Sesión', 'logout', 'ADM_USUARIO', $_SESSION['iduser'], 'Cerró sesión');
    }
}

session_destroy();           // Destruye la sesión
header("Location: index.php");
exit();
