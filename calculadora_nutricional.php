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
$rol = strtoupper($_SESSION['rol']);
if (!in_array($rol, ['SISTEMA', 'DOCTOR'], true)) {
    die('<p style="color:red;font-family:sans-serif;padding:2rem;">'.htmlspecialchars(t('common.accessRestricted')).'</p>');
}
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
