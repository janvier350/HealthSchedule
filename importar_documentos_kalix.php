<?php
/**
 * importar_documentos_kalix.php — Importa masivamente los PDF de Kalix y los
 * asocia a cada paciente, usando el índice CSV (Indice,Fecha,Paciente,Titulo,
 * Doc_ID,Archivo,Ruta). Empata por nombre "Apellidos, Nombres".
 *
 * Flujo:
 *   1) Sube los PDF al servidor en una carpeta base (p. ej. documentos_kalix_import/)
 *      con las subcarpetas por paciente, y el CSV.
 *   2) Ejecuta en modo VISTA PREVIA: revisa coincidencias, ambiguos y no encontrados.
 *   3) Ejecuta en modo IMPORTAR (por lotes) para copiar y registrar los archivos.
 *
 * SISTEMA-only. Idempotente (no re-importa un mismo archivo del mismo paciente).
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/auditoria.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
@set_time_limit(0);

$DESTINO = __DIR__ . '/documentos_pacientes';

// ── Normalización de nombres para empatar ───────────────────────────────
function _norm($s) {
    $s = (string)$s;
    $s = strtr($s, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n',
    ]);
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
    return preg_replace('/\s+/', ' ', trim($s));
}
function _sinIniciales($norm) {
    $parts = array_filter(explode(' ', $norm), function ($p) { return strlen($p) > 1; });
    return implode(' ', $parts);
}

// ── Índice de pacientes activos (varias formas del nombre → ids) ─────────
function construirIndicePacientes($conexion) {
    $idx = [];
    $add = function ($clave, $id) use (&$idx) {
        if ($clave === '') return;
        if (!isset($idx[$clave])) $idx[$clave] = [];
        $idx[$clave][$id] = true;
    };
    $rs = $conexion->query("SELECT IDPACIENTE, NOMBRES, APELLIDOS FROM AG_PACIENTE WHERE ESTADO='A'");
    if ($rs) while ($p = $rs->fetch_assoc()) {
        $id = (int)$p['IDPACIENTE'];
        $ap = _norm($p['APELLIDOS']); $no = _norm($p['NOMBRES']);
        $f1 = trim($ap.' '.$no); $f2 = trim($no.' '.$ap);
        $add($f1, $id); $add($f2, $id);
        $add(_sinIniciales($f1), $id); $add(_sinIniciales($f2), $id);
    }
    return $idx;
}

// Devuelve ['estado'=>'match|ambiguo|nada', 'id'=>int|null, 'ids'=>[...]]
function empatarPaciente($paciente, $idx) {
    // CSV: "Apellidos, Nombres"
    $ap = ''; $no = '';
    if (strpos($paciente, ',') !== false) {
        list($ap, $no) = array_map('trim', explode(',', $paciente, 2));
    } else { $no = trim($paciente); }
    $candidatos = [
        _norm($ap.' '.$no), _norm($no.' '.$ap),
    ];
    $candidatos[] = _sinIniciales($candidatos[0]);
    $candidatos[] = _sinIniciales($candidatos[1]);
    foreach ($candidatos as $c) {
        if ($c !== '' && isset($idx[$c])) {
            $ids = array_keys($idx[$c]);
            if (count($ids) === 1) return ['estado'=>'match','id'=>$ids[0],'ids'=>$ids];
            return ['estado'=>'ambiguo','id'=>null,'ids'=>$ids];
        }
    }
    return ['estado'=>'nada','id'=>null,'ids'=>[]];
}

// ── Lectura del CSV ──────────────────────────────────────────────────────
function leerCsv($rutaCsv) {
    $filas = [];
    if (!is_file($rutaCsv)) return [null, 'No se encontró el CSV en: '.$rutaCsv];
    $fh = fopen($rutaCsv, 'r');
    if (!$fh) return [null, 'No se pudo abrir el CSV.'];
    $primera = true;
    while (($r = fgetcsv($fh)) !== false) {
        if ($primera) { $primera = false; continue; } // encabezado
        if (count($r) < 6) continue;
        // Indice,Fecha,Paciente,Titulo,Doc_ID,Archivo,Ruta
        $filas[] = [
            'fecha'    => trim($r[1] ?? ''),
            'paciente' => trim($r[2] ?? ''),
            'titulo'   => trim($r[3] ?? ''),
            'docid'    => trim($r[4] ?? ''),
            'archivo'  => trim($r[5] ?? ''),
        ];
    }
    fclose($fh);
    return [$filas, null];
}

$esPost  = ($_SERVER['REQUEST_METHOD'] === 'POST');
$baseDir = trim($_POST['baseDir'] ?? (__DIR__ . '/documentos_kalix_import'));
$csvPath = trim($_POST['csvPath'] ?? ($baseDir . '/_Indice_General_Documentos.csv'));
$modo    = ($_POST['modo'] ?? 'preview') === 'importar' ? 'importar' : 'preview';
$offset  = max(0, (int)($_POST['offset'] ?? 0));
$limite  = min(2000, max(50, (int)($_POST['limite'] ?? 500)));

function pagina($html) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Importar documentos Kalix</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="p-4"><div class="container" style="max-width:900px;">' . $html . '</div></body></html>';
}

function formulario($baseDir, $csvPath, $limite) {
    return '<h4>Importar documentos de Kalix</h4>'
      . '<p class="text-muted">Asocia los PDF a cada paciente usando el índice CSV. Primero usa <b>Vista previa</b>; luego <b>Importar</b> por lotes.</p>'
      . '<form method="POST" class="card card-body shadow-sm">'
      . '  <div class="mb-2"><label class="form-label">Carpeta base (con subcarpetas por paciente)</label>'
      . '    <input type="text" name="baseDir" class="form-control" value="'.htmlspecialchars($baseDir).'"></div>'
      . '  <div class="mb-2"><label class="form-label">Ruta del CSV</label>'
      . '    <input type="text" name="csvPath" class="form-control" value="'.htmlspecialchars($csvPath).'"></div>'
      . '  <div class="row g-2 mb-2">'
      . '    <div class="col"><label class="form-label">Desde (offset)</label><input type="number" name="offset" class="form-control" value="0" min="0"></div>'
      . '    <div class="col"><label class="form-label">Lote (máx 2000)</label><input type="number" name="limite" class="form-control" value="'.(int)$limite.'" min="50" max="2000"></div>'
      . '  </div>'
      . '  <div class="d-flex gap-2">'
      . '    <button class="btn btn-secondary" name="modo" value="preview" type="submit">Vista previa</button>'
      . '    <button class="btn btn-danger" name="modo" value="importar" type="submit" onclick="return confirm(\'¿Importar este lote? Copiará los archivos y los registrará.\');">Importar lote</button>'
      . '  </div>'
      . '</form>';
}

if (!$esPost) { pagina(formulario($baseDir, $csvPath, $limite)); exit; }

list($filas, $err) = leerCsv($csvPath);
if ($err) { pagina(formulario($baseDir, $csvPath, $limite) . '<div class="alert alert-danger mt-3">'.htmlspecialchars($err).'</div>'); exit; }

$totalCsv = count($filas);
$idx = construirIndicePacientes($conexion);

if ($modo === 'preview') {
    $cMatch = 0; $cAmb = 0; $cNada = 0; $cSinArch = 0;
    $muestraAmb = []; $muestraNada = [];
    foreach ($filas as $f) {
        $m = empatarPaciente($f['paciente'], $idx);
        if ($m['estado'] === 'match') {
            $cMatch++;
            $ruta = $baseDir . '/' . $f['paciente'] . '/' . $f['archivo'];
            if (!is_file($ruta)) $cSinArch++;
        } elseif ($m['estado'] === 'ambiguo') {
            $cAmb++; if (count($muestraAmb) < 25) $muestraAmb[] = $f['paciente'];
        } else {
            $cNada++; if (count($muestraNada) < 25) $muestraNada[] = $f['paciente'];
        }
    }
    $html = formulario($baseDir, $csvPath, $limite);
    $html .= '<div class="card card-body mt-3"><h5>Vista previa</h5>'
          . '<ul class="list-group mb-2">'
          . '<li class="list-group-item d-flex justify-content-between">Documentos en el CSV<span class="badge bg-dark">'.$totalCsv.'</span></li>'
          . '<li class="list-group-item d-flex justify-content-between">Empatados con un paciente<span class="badge bg-success">'.$cMatch.'</span></li>'
          . '<li class="list-group-item d-flex justify-content-between">Empatados pero el PDF no se encontró en disco<span class="badge bg-warning text-dark">'.$cSinArch.'</span></li>'
          . '<li class="list-group-item d-flex justify-content-between">Ambiguos (varios pacientes)<span class="badge bg-warning text-dark">'.$cAmb.'</span></li>'
          . '<li class="list-group-item d-flex justify-content-between">Sin paciente (no encontrado)<span class="badge bg-danger">'.$cNada.'</span></li>'
          . '</ul>';
    if ($muestraNada) $html .= '<p class="mb-1"><b>Ejemplos no encontrados:</b></p><p class="small text-muted">'.htmlspecialchars(implode(' · ', $muestraNada)).'</p>';
    if ($muestraAmb)  $html .= '<p class="mb-1"><b>Ejemplos ambiguos:</b></p><p class="small text-muted">'.htmlspecialchars(implode(' · ', $muestraAmb)).'</p>';
    $html .= '<div class="alert alert-info">Para importar, usa el botón <b>Importar lote</b> (procesa por tandas con "Desde" y "Lote"). Los ambiguos y no encontrados no se importan; revísalos aparte.</div></div>';
    pagina($html);
    exit;
}

// ── Modo IMPORTAR (por lote) ─────────────────────────────────────────────
if (!is_dir($DESTINO) && !mkdir($DESTINO, 0775, true) && !is_dir($DESTINO)) {
    pagina('<div class="alert alert-danger">No se pudo crear la carpeta de documentos.</div>'); exit;
}
// Asegurar la tabla
@$conexion->query("CREATE TABLE IF NOT EXISTS paciente_archivos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY, IDPACIENTE INT NOT NULL, titulo VARCHAR(255) NULL,
    nombre_original VARCHAR(255) NOT NULL, archivo VARCHAR(255) NOT NULL, mime VARCHAR(100) NULL,
    tamano INT NULL, id_usuario INT NULL, fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado CHAR(1) NOT NULL DEFAULT 'A', INDEX idx_pac (IDPACIENTE, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$idUser = (int)($_SESSION['iduser'] ?? 0);
$lote = array_slice($filas, $offset, $limite);
$imp = 0; $dup = 0; $noArch = 0; $noMatch = 0; $errCopia = 0;
$chkDup = $conexion->prepare("SELECT id FROM paciente_archivos WHERE IDPACIENTE=? AND nombre_original=? AND estado='A' LIMIT 1");
$ins = $conexion->prepare("INSERT INTO paciente_archivos (IDPACIENTE, titulo, nombre_original, archivo, mime, tamano, id_usuario) VALUES (?,?,?,?,?,?,?)");

foreach ($lote as $f) {
    $m = empatarPaciente($f['paciente'], $idx);
    if ($m['estado'] !== 'match') { $noMatch++; continue; }
    $idPac = (int)$m['id'];
    $src = $baseDir . '/' . $f['paciente'] . '/' . $f['archivo'];
    if (!is_file($src)) { $noArch++; continue; }

    // Duplicado por paciente + nombre original
    $nom = $f['archivo'];
    $chkDup->bind_param('is', $idPac, $nom); $chkDup->execute();
    if ($chkDup->get_result()->fetch_assoc()) { $dup++; continue; }

    try { $rand = bin2hex(random_bytes(8)); } catch (Exception $e) { $rand = substr(md5(uniqid('', true)),0,16); }
    $almacen = 'doc_'.$idPac.'_'.time().'_'.$rand.'.pdf';
    if (!@copy($src, $DESTINO.'/'.$almacen)) { $errCopia++; continue; }
    @chmod($DESTINO.'/'.$almacen, 0640);

    $titulo = trim(($f['titulo'] !== '' ? $f['titulo'] : $nom) . ($f['fecha'] !== '' ? ' ('.$f['fecha'].')' : ''));
    $titulo = mb_substr($titulo, 0, 255);
    $mime = 'application/pdf';
    $tam = (int)@filesize($DESTINO.'/'.$almacen);
    $ins->bind_param('issssii', $idPac, $titulo, $nom, $almacen, $mime, $tam, $idUser);
    if ($ins->execute()) $imp++; else { @unlink($DESTINO.'/'.$almacen); $errCopia++; }
}
$chkDup->close(); $ins->close();

auditar($conexion, 'Documentos', 'importar', 'AG_PACIENTE', null,
    "Importación Kalix (lote offset $offset, limite $limite): importados $imp, duplicados $dup, sin archivo $noArch, sin paciente $noMatch, errores $errCopia");

$siguiente = $offset + $limite;
$hayMas = $siguiente < $totalCsv;
$html = '<h4>Resultado del lote</h4>'
      . '<ul class="list-group mb-3" style="max-width:640px;">'
      . '<li class="list-group-item d-flex justify-content-between">Importados<span class="badge bg-success">'.$imp.'</span></li>'
      . '<li class="list-group-item d-flex justify-content-between">Ya existían (omitidos)<span class="badge bg-secondary">'.$dup.'</span></li>'
      . '<li class="list-group-item d-flex justify-content-between">PDF no encontrado en disco<span class="badge bg-warning text-dark">'.$noArch.'</span></li>'
      . '<li class="list-group-item d-flex justify-content-between">Sin paciente / ambiguo<span class="badge bg-warning text-dark">'.$noMatch.'</span></li>'
      . '<li class="list-group-item d-flex justify-content-between">Errores de copia<span class="badge bg-danger">'.$errCopia.'</span></li>'
      . '</ul>'
      . '<p>Procesado del '.($offset+1).' al '.min($siguiente,$totalCsv).' de '.$totalCsv.'.</p>';
if ($hayMas) {
    $html .= '<form method="POST">'
          . '<input type="hidden" name="baseDir" value="'.htmlspecialchars($baseDir).'">'
          . '<input type="hidden" name="csvPath" value="'.htmlspecialchars($csvPath).'">'
          . '<input type="hidden" name="offset" value="'.$siguiente.'">'
          . '<input type="hidden" name="limite" value="'.(int)$limite.'">'
          . '<button class="btn btn-danger" name="modo" value="importar" type="submit">Continuar siguiente lote (desde '.$siguiente.')</button>'
          . '</form>';
} else {
    $html .= '<div class="alert alert-success">Se procesaron todos los documentos del CSV.</div>';
}
$html .= '<a class="btn btn-link mt-2" href="importar_documentos_kalix.php">Volver</a>';
pagina($html);
