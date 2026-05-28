<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

// Consulta para obtener las citas
$query = "SELECT A.IDCITA, A.IDPACIENTE, CONCAT(B.NOMBRES, ' ', B.APELLIDOS, ' -- TIPO: ', C.NOMBRES, ' -- ESTADO: ', A.ESTADO_CITA) AS PACIENTE,
          A.FECHA_CITA, A.HORA_INICIO, A.HORA_FIN, C.NOMBRES AS TIPO_CONSULTA, A.ESTADO_CITA, A.COMENTARIO 
          FROM AG_CITA A 
          INNER JOIN AG_PACIENTE B ON A.IDPACIENTE = B.IDPACIENTE 
          INNER JOIN AG_TIPOCONSULTA C ON A.IDTIPOCONSULTA = C.IDTIPOCONSULTA";

$resultado = $conexion->query($query);

$eventos = array();
while ($row = $resultado->fetch_assoc()) {
    $eventos[] = array(
        'id' => $row['IDCITA'],
        'title' => $row['PACIENTE'], // Título del evento
        'start' => $row['FECHA_CITA'] . 'T' . $row['HORA_INICIO'], // Fecha y hora de inicio
        'end' => $row['FECHA_CITA'] . 'T' . $row['HORA_FIN'], // Fecha y hora de fin
        'description' => 'Tipo: ' . $row['TIPO_CONSULTA'] . ' - Estado: ' . $row['ESTADO_CITA'] // Descripción
    );
}

// Envía el JSON al frontend
echo json_encode($eventos);
?>