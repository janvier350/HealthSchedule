<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy(); header("Location: expirada.php"); exit();
}
$en = (current_lang() === 'en');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php te('help.centerTitle'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .help-acc .accordion-button { font-weight:600; }
        .help-acc .accordion-body { line-height:1.8; }
        .help-acc h6 { color:#0d6efd; font-weight:700; margin-top:.5rem; }
        .help-acc ul, .help-acc ol { margin-bottom:.5rem; }
    </style>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">
    <div class="app-header header-shadow">
        <div class="app-header__logo"><div class="logo-src"></div>
            <div class="header__pane ml-auto">
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box"><span class="hamburger-inner"></span></span>
                </button>
            </div>
        </div>
        <div class="app-header__mobile-menu">
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box"><span class="hamburger-inner"></span></span>
            </button>
        </div>
        <div class="app-header__content">
            <div class="app-header-left"></div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0"><div class="widget-content p-0"><div class="widget-content-wrapper">
                    <div class="widget-content-left ml-3 header-user-info">
                        <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                        <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                    </div>
                    <div class="widget-content-left ms-3">
                        <a href="salir.php" class="btn btn-sm btn-outline-secondary"><?php te('common.close'); ?></a>
                    </div>
                </div></div></div>
            </div>
        </div>
    </div>
    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>
        <div class="app-main__outer"><div class="app-main__inner">

            <div class="app-page-title mb-3"><div class="page-title-wrapper"><div class="page-title-heading">
                <div class="page-title-icon"><i class="pe-7s-help1 icon-gradient bg-mean-fruit"></i></div>
                <div><?php te('help.centerTitle'); ?>
                    <div class="page-title-subheading"><?php te('help.centerSub'); ?></div>
                </div>
            </div></div></div>

            <div class="accordion help-acc" id="accAyuda">

              <!-- Agenda -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#h1">
                    <i class="bi bi-calendar3 me-2"></i><?php te('help.scheduleTitle'); ?></button></h2>
                <div id="h1" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <h6>Schedule a new appointment</h6>
                    <ol><li>Click a day on the calendar (or the schedule button).</li>
                    <li>Choose date, patient, time, consultation type, doctor and location.</li>
                    <li>Recurrence: leave "Do not repeat" for a single appointment; for a series pick weekly/biweekly/monthly and confirm the count.</li></ol>
                    <div class="alert alert-warning py-2">To schedule, the patient must already have at least one ICD-10 diagnosis and a complete address.</div>
                    <h6>Move / change &amp; status</h6>
                    <ul><li>Drag the appointment or open it and use Reschedule (a confirmation appears before saving); the patient is emailed. For a series choose "all future visits" to move the whole series to the new weekday.</li>
                    <li>Attend any time; mark "No answer" if the patient doesn't pick up and move on.</li></ul>
                  <?php else: ?>
                    <h6>Agendar una cita nueva</h6>
                    <ol><li>Haz clic en un día del calendario (o en el botón de agendar).</li>
                    <li>Elige fecha, paciente, hora, tipo de consulta, doctor y location.</li>
                    <li>Recurrencia: deja "No repetir" para una sola cita; para una serie elige semanal/quincenal/mensual y confirma la cantidad.</li></ol>
                    <div class="alert alert-warning py-2">Para agendar, el paciente debe tener ya al menos un diagnóstico ICD-10 y la dirección completa.</div>
                    <h6>Mover / cambiar y estados</h6>
                    <ul><li>Arrastra la cita o ábrela y usa Reagendar (sale una confirmación antes de guardar); al paciente le llega correo. En una serie elige "todas las futuras" para mover toda la serie al nuevo día.</li>
                    <li>Atiende a cualquier hora; marca "No contestó" si el paciente no responde y sigue con otro.</li></ul>
                  <?php endif; ?>
                </div></div>
              </div>

              <!-- Citas recurrentes y revisión -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hrec">
                    <i class="bi bi-arrow-repeat me-2"></i><?php te('help.recurringTitle'); ?></button></h2>
                <div id="hrec" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <h6>Create a recurring series</h6>
                    <ol>
                      <li>In the schedule form set the <b>first date, time, patient, type, doctor and location</b>.</li>
                      <li>In <b>Repeat appointment</b> choose <b>Weekly / Biweekly / Monthly</b> (or "Do not repeat" for one).</li>
                      <li>In <b>End…</b> choose <b>after N sessions</b> or <b>on a date</b>. It confirms how many will be created.</li>
                    </ol>
                    <h6>Double-booking</h6>
                    <ul>
                      <li>If the slot is already taken for that doctor, by default those dates are <b>skipped</b> and the message tells you <b>which date and which patient</b> occupies each.</li>
                      <li>Tick <b>"Allow double-booking"</b> to schedule anyway (overlap). Assistants then resolve clashes.</li>
                    </ul>
                    <h6>Review, edit or cancel — "Appointments by date"</h6>
                    <ul>
                      <li>Open <b>Agenda → Appointments by date</b> and pick a <b>date range</b> (and optionally doctor/patient).</li>
                      <li><b>Edit</b> a row to change date/time/type/doctor/location — for a series choose <b>only this one</b> or the <b>whole future series</b>.</li>
                      <li><b>Cancel</b> / <b>Cancel series</b>, or tick several and <b>Cancel selected</b>. This cleanup <b>does not email</b> the patient. Everything is saved in the audit log.</li>
                    </ul>
                    <p class="text-muted small mb-0">Note: the calendar shows the last 3 months plus the future; to review older dates use "Appointments by date".</p>
                  <?php else: ?>
                    <h6>Crear una serie recurrente</h6>
                    <ol>
                      <li>En el formulario de agendar pon la <b>primera fecha, hora, paciente, tipo, doctor y lugar</b>.</li>
                      <li>En <b>Repetir cita</b> elige <b>Semanal / Quincenal / Mensual</b> (o "No repetir" para una sola).</li>
                      <li>En <b>Terminar…</b> elige <b>después de N sesiones</b> o <b>en una fecha</b>. Te confirma cuántas se van a crear.</li>
                    </ol>
                    <h6>Doble reserva</h6>
                    <ul>
                      <li>Si el horario ya está ocupado para ese doctor, por defecto esas fechas se <b>saltan</b> y el aviso te dice <b>qué fecha y qué paciente</b> la ocupa.</li>
                      <li>Marca <b>"Permitir doble reserva"</b> para agendar de todos modos (encimado). Las asistentes luego resuelven los choques.</li>
                    </ul>
                    <h6>Revisar, editar o dar de baja — "Citas por fecha"</h6>
                    <ul>
                      <li>Entra a <b>Agenda → Citas por fecha</b> y elige un <b>rango de fechas</b> (y opcional doctor/paciente).</li>
                      <li><b>Editar</b> una fila para cambiar fecha/hora/tipo/doctor/lugar — en una serie elige <b>solo esta</b> o <b>toda la serie futura</b>.</li>
                      <li><b>Baja</b> / <b>Baja serie</b>, o marca varias y <b>Dar de baja seleccionadas</b>. Esta limpieza <b>no envía correo</b> al paciente. Todo queda en la bitácora.</li>
                    </ul>
                    <p class="text-muted small mb-0">Nota: el calendario muestra los últimos 3 meses y el futuro; para revisar fechas más antiguas usa "Citas por fecha".</p>
                  <?php endif; ?>
                </div></div>
              </div>

              <!-- Consulta -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#h2">
                    <i class="bi bi-clipboard2-pulse me-2"></i><?php te('help.attendTitle'); ?></button></h2>
                <div id="h2" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <ul><li>Type weight &amp; height → BMI auto-calculated (units kg/lbs, cm/m/ft-in). Previous weight/height shows in the header.</li>
                    <li>Follow-ups pre-load the previous note.</li>
                    <li>Add ICD-10 diagnoses; insert an NCP/PES nutrition diagnosis.</li>
                    <li>Nutrition Calculator opens pre-filled; press "Insert into report" on any card.</li>
                    <li>Load a report template; auto fields fill by themselves (dates in month/day/year).</li>
                    <li>Save &amp; Finish stores the note and marks the visit attended; Preview &amp; Print prints it.</li></ul>
                  <?php else: ?>
                    <ul><li>Escribe peso y estatura → IMC automático (unidades kg/lbs, cm/m/pies-pulgadas). El peso/talla anterior sale en el encabezado.</li>
                    <li>En seguimientos se precarga la nota anterior.</li>
                    <li>Agrega diagnósticos ICD-10; inserta un diagnóstico nutricional NCP/PES.</li>
                    <li>La Calculadora abre con los datos cargados; pulsa "Insertar en el informe" en cualquier tarjeta.</li>
                    <li>Carga una plantilla; los campos automáticos se llenan solos (fechas en mes/día/año).</li>
                    <li>Guardar y finalizar guarda la nota y marca la cita como atendida; Vista previa e imprimir la imprime.</li></ul>
                  <?php endif; ?>
                </div></div>
              </div>

              <!-- Calculadora -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#h3">
                    <i class="bi bi-calculator me-2"></i><?php te('help.calcTitle'); ?></button></h2>
                <div id="h3" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <h6>Adult</h6>
                    <ul><li>Base: BMR (Mifflin), TEE, ideal &amp; adjusted weight, BMI, fluids (Holliday-Segar).</li>
                    <li>Clinical condition (incl. Overweight and Obesity classes) for calorie/protein/fluid ranges.</li>
                    <li>Muscle gain (hypertrophy): surplus &amp; macros from TEE.</li></ul>
                    <h6>Pediatric</h6>
                    <ul><li>EER (IOM/DRI by age), protein by age, fluids, fiber, quick kcal/kg. Age with decimals for infants (0.5 = 6 months).</li></ul>
                    <h6>Heart rate &amp; manual</h6>
                    <ul><li>HRmax (Tanaka) + training zones; add resting HR for Karvonen.</li>
                    <li>Manual per kg: type kcal/kg, protein g/kg or fluid mL/kg → multiplied by weight.</li></ul>
                    <div class="alert alert-info py-2 mb-0">Every card has "Insert into report" to drop it into the note.</div>
                  <?php else: ?>
                    <h6>Adulto</h6>
                    <ul><li>Base: TMB (Mifflin), GET, peso ideal y ajustado, IMC, líquidos (Holliday-Segar).</li>
                    <li>Condición clínica (incluye Sobrepeso y clases de Obesidad) para rangos de calorías/proteína/líquidos.</li>
                    <li>Aumento muscular (hipertrofia): superávit y macros desde el GET.</li></ul>
                    <h6>Pediátrico</h6>
                    <ul><li>EER (IOM/DRI por edad), proteína por edad, líquidos, fibra, kcal/kg rápido. Edad con decimales para bebés (0.5 = 6 meses).</li></ul>
                    <h6>Frecuencia cardíaca y manual</h6>
                    <ul><li>FCmáx (Tanaka) + zonas de entrenamiento; agrega la FC en reposo para Karvonen.</li>
                    <li>Manual por kg: escribe kcal/kg, proteína g/kg o líquidos mL/kg → se multiplica por el peso.</li></ul>
                    <div class="alert alert-info py-2 mb-0">Cada tarjeta tiene "Insertar en el informe" para pasarlo a la nota.</div>
                  <?php endif; ?>
                </div></div>
              </div>

              <!-- Pacientes -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#h4">
                    <i class="bi bi-people me-2"></i><?php te('help.patientsTitle'); ?></button></h2>
                <div id="h4" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <ol><li>Register a patient: name, ID, date of birth, sex, phone, email and address (street, or city + state + ZIP).</li>
                    <li>You can add extra phones/emails.</li>
                    <li>ICD-10 + address are required to schedule.</li>
                    <li>Insurance: insurer(s), policy number, priority, and card image.</li>
                    <li>Search and click a patient to edit, see history or open the record.</li>
                    <li>Deleting asks for a reason and keeps the patient in "Deleted patients".</li></ol>
                  <?php else: ?>
                    <ol><li>Registra un paciente: nombre, ID, fecha de nacimiento, sexo, teléfono, correo y dirección (calle, o ciudad + estado + ZIP).</li>
                    <li>Puedes agregar teléfonos/correos extra.</li>
                    <li>El ICD-10 y la dirección son obligatorios para agendar.</li>
                    <li>Seguro: aseguradora(s), número de póliza, prioridad e imagen de la tarjeta.</li>
                    <li>Busca y haz clic en un paciente para editar, ver historial o abrir la ficha.</li>
                    <li>Al eliminar se pide un motivo y el paciente queda en "Pacientes eliminados".</li></ol>
                  <?php endif; ?>
                </div></div>
              </div>

              <!-- Documentos a pacientes -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hdoc">
                    <i class="bi bi-file-earmark-text me-2"></i><?php te('help.documentsTitle'); ?></button></h2>
                <div id="hdoc" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <p><b>1) Templates — "Documents" menu.</b> Here you keep the reusable documents/policies you send patients to sign (consent, HIPAA notice, credit-card authorization, etc.).</p>
                    <ul>
                      <li><b>New Document</b> creates a template; the pencil edits one.</li>
                      <li>The <i class="bi bi-send"></i> (send) icon sends that document to a patient.</li>
                    </ul>
                    <p><b>2) Send to a patient.</b> Click the send icon, choose the patient, and send. The patient gets an email with a link to read and <b>sign</b> the document online.</p>
                    <p><b>3) Track them — "Sent Documents" menu.</b> Every send is listed with the patient, the document, the <b>status</b> (Pending / Signed), and the sent &amp; signed dates.</p>
                    <ul>
                      <li><b>Pending</b> = sent but not signed yet. <b>Signed</b> = the patient already signed (with the date/time).</li>
                      <li><b>View</b> opens the document to see it or the signed copy.</li>
                      <li>Use the search box to filter by patient, document or status.</li>
                    </ul>
                    <p class="text-muted small mb-0">Tip: signed documents are the proof of consent for billing/compliance — check "Sent Documents" before the first visit.</p>
                  <?php else: ?>
                    <p><b>1) Plantillas — menú "Documentos".</b> Aquí guardas los documentos/políticas reutilizables que envías a los pacientes para firmar (consentimiento, aviso HIPAA, autorización de tarjeta, etc.).</p>
                    <ul>
                      <li><b>Nuevo Documento</b> crea una plantilla; el lápiz la edita.</li>
                      <li>El ícono <i class="bi bi-send"></i> (enviar) manda ese documento a un paciente.</li>
                    </ul>
                    <p><b>2) Enviar a un paciente.</b> Da clic en el ícono de enviar, elige al paciente y envía. El paciente recibe un correo con un enlace para leer y <b>firmar</b> el documento en línea.</p>
                    <p><b>3) Darles seguimiento — menú "Documentos Enviados".</b> Cada envío aparece con el paciente, el documento, el <b>estado</b> (Pendiente / Firmado) y las fechas de envío y firma.</p>
                    <ul>
                      <li><b>Pendiente</b> = enviado pero aún sin firmar. <b>Firmado</b> = el paciente ya firmó (con fecha y hora).</li>
                      <li><b>Ver</b> abre el documento para revisarlo o ver la copia firmada.</li>
                      <li>Usa el buscador para filtrar por paciente, documento o estado.</li>
                    </ul>
                    <p class="text-muted small mb-0">Consejo: los documentos firmados son la constancia de consentimiento para facturación/cumplimiento — revisa "Documentos Enviados" antes de la primera consulta.</p>
                  <?php endif; ?>
                </div></div>
              </div>

              <!-- Facturación -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#h5">
                    <i class="bi bi-receipt me-2"></i><?php te('help.billingTitle'); ?></button></h2>
                <div id="h5" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <ul><li>Create an invoice: choose patient and date, add line items (auto-total), save.</li>
                    <li>Accounts Receivable shows each invoice's status; open one to register a full/partial payment or print it.</li>
                    <li>Service/prices catalog = Bill Items. Insurers = Insurance.</li></ul>
                  <?php else: ?>
                    <ul><li>Crea una factura: elige paciente y fecha, agrega renglones (total automático), guarda.</li>
                    <li>Cuentas por Cobrar muestra el estado de cada factura; abre una para registrar un pago total/parcial o imprimirla.</li>
                    <li>Catálogo de servicios/precios = Bill Items. Aseguradoras = Seguros.</li></ul>
                  <?php endif; ?>
                </div></div>
              </div>

              <?php if (strtoupper($_SESSION['rol'] ?? '') === 'SISTEMA'): ?>
              <!-- Administración (solo SISTEMA) -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#h6">
                    <i class="bi bi-shield-lock me-2"></i><?php te('help.adminTitle'); ?></button></h2>
                <div id="h6" class="accordion-collapse collapse" data-bs-parent="#accAyuda"><div class="accordion-body">
                  <?php if ($en): ?>
                    <h6>Users &amp; permissions</h6>
                    <ul><li>Control Panel → <b>Users</b>: create users and assign a role (Doctor, Assistant, System…).</li>
                    <li>Control Panel → <b>User permissions</b>: turn each module on/off <b>per person</b> (allow / block / inherit from role). SYSTEM always has full access.</li></ul>
                    <h6>Catalogs</h6>
                    <ul><li><b>ICD-10</b>, <b>Consultation types</b>, <b>NCP/PES diagnoses</b>, <b>Insurance types</b> and <b>Insurers</b> are maintained from the Configuration/Billing menu.</li>
                    <li><b>NCP/PES:</b> add more nutrition-diagnosis cases from menu → NCP/PES Diagnoses.</li></ul>
                    <h6>Status colors &amp; duplicates</h6>
                    <ul><li><b>Status Colors:</b> choose or customize the color palette for appointment statuses (per user).</li>
                    <li><b>Merge duplicates:</b> if a patient exists twice, merge them without losing history.</li></ul>
                    <h6>Migrations</h6>
                    <ul><li>New features that need database changes ship with a one-time <b>migration</b> page (SYSTEM only). Run it once after deploying; it's idempotent (safe to re-run). Example: <code>migrar_ncp_diagnosticos.php</code> to load new NCP/PES cases.</li></ul>
                  <?php else: ?>
                    <h6>Usuarios y permisos</h6>
                    <ul><li>Panel de Control → <b>Usuarios</b>: crea usuarios y asigna su rol (Doctor, Asistente, Sistema…).</li>
                    <li>Panel de Control → <b>Permisos por usuario</b>: prende/apaga cada módulo <b>por persona</b> (permitir / bloquear / según el rol). SISTEMA siempre tiene acceso total.</li></ul>
                    <h6>Catálogos</h6>
                    <ul><li><b>ICD-10</b>, <b>Tipos de consulta</b>, <b>Diagnósticos NCP/PES</b>, <b>Tipos de seguro</b> y <b>Aseguradoras</b> se mantienen desde el menú de Configuración/Facturación.</li>
                    <li><b>NCP/PES:</b> agrega más casos de diagnóstico nutricional en menú → Diagnósticos NCP/PES.</li></ul>
                    <h6>Colores de estado y duplicados</h6>
                    <ul><li><b>Colores de estado:</b> elige o personaliza la paleta de colores de los estados de cita (por usuario).</li>
                    <li><b>Fusionar duplicados:</b> si un paciente está dos veces, fusiónalos sin perder el historial.</li></ul>
                    <h6>Migraciones</h6>
                    <ul><li>Las funciones nuevas que requieren cambios en la base de datos traen una página de <b>migración</b> de una sola vez (solo SISTEMA). Ejecútala una vez tras desplegar; es idempotente (se puede repetir sin problema). Ejemplo: <code>migrar_ncp_diagnosticos.php</code> para cargar nuevos casos NCP/PES.</li></ul>
                  <?php endif; ?>
                </div></div>
              </div>
              <?php endif; ?>

            </div>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
