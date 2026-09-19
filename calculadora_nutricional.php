<?php
ob_start();
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
require_once(__DIR__ . "/class/permisos.php");

if (!isset($_SESSION["rol"])) { header("Location: break.php"); exit(); }
if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
    session_destroy(); header("Location: expirada.php"); exit();
}
requerir('rep.calculadora');
$rol = strtoupper($_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php te('nc.pageTitle'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./main.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <style>
        .nc-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:1rem; }
        .nc-metric { font-size:1.6rem; font-weight:700; color:#3d5af1; }
        .nc-metric small { font-size:.7rem; color:#64748b; font-weight:500; margin-left:4px; }
        .nc-section { color:#5a2d82; font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.5rem; }
        .nc-cond-note { background:#fff8e1; border-left:3px solid #f59e0b; padding:6px 10px; font-size:.8rem; margin-bottom:6px; }
        .badge-basis { font-size:.65rem; }
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
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0"><div class="widget-content-wrapper">
                        <div class="widget-content-left ml-3 header-user-info">
                            <div class="widget-heading"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
                            <div class="widget-subheading"><?php echo htmlspecialchars($_SESSION['rol'] ?? ''); ?></div>
                        </div>
                        <div class="widget-content-left ms-3">
                            <a href="salir.php" class="btn btn-sm btn-outline-secondary"><?php te('common.close'); ?></a>
                        </div>
                    </div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-main">
        <div class="app-sidebar sidebar-shadow"><?php include("./menu/menu_adm.php"); ?></div>

        <div class="app-main__outer">
            <div class="app-main__inner">

                <div class="app-page-title mb-3">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon">
                                <i class="pe-7s-calculator icon-gradient bg-warm-flame"></i>
                            </div>
                            <div>
                                <?php te('nc.title'); ?>
                                <div class="page-title-subheading"><?php te('nc.subtitle'); ?></div>
                            </div>
                        </div>
                        <div class="page-title-actions">
                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalAyudaCalc">
                                <i class="bi bi-question-circle me-1"></i><?php te('help.howItWorks'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── MODAL: ¿Cómo usar la calculadora? ──────────────── -->
                <div class="modal fade" id="modalAyudaCalc" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="bi bi-calculator me-2"></i><?php te('help.calcTitle'); ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <?php if (current_lang() === 'en'): ?>
                          <p class="text-muted mb-2">Fill sex, age, weight and height. Every result recalculates automatically. From inside a consultation it opens pre-filled with the patient's data.</p>
                          <h6 class="fw-bold text-info"><i class="bi bi-person me-1"></i>Adult</h6>
                          <ul style="line-height:1.8;">
                            <li><b>Base:</b> BMR (Mifflin-St Jeor), TEE (BMR × activity), ideal &amp; adjusted weight, BMI, fluids (Holliday-Segar).</li>
                            <li><b>Clinical condition:</b> pick one (incl. <b>Overweight</b> and Obesity classes) for recommended calorie / protein / fluid ranges.</li>
                            <li><b>Muscle gain (hypertrophy):</b> surplus targets and macros from the TEE.</li>
                          </ul>
                          <h6 class="fw-bold text-info"><i class="bi bi-emoji-smile me-1"></i>Pediatric</h6>
                          <ul style="line-height:1.8;">
                            <li>Toggle <b>Pediatric</b> (auto for under-18 inside a consultation). Shows <b>EER</b> (IOM/DRI by age), protein by age, fluids (Holliday-Segar), fiber and quick kcal/kg. Enter age with decimals for infants (e.g. 0.5 = 6 months).</li>
                          </ul>
                          <h6 class="fw-bold text-info"><i class="bi bi-heart-pulse me-1"></i>Heart rate &amp; manual</h6>
                          <ul style="line-height:1.8;">
                            <li><b>Heart rate:</b> HRmax (Tanaka) and training zones; add the resting HR for the Karvonen method.</li>
                            <li><b>Manual (per kg):</b> type your own kcal/kg, protein g/kg or fluid mL/kg and it multiplies by the weight automatically.</li>
                          </ul>
                          <div class="alert alert-info py-2 mb-0"><b>Insert into report:</b> every card has this button — it drops that block into the consultation note.</div>
                        <?php else: ?>
                          <p class="text-muted mb-2">Llena sexo, edad, peso y talla. Todo se recalcula solo. Desde una consulta abre con los datos del paciente ya cargados.</p>
                          <h6 class="fw-bold text-info"><i class="bi bi-person me-1"></i>Adulto</h6>
                          <ul style="line-height:1.8;">
                            <li><b>Base:</b> TMB (Mifflin-St Jeor), GET (TMB × actividad), peso ideal y ajustado, IMC, líquidos (Holliday-Segar).</li>
                            <li><b>Condición clínica:</b> elige una (incluye <b>Sobrepeso</b> y las clases de Obesidad) para los rangos recomendados de calorías / proteína / líquidos.</li>
                            <li><b>Aumento muscular (hipertrofia):</b> superávit y macros a partir del GET.</li>
                          </ul>
                          <h6 class="fw-bold text-info"><i class="bi bi-emoji-smile me-1"></i>Pediátrico</h6>
                          <ul style="line-height:1.8;">
                            <li>Activa <b>Pediátrico</b> (automático en menores de 18 desde la consulta). Muestra <b>EER</b> (IOM/DRI por edad), proteína por edad, líquidos (Holliday-Segar), fibra y kcal/kg rápido. Para bebés escribe la edad con decimales (ej. 0.5 = 6 meses).</li>
                          </ul>
                          <h6 class="fw-bold text-info"><i class="bi bi-heart-pulse me-1"></i>Frecuencia cardíaca y manual</h6>
                          <ul style="line-height:1.8;">
                            <li><b>Frecuencia cardíaca:</b> FCmáx (Tanaka) y zonas de entrenamiento; agrega la FC en reposo para el método Karvonen.</li>
                            <li><b>Manual (por kg):</b> escribe tu propio kcal/kg, proteína g/kg o líquidos mL/kg y se multiplica por el peso automáticamente.</li>
                          </ul>
                          <div class="alert alert-info py-2 mb-0"><b>Insertar en el informe:</b> cada tarjeta tiene este botón — coloca ese bloque en la nota de la consulta.</div>
                        <?php endif; ?>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php te('common.close'); ?></button>
                      </div>
                    </div>
                  </div>
                </div>

                <?php include(__DIR__ . '/calculadora_nutricional_widget.php'); ?>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/nutri_calc.js"></script>
<script src="js/nutri_calc_ui.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    NutriCalcUI.mount('#ncRoot', { lang: <?php echo json_encode(current_lang()); ?> });
});
</script>
</body>
</html>
