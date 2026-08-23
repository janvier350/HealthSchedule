<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();

if (!isset($_SESSION["rol"], $_SESSION["iduser"])) { echo 'SIN_SESION'; exit; }

$idPaciente = (int)($_POST['idPaciente'] ?? 0);
if (!$idPaciente) { echo 'PACIENTE_INVALIDO'; exit; }

// La tabla debe existir (migrar_plan_peso.php)
$dbName = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$tablaExiste = (int)$conexion->query(
    "SELECT COUNT(*) c FROM information_schema.TABLES
     WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='AG_PLAN_PESO'"
)->fetch_assoc()['c'] > 0;
if (!$tablaExiste) { echo 'SIN_TABLA'; exit; }

$idUser   = (int)$_SESSION['iduser'];
$unidades = substr(trim($_POST['unidades'] ?? ''), 0, 10);
$sexo     = ($_POST['sexo'] === '0' || $_POST['sexo'] === '1') ? (int)$_POST['sexo'] : null;
$edad     = (int)($_POST['edad'] ?? 0);
$estatura = (float)($_POST['estaturaM'] ?? 0);
$pesoIni  = (float)($_POST['pesoKg'] ?? 0);
$palIni   = (float)($_POST['pal'] ?? 0);
$pesoMeta = (float)($_POST['metaKg'] ?? 0);
$fechaMeta= trim($_POST['fechaMeta'] ?? '');
$palMeta  = (float)($_POST['palMeta'] ?? 0);
$calMantActual = (int)round($_POST['calMantenerActual'] ?? 0);
$calAlcanzar   = (int)round($_POST['calAlcanzar'] ?? 0);
$calMantMeta   = (int)round($_POST['calMantenerMeta'] ?? 0);

if ($pesoIni <= 0 || $pesoMeta <= 0 || $edad < 18 || $estatura <= 0) {
    echo 'DATOS_INCOMPLETOS';
    exit;
}
$fechaMetaParam = ($fechaMeta === '') ? null : $fechaMeta;

$stmt = $conexion->prepare(
    "INSERT INTO AG_PLAN_PESO
        (IDPACIENTE, IDADM_USUARIO, UNIDADES, SEXO, EDAD, ESTATURA_M, PESO_INICIAL, PAL_INICIAL,
         PESO_META, FECHA_META, PAL_META, CAL_MANTENER_ACTUAL, CAL_ALCANZAR, CAL_MANTENER_META, ESTADO)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'A')"
);
$stmt->bind_param(
    "iisiiddddsdiii",
    $idPaciente, $idUser, $unidades, $sexo, $edad, $estatura, $pesoIni, $palIni,
    $pesoMeta, $fechaMetaParam, $palMeta, $calMantActual, $calAlcanzar, $calMantMeta
);

if ($stmt->execute()) {
    echo 'OK';
} else {
    echo 'ERROR: ' . $stmt->error;
}
$stmt->close();
$conexion->close();
