<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();

$idUsuario = (int)($_REQUEST['idUsuario'] ?? 0);

$sql = "UPDATE ADM_USUARIO SET ESTADO = 'A' WHERE IDADM_USUARIO = " . $idUsuario;
$consulta = $conexion->query($sql) or die("Problemas al Activar datos:<br>" . mysqli_error($conexion));

if ($consulta) {
    echo "<script>javascript: alert('Usuario Activado Correctamente!') </script>";
    echo "<Script language='JavaScript'>";
    echo 'self.location = "../PNC_UsuarioCrear.php"';
    echo "</script>";
} else {
    echo 'Failed ' . mysqli_error($conexion);
}
?>
