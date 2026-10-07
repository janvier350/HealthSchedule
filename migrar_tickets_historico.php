<?php
/**
 * migrar_tickets_historico.php — Carga retrospectiva del trabajo ya realizado
 * como tickets CERRADOS, para tener un histórico consultable desde el módulo
 * de Solicitudes. Idempotente: no duplica (se salta los que ya existen por
 * título). Las fechas son aproximadas (registro retrospectivo).
 * SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/class/tickets.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
tickets_ensure_tablas($conexion);

// [titulo, descripcion, modulo, tipo, prioridad, solicitante, rol, dias_atras]
// dias_atras = cuántos días atrás (aprox.) se resolvió, para ordenar el histórico.
$items = [
    ['Dirección y teléfono del paciente no se guardaban',
     'Al editar un paciente, la dirección completa y el teléfono se borraban al guardar. Se corrigió el ancho de columnas (ADDRESS/TELEFONO) y el guardado.',
     'Pacientes','Error','Alta','Flor Ramírez','ASISTENTE',120],
    ['Ciudad, Estado y ZIP desaparecían al editar paciente',
     'La ciudad/estado/zip no se guardaban o no aparecían al reabrir. Se auto-provisionan las columnas CITY/STATE/ZIP y se corrigió el guardado y la visualización.',
     'Pacientes','Error','Alta','Nahida','ASISTENTE',70],
    ['Mostrar la dirección completa en la tarjeta del paciente',
     'La tarjeta del paciente no mostraba ciudad/estado/zip. Ahora muestra la dirección completa (o aviso si falta).',
     'Pacientes','Mejora','Media','Dra. Silvia Ross','DOCTOR',68],
    ['Autoguardado de notas de consulta',
     'Se perdían notas largas si se caía la sesión. Se agregó autoguardado (borrador) para no perder el trabajo.',
     'Pacientes','Mejora','Alta','Dra. Silvia Ross','DOCTOR',95],
    ['Tarjetas de notas ajustadas al contenido',
     'Las tarjetas de notas quedaban de alto fijo. Ahora se ajustan al contenido.',
     'Pacientes','Mejora','Baja','Dra. Silvia Ross','DOCTOR',5],
    ['Contactos adicionales: error FALTA_MIGRACION al guardar teléfono',
     'No dejaba guardar teléfonos/correos adicionales. Se auto-crea la tabla de contactos para evitar el error.',
     'Pacientes','Error','Media','Dra. Silvia Ross','DOCTOR',30],
    ['Módulo seguro de documentos del paciente (PDF)',
     'Se creó almacenamiento seguro por paciente para subir PDFs (carpeta protegida, acceso sólo con sesión y permiso, registro de accesos).',
     'Documentos','Mejora','Alta','Dra. Silvia Ross','DOCTOR',45],
    ['Importador masivo de reportes de Kalix',
     'Se construyó el importador masivo de PDFs de Kalix (emparejado por nombre, importación por lotes). La importación quedó en pausa hasta verificar que las carpetas estén completas.',
     'Documentos','Mejora','Media','Javier Varas','SISTEMA',28],
    ['Anonimizar datos reales para la copia DEMO',
     'Se creó una forma segura de anonimizar los datos de pacientes en la copia DEMO.',
     'Pacientes','Mejora','Media','Javier Varas','SISTEMA',90],
    ['Calendario con letra muy pequeña — control de tamaño',
     'La información del calendario se veía muy pequeña. Se agregó un control A-/A/A+ para subir el tamaño de la fuente.',
     'Agenda','Mejora','Media','Dra. Silvia Ross','DOCTOR',110],
    ['Aplicar el tamaño de fuente también al modal de cita',
     'Se extendió el control de tamaño de letra al modal de la cita.',
     'Agenda','Mejora','Baja','Dra. Silvia Ross','DOCTOR',108],
    ['El Doctor "rebotaba" al agendar la cita',
     'Al elegir Doctor en el select, se reiniciaba/rebotaba en algunas resoluciones. Se corrigió para que sea compatible con distintas pantallas.',
     'Agenda','Error','Alta','Dra. Silvia Ross','DOCTOR',100],
    ['Citas recurrentes "saltaban" fechas',
     'Se revisó el caso de citas repetidas: se agregó verificación de conflicto por doctor y un reporte de qué fechas/pacientes bloquean la serie.',
     'Agenda','Error','Media','Dra. Silvia Ross','DOCTOR',40],
    ['Habilitar doble reserva (opcional)',
     'Se agregó la opción de permitir doble reserva en el mismo horario cuando se requiera.',
     'Agenda','Mejora','Media','Javier Varas','SISTEMA',38],
    ['Página "Citas por fecha" para revisar/editar/cancelar series',
     'Se creó una página para revisar citas por rango de fechas, editar o dar de baja citas y series. Se quitó el límite de 12 por serie.',
     'Agenda','Mejora','Media','Dra. Silvia Ross','DOCTOR',25],
    ['Editar serie y cambiar frecuencia (semanal ↔ quincenal)',
     'Se permitió editar una serie de citas y cambiar su frecuencia (p. ej. de cada semana a cada 2 semanas).',
     'Agenda','Mejora','Media','Dra. Silvia Ross','DOCTOR',22],
    ['Mostrar el diagnóstico ICD-10 al agendar',
     'Al agendar una cita ahora se muestra el diagnóstico ICD-10 del paciente.',
     'Agenda','Mejora','Media','Dra. Silvia Ross','DOCTOR',20],
    ['Gráfico de evolución de peso e IMC en el informe',
     'Se creó un gráfico de peso + IMC con alternancia y se puede insertar en el informe de consulta.',
     'Informes','Mejora','Media','Dra. Silvia Ross','DOCTOR',50],
    ['El informe abría detrás del historial del paciente',
     'Al abrir el informe desde el historial, quedaba por detrás. Se corrigió el apilado de ventanas (z-index).',
     'Informes','Error','Media','Dra. Silvia Ross','DOCTOR',18],
    ['Exportar informes de consulta a PDF',
     'Se agregó un botón para exportar los informes de consulta a PDF.',
     'Informes','Mejora','Media','Dra. Silvia Ross','DOCTOR',12],
    ['Sesión a 24 horas y no cortarse al escribir',
     'La sesión duraba menos de lo esperado y se cortaba escribiendo notas largas. Se ajustó a 24h y se corrigió el tiempo de vida de la sesión en PHP.',
     'Sesión/Acceso','Error','Alta','Dra. Silvia Ross','DOCTOR',92],
    ['Bitácora de auditoría (quién hace cambios y en qué módulo)',
     'Se creó la bitácora de cambios para ver quién crea/edita/elimina y en qué módulo, visible para SISTEMA y la Dra.',
     'Pacientes','Mejora','Alta','Dra. Silvia Ross','DOCTOR',35],
    ['Auditoría: citas, ICD-10, campos modificados, sesión y Excel',
     'Se amplió la auditoría para cubrir edición de citas, alta/baja de ICD-10, columna de paciente, qué campos cambiaron, eventos de sesión y exportación a Excel.',
     'Pacientes','Mejora','Media','Dra. Silvia Ross','DOCTOR',33],
    ['Agregar casos NCP (hipotiroidismo, hipertiroidismo, hígado graso)',
     'Se agregaron planes de cuidado NCP desde un PDF bilingüe.',
     'ICD-10','Mejora','Media','Dra. Silvia Ross','DOCTOR',105],
    ['Códigos de obesidad: agregar nuevos y desactivar estigmatizantes',
     'Se agregaron E66.811/812/813, E66.89, Z68.4 y Z71.82 y se desactivaron E66.0/E66.01/E66.09.',
     'ICD-10','Corrección','Media','Nahida','ASISTENTE',1],
    ['Guía para enviar documentos a los pacientes',
     'Se agregó una guía en el Centro de ayuda para enviar documentos a los pacientes.',
     'Documentos','Mejora','Baja','Javier Varas','SISTEMA',60],

    // ── Mejoras de iniciativa propia (no solicitadas) ──────────────────
    ['Módulo de facturación (facturas y cuentas por cobrar)',
     'Se construyó el módulo de facturación: creación de facturas y cuentas por cobrar.',
     'Facturación','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',185],
    ['Registrar y anular pagos',
     'Registro y anulación de pagos sobre las facturas.',
     'Facturación','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',183],
    ['Catálogo de Bill Items',
     'Catálogo de conceptos de facturación (Bill Items).',
     'Facturación','Mejora','Baja','Iniciativa propia (Javier)','SISTEMA',182],
    ['Gestión de aseguradoras',
     'Alta y administración de aseguradoras.',
     'Facturación','Mejora','Baja','Iniciativa propia (Javier)','SISTEMA',181],
    ['Importar facturas desde Kalix',
     'Importación de facturas desde Kalix.',
     'Facturación','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',150],
    ['Reportes de facturación',
     'Reportes de facturación para seguimiento de cobros.',
     'Facturación','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',148],
    ['Tipos de seguro (catálogo)',
     'Catálogo de tipos de seguro.',
     'Facturación','Mejora','Baja','Iniciativa propia (Javier)','SISTEMA',180],
    ['Colores de estado del calendario',
     'Personalización de colores por estado de cita en el calendario (UX).',
     'Agenda','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',175],
    ['Tipos de consulta con colores',
     'Catálogo de tipos de consulta con color para identificarlos en la agenda.',
     'Agenda','Mejora','Baja','Iniciativa propia (Javier)','SISTEMA',174],
    ['Planificador de peso (Weight Planner)',
     'Herramienta de planificación de peso.',
     'Agenda','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',170],
    ['Vacaciones / no disponibilidad del doctor',
     'Gestión de ausencias del doctor para bloquear la agenda en esas fechas.',
     'Agenda','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',165],
    ['Papelera de pacientes eliminados (restaurar)',
     'Pacientes eliminados van a una papelera y se pueden restaurar.',
     'Pacientes','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',160],
    ['Fusionar pacientes duplicados',
     'Herramienta para unir pacientes duplicados conservando su historia.',
     'Pacientes','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',158],
    ['Plantillas de documentos y envío a pacientes',
     'Plantillas de documentos y envío/registro de documentos enviados a pacientes.',
     'Documentos','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',155],
    ['Plantillas de informe',
     'Plantillas reutilizables para los informes de consulta.',
     'Informes','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',152],
    ['Calculadora nutricional',
     'Calculadora nutricional integrada.',
     'Informes','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',150],
    ['Permisos por usuario (acceso por módulo)',
     'Control de acceso por módulo, configurable por usuario (sobre el rol).',
     'Sesión/Acceso','Mejora','Alta','Iniciativa propia (Javier)','SISTEMA',145],
    ['Soporte bilingüe Español / Inglés',
     'Toda la aplicación disponible en español e inglés.',
     'Sesión/Acceso','Mejora','Media','Iniciativa propia (Javier)','SISTEMA',140],
    ['Centro de ayuda',
     'Sección de ayuda con guías de uso dentro de la app.',
     'Otro','Mejora','Baja','Iniciativa propia (Javier)','SISTEMA',135],
];

$ins = 0; $skip = 0;
$chk = $conexion->prepare("SELECT id FROM tickets WHERE titulo=? LIMIT 1");
$sql = "INSERT INTO tickets
          (titulo, descripcion, modulo, tipo, prioridad, estado, id_solicitante, solicitante, rol_solicitante, fecha, fecha_actualizacion, fecha_cierre)
        VALUES (?,?,?,?,?, 'Cerrado', NULL, ?, ?,
                DATE_SUB(NOW(), INTERVAL ? DAY),
                DATE_SUB(NOW(), INTERVAL GREATEST(?-1,0) DAY),
                DATE_SUB(NOW(), INTERVAL GREATEST(?-1,0) DAY))";
$st = $conexion->prepare($sql);

foreach ($items as $it) {
    [$titulo,$desc,$modulo,$tipo,$prio,$sol,$rol,$dias] = $it;
    $desc .= "\n\n(Registrado como histórico: trabajo ya realizado. Fecha aproximada.)";
    $chk->bind_param('s', $titulo); $chk->execute();
    if ($chk->get_result()->fetch_assoc()) { $skip++; continue; }
    $d = (int)$dias;
    $st->bind_param('sssssssiii', $titulo,$desc,$modulo,$tipo,$prio,$sol,$rol,$d,$d,$d);
    if ($st->execute()) $ins++;
}
$chk->close(); $st->close();

echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Histórico de tickets</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:640px;">';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h4>Histórico cargado</h4>'
       . '<div class="alert alert-success">Insertados: <b>'.$ins.'</b> · Omitidos (ya existían): <b>'.$skip.'</b></div>'
       . '<a class="btn btn-primary" href="tickets.php?estado=Cerrado">Ver histórico</a>';
} else {
    echo '<h4>Cargar histórico de trabajo como tickets cerrados</h4>'
       . '<p class="text-muted">Agrega '.count($items).' solicitudes ya resueltas (desde el inicio del proyecto) como tickets <b>Cerrados</b>, para tener un registro consultable. Idempotente: no duplica.</p>'
       . '<form method="POST"><button class="btn btn-primary" type="submit">Cargar histórico</button> '
       . '<a class="btn btn-link" href="tickets.php">Cancelar</a></form>';
}
echo '</div></body></html>';
