<?php
require_once("funciones.php");
require_once("conexionBD.php");
$conexion = conectarse();
session_start();



$id = isset($_POST['id']) ? $_POST['id'] : null;
$estado = isset($_POST['estado']) ? $_POST['estado'] : null;

if ($id === null || $estado === null) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos",
        "id_recibido" => $id,
        "estado_recibido" => $estado
    ]);
    exit();
}

// Verifica si el ID existe en la base de datos
$verificar = $conexion->prepare("SELECT * FROM AG_CITA WHERE IDCITA = ?");
$verificar->bind_param("i", $id);
$verificar->execute();
$resultado = $verificar->get_result();

if ($resultado->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró la cita con ID proporcionado",
        "id_recibido" => $id
    ]);
    exit();
}

// Intentar actualizar el estado de la cita
$stmt = $conexion->prepare("UPDATE AG_CITA SET ESTADO_CITA = ? WHERE IDCITA = ?");
$stmt->bind_param("si", $estado, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Estado actualizado correctamente",
        "id_recibido" => $id,
        "estado_recibido" => $estado
    ]);
    echo 'Event data inserted successfully.. Event ID: '.$conexion->insert_id;
            echo "<script>javascript: alert('Datos Creados Correctamente!') </script>";    
            echo "<Script language='JavaScript'>";
            echo 'self.location = "../SCH_Calendar.php"';
            echo"</script>"; 
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error en la consulta SQL",
        "error" => $stmt->error // Mostrar el error de la consulta
    ]);
}

$stmt->close();
$conexion->close();
?>

