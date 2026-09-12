<?php
/**
 * factura_pago_guardar.php
 * Registra un pago/abono contra una factura y recalcula pagado + saldo + estado.
 * SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido.');
}

$idFactura = (int)($_POST['id_factura'] ?? 0);
$monto     = (float)($_POST['monto'] ?? 0);
$metodo    = trim($_POST['metodo'] ?? '');
$fecha     = trim($_POST['fecha'] ?? '') ?: date('Y-m-d');
$ref       = trim($_POST['referencia'] ?? '');

$metodosOk = ['Cash','Credit Card','Debit','Check','Electronic Transfer','Adjustment','Insurance'];
if (!in_array($metodo,$metodosOk,true)) $metodo = 'Cash';

if ($idFactura <= 0 || $monto == 0) {
    header("Location: factura_ver.php?id=".$idFactura."&err=1"); exit;
}

// Verificar que la factura existe
$chk = $conexion->prepare("SELECT total FROM facturas WHERE id=? LIMIT 1");
$chk->bind_param('i',$idFactura); $chk->execute();
$f = $chk->get_result()->fetch_assoc(); $chk->close();
if (!$f) { header("Location: cuentas_por_cobrar.php"); exit; }

// Insertar pago
$creadoPor = (int)($_SESSION['iduser'] ?? 0);
$ins = $conexion->prepare("INSERT INTO factura_pagos (id_factura,fecha,monto,metodo,referencia,registrado_por) VALUES (?,?,?,?,?,?)");
$ins->bind_param('isdssi',$idFactura,$fecha,$monto,$metodo,$ref,$creadoPor);
$ins->execute(); $ins->close();

// Recalcular pagado y saldo
$sum = $conexion->query("SELECT COALESCE(SUM(monto),0) p FROM factura_pagos WHERE id_factura=".$idFactura)->fetch_assoc();
$pagado = (float)$sum['p'];
$total  = (float)$f['total'];
$saldo  = $total - $pagado;
$upd = $conexion->prepare("UPDATE facturas SET pagado=?, saldo=? WHERE id=?");
$upd->bind_param('ddi',$pagado,$saldo,$idFactura); $upd->execute(); $upd->close();

header("Location: factura_ver.php?id=".$idFactura."&pago=1");
exit;
