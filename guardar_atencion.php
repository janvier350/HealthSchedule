<?php
// En guardar_atencion.php
$idCita = $_POST['idCita'];
$informe = $_POST['informe'];
$peso = $_POST['peso'];
$talla = $_POST['talla'];
$imc = $_POST['imc'];

// Iniciamos transacción para asegurar que ambos cambios ocurran
$conexion->begin_transaction();

try {
    // 1. Guardar el informe clínico
    $stmt = $conexion->prepare("INSERT INTO AG_HISTORIAL (IDCITA, CONTENIDO_INFORME, PESO, TALLA, IMC) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isddd", $idCita, $informe, $peso, $talla, $imc);
    $stmt->execute();

    // 2. Marcar la cita como Atendida (cambiará de color en el calendario)
    $stmt2 = $conexion->prepare("UPDATE AG_CITA SET ESTADO_CITA = 'Atendido' WHERE IDCITA = ?");
    $stmt2->bind_param("i", $idCita);
    $stmt2->execute();

    $conexion->commit();
    echo "OK";
} catch (Exception $e) {
    $conexion->rollback();
    echo "Error: " . $e->getMessage();
}
?>