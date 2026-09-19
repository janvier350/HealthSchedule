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
                    <li>Recurrence: leave "Do not repeat" for a single appointment; for a series pick weekly/biweekly/monthly (up to 12) and confirm the count.</li></ol>
                    <div class="alert alert-warning py-2">To schedule, the patient must already have at least one ICD-10 diagnosis and a complete address.</div>
                    <h6>Move / change &amp; status</h6>
                    <ul><li>Drag the appointment or use Reschedule; the patient is emailed. For a series choose "all future visits" to move the whole series to the new weekday.</li>
                    <li>Attend any time; mark "No answer" if the patient doesn't pick up and move on.</li></ul>
                  <?php else: ?>
                    <h6>Agendar una cita nueva</h6>
                    <ol><li>Haz clic en un día del calendario (o en el botón de agendar).</li>
                    <li>Elige fecha, paciente, hora, tipo de consulta, doctor y location.</li>
                    <li>Recurrencia: deja "No repetir" para una sola cita; para una serie elige semanal/quincenal/mensual (hasta 12) y confirma la cantidad.</li></ol>
                    <div class="alert alert-warning py-2">Para agendar, el paciente debe tener ya al menos un diagnóstico ICD-10 y la dirección completa.</div>
                    <h6>Mover / cambiar y estados</h6>
                    <ul><li>Arrastra la cita o usa Reagendar; al paciente le llega correo. En una serie elige "todas las futuras" para mover toda la serie al nuevo día.</li>
                    <li>Atiende a cualquier hora; marca "No contestó" si el paciente no responde y sigue con otro.</li></ul>
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

            </div>

        </div></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
