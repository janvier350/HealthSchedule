<?php
/**
 * demo_anonimizar.php
 * Reemplaza los datos personales de pacientes por datos FICTICIOS en la base
 * de datos DEMO, para poder promocionar la app sin exponer información real.
 *
 *  - SÓLO SISTEMA.
 *  - Destructivo e IRREVERSIBLE: sobrescribe nombres, teléfonos, correos,
 *    direcciones, cédulas, notas de consulta, comentarios de cita, contactos
 *    y pólizas/imágenes de seguro.
 *  - Salvaguardas para NO ejecutarlo por error en producción:
 *      1) Muestra el nombre de la base de datos actual.
 *      2) Exige escribir ESE MISMO nombre de base en el formulario.
 *      3) Exige la frase de confirmación y una casilla de "entiendo".
 *      4) (Opcional) $DEMO_DB_LOCK: si lo defines con el nombre de tu base
 *         demo, el script se niega a correr en cualquier otra base.
 *  - Determinista por IDPACIENTE (mismo id → mismos datos ficticios), así que
 *    se puede volver a ejecutar sin problemas.
 *
 * Conserva PESO/TALLA/IMC e historial clínico "neutro" para que la demo luzca
 * real. No modifica usuarios ni doctores (la marca de quien promociona).
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}

// ── OPCIONAL: bloquear a una base demo específica ────────────────────────
// Descomenta y pon el nombre EXACTO de tu base demo para máxima seguridad:
// $DEMO_DB_LOCK = 'nombre_de_tu_base_demo';
$DEMO_DB_LOCK = $DEMO_DB_LOCK ?? '';

$dbActual = $conexion->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];
$dbEsc    = $conexion->real_escape_string($dbActual);
$FRASE_OK = 'ANONIMIZAR DEMO';

$tabExiste = function ($t) use ($conexion, $dbEsc) {
    $t = $conexion->real_escape_string($t);
    return (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='$t'")->fetch_assoc()['c'] > 0;
};
$colExiste = function ($t, $c) use ($conexion, $dbEsc) {
    $t = $conexion->real_escape_string($t); $c = $conexion->real_escape_string($c);
    return (int)$conexion->query("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbEsc' AND TABLE_NAME='$t' AND COLUMN_NAME='$c'")->fetch_assoc()['c'] > 0;
};

$esPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$confDb  = trim($_POST['confirmar_db'] ?? '');
$confFr  = trim($_POST['frase'] ?? '');
$confChk = isset($_POST['entiendo']);

$errores = [];
if ($esPost) {
    if ($DEMO_DB_LOCK !== '' && $dbActual !== $DEMO_DB_LOCK) {
        $errores[] = "Bloqueo activo: este script sólo puede correr en la base «{$DEMO_DB_LOCK}». La base actual es «{$dbActual}».";
    }
    if ($confDb !== $dbActual) $errores[] = 'El nombre de la base escrito no coincide con la base actual.';
    if ($confFr !== $FRASE_OK) $errores[] = 'La frase de confirmación no es correcta.';
    if (!$confChk)             $errores[] = 'Debes marcar la casilla de confirmación.';
}

function pagina($contenidoHtml) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Anonimizar Demo</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="p-4"><div class="container" style="max-width:720px;">' . $contenidoHtml
       . '</div></body></html>';
}

// ── Mostrar formulario si no es POST o si hay errores de confirmación ─────
if (!$esPost || $errores) {
    $alerta = '';
    foreach ($errores as $e) $alerta .= '<div class="alert alert-danger py-2">'.htmlspecialchars($e).'</div>';
    $lock = $DEMO_DB_LOCK !== ''
        ? '<div class="alert alert-info py-2">Bloqueo de base activo: sólo <code>'.htmlspecialchars($DEMO_DB_LOCK).'</code>.</div>'
        : '<div class="alert alert-warning py-2">Sin bloqueo de base fijo. Para máxima seguridad, define <code>$DEMO_DB_LOCK</code> en este archivo con el nombre de tu base demo.</div>';
    pagina(
        '<h4 class="mb-3">Anonimizar base de datos DEMO</h4>'
      . '<div class="alert alert-danger"><strong>¡Cuidado!</strong> Esta acción reemplaza de forma <strong>permanente</strong> los datos '
      . 'personales de los pacientes por datos ficticios. Ejecútala <strong>sólo en la base de la demo</strong>, nunca en producción.</div>'
      . '<p>Base de datos actual: <span class="badge bg-dark fs-6">'.htmlspecialchars($dbActual).'</span></p>'
      . $lock . $alerta
      . '<form method="POST" class="mt-3">'
      . '  <div class="mb-3"><label class="form-label">Escribe el nombre de la base actual para confirmar</label>'
      . '    <input type="text" name="confirmar_db" class="form-control" autocomplete="off" placeholder="'.htmlspecialchars($dbActual).'"></div>'
      . '  <div class="mb-3"><label class="form-label">Escribe la frase: <code>'.$FRASE_OK.'</code></label>'
      . '    <input type="text" name="frase" class="form-control" autocomplete="off"></div>'
      . '  <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="entiendo" id="entiendo">'
      . '    <label class="form-check-label" for="entiendo">Entiendo que esto reemplaza permanentemente los datos de pacientes por ficticios.</label></div>'
      . '  <button class="btn btn-danger" type="submit">Anonimizar ahora</button> '
      . '  <a class="btn btn-link" href="SCH_Calendar.php">Cancelar</a>'
      . '</form>'
    );
    exit;
}

// ── Ejecutar anonimización ───────────────────────────────────────────────
$msgs = [];
$run  = function ($label, $sql) use ($conexion, &$msgs) {
    if ($conexion->query($sql)) $msgs[] = ['OK', "$label — filas: ".$conexion->affected_rows];
    else                         $msgs[] = ['ERR', "$label — error: ".$conexion->error];
};

// 1) AG_PACIENTE: identidad y datos de contacto ficticios (determinista por id)
$set = [];
$set[] = "NOMBRES = ELT(MOD(IDPACIENTE,10)+1,'María','José','Ana','Luis','Carmen','Juan','Lucía','Pedro','Sofía','Miguel')";
$set[] = "APELLIDOS = CONCAT(ELT(MOD(IDPACIENTE,10)+1,'García','Rodríguez','Martínez','López','González','Pérez','Sánchez','Ramírez','Torres','Flores'),' ',ELT(MOD(IDPACIENTE*3+1,8)+1,'Díaz','Cruz','Reyes','Morales','Ortiz','Gómez','Castro','Rivera'))";
if ($colExiste('AG_PACIENTE','CEDULA'))   $set[] = "CEDULA = CONCAT('DEMO-', LPAD(IDPACIENTE,6,'0'))";
if ($colExiste('AG_PACIENTE','TELEFONO')) $set[] = "TELEFONO = CONCAT('(555) ', LPAD(MOD(IDPACIENTE,900)+100,3,'0'),'-',LPAD(MOD(IDPACIENTE*7,9000)+1000,4,'0'))";
if ($colExiste('AG_PACIENTE','EMAIL'))    $set[] = "EMAIL = CONCAT('demo', IDPACIENTE, '@ejemplo.test')";
if ($colExiste('AG_PACIENTE','ADDRESS'))  $set[] = "ADDRESS = CONCAT(MOD(IDPACIENTE*13,9899)+100,' ',ELT(MOD(IDPACIENTE,5)+1,'Main','Oak','Maple','Pine','Elm'),' St')";
if ($colExiste('AG_PACIENTE','CITY'))     $set[] = "CITY = ELT(MOD(IDPACIENTE,6)+1,'Springfield','Riverside','Franklin','Greenville','Bristol','Fairview')";
if ($colExiste('AG_PACIENTE','STATE'))    $set[] = "STATE = ELT(MOD(IDPACIENTE,5)+1,'NY','NJ','CT','PA','FL')";
if ($colExiste('AG_PACIENTE','ZIP'))      $set[] = "ZIP = LPAD(MOD(IDPACIENTE*31,90000)+10000,5,'0')";
if ($colExiste('AG_PACIENTE','NOTES'))    $set[] = "NOTES = ''";
if ($colExiste('AG_PACIENTE','ADDNOTES')) $set[] = "ADDNOTES = ''";
if ($colExiste('AG_PACIENTE','ALERTA'))   $set[] = "ALERTA = ''";
// Fecha de nacimiento: conservar el año (edad realista), quitar día/mes exactos.
if ($colExiste('AG_PACIENTE','FECHANACIMIENTO'))
    $set[] = "FECHANACIMIENTO = CASE WHEN FECHANACIMIENTO IS NULL OR FECHANACIMIENTO='0000-00-00' THEN FECHANACIMIENTO ELSE DATE_FORMAT(FECHANACIMIENTO,'%Y-01-01') END";
$run('AG_PACIENTE (identidad/contacto)', "UPDATE AG_PACIENTE SET ".implode(", ", $set));

// 2) AG_HISTORIAL: nota clínica neutra (conserva peso/talla/imc)
if ($tabExiste('AG_HISTORIAL') && $colExiste('AG_HISTORIAL','CONTENIDO_INFORME')) {
    $notaDemo = "<p><strong>Nota de demostración.</strong> Contenido clínico ficticio para fines de presentación. "
              . "Los datos reales del paciente fueron anonimizados.</p>";
    $notaEsc = $conexion->real_escape_string($notaDemo);
    $run('AG_HISTORIAL (notas)', "UPDATE AG_HISTORIAL SET CONTENIDO_INFORME='$notaEsc'");
}

// 3) AG_CITA: comentarios de cita
if ($tabExiste('AG_CITA') && $colExiste('AG_CITA','COMENTARIO')) {
    $run('AG_CITA (comentarios)', "UPDATE AG_CITA SET COMENTARIO='' WHERE COMENTARIO IS NOT NULL AND COMENTARIO<>''");
}

// 4) paciente_contactos: teléfonos/correos extra
if ($tabExiste('paciente_contactos')) {
    $run('paciente_contactos', "UPDATE paciente_contactos SET valor = CASE WHEN tipo='email' THEN CONCAT('demo',IDPACIENTE,'@ejemplo.test') ELSE '(555) 000-0000' END");
}

// 5) paciente_seguro: pólizas e imágenes de credencial
if ($tabExiste('paciente_seguro')) {
    $sets = [];
    if ($colExiste('paciente_seguro','num_poliza')) $sets[] = "num_poliza = CONCAT('DEMO-', LPAD(id_paciente_seguro,6,'0'))";
    if ($colExiste('paciente_seguro','img_frente'))  $sets[] = "img_frente = NULL";
    if ($colExiste('paciente_seguro','img_reverso')) $sets[] = "img_reverso = NULL";
    if ($sets) $run('paciente_seguro (pólizas/imágenes)', "UPDATE paciente_seguro SET ".implode(", ", $sets));
}

$cls = ['OK'=>'success','ERR'=>'danger'];
$li = '';
foreach ($msgs as $m) $li .= '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
pagina(
    '<h4>Anonimización completada</h4>'
  . '<p class="text-muted">Base: <code>'.htmlspecialchars($dbActual).'</code></p>'
  . '<ul class="list-group mb-3">'.$li.'</ul>'
  . '<p class="text-muted small">Se conservaron peso, talla e IMC y no se modificaron usuarios ni doctores. Puedes volver a ejecutarlo cuando agregues más datos de prueba.</p>'
  . '<a class="btn btn-primary" href="SCH_Calendar.php">Ir al calendario</a>'
);
