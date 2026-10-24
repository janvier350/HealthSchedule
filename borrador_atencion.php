<?php
/**
 * borrador_atencion.php — Autoguardado de la nota de consulta EN EL SERVIDOR.
 * Guarda un borrador por cita, para recuperarlo al volver a abrir la consulta
 * en cualquier dispositivo, incluso tras cerrarse la sesión.
 *   accion=guardar   (POST idCita, informe, idPaciente) -> {ok, ts}
 *   accion=descartar (POST idCita)                      -> {ok}
 * Requiere sesión. La tabla se auto-crea.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol'])) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) { echo json_encode(['ok'=>false,'error'=>'SIN_SESION']); exit; }

@$conexion->query("CREATE TABLE IF NOT EXISTS atencion_borradores (
    id_cita INT NOT NULL PRIMARY KEY,
    id_paciente INT NULL,
    id_usuario INT NULL,
    informe LONGTEXT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$accion = $_REQUEST['accion'] ?? 'guardar';
$idCita = (int)($_POST['idCita'] ?? $_REQUEST['idCita'] ?? 0);
if ($idCita <= 0) { echo json_encode(['ok'=>false,'error'=>'CITA_INVALIDA']); exit; }

if ($accion === 'descartar') {
    if ($st = $conexion->prepare("DELETE FROM atencion_borradores WHERE id_cita=?")) {
        $st->bind_param('i', $idCita); $st->execute(); $st->close();
    }
    echo json_encode(['ok'=>true]); exit;
}

// Guardar / actualizar
$informe = (string)($_POST['informe'] ?? '');
if (trim(strip_tags($informe)) === '') { echo json_encode(['ok'=>true,'vacio'=>true]); exit; } // no guardar vacío
$idPac  = (int)($_POST['idPaciente'] ?? 0);
$idUser = (int)($_SESSION['iduser'] ?? 0);

$st = $conexion->prepare(
    "INSERT INTO atencion_borradores (id_cita, id_paciente, id_usuario, informe)
     VALUES (?,?,?,?)
     ON DUPLICATE KEY UPDATE informe=VALUES(informe), id_usuario=VALUES(id_usuario), fecha=NOW()"
);
if (!$st) { echo json_encode(['ok'=>false,'error'=>'DB']); exit; }
$st->bind_param('iiis', $idCita, $idPac, $idUser, $informe);
$ok = $st->execute(); $st->close();

echo json_encode(['ok'=>(bool)$ok, 'ts'=>time()*1000]);
