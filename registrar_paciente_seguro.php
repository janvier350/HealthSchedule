<?php
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
require_once(__DIR__ . "/lang/i18n.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"])) {
    header("Location: break.php");
    exit();
}

// Lista de aseguradoras para los selects (se reutiliza en cada fila)
$seguros = [];
$rSeg = $conexion->query("SELECT Id_seguro, Empresa_seguro FROM seguros WHERE estado = 1 ORDER BY Empresa_seguro");
if ($rSeg) { while ($s = $rSeg->fetch_assoc()) { $seguros[] = $s; } }

// Catálogo ICD-10
$icd10 = [];
$rIcd = $conexion->query("SELECT ID_ENFE_DIAG_COD, CODIGO, DESCRIPCION FROM ENFE_DIAG_COD ORDER BY CODIGO");
if ($rIcd) { while ($ic = $rIcd->fetch_assoc()) { $icd10[] = $ic; } }

$okMsg  = (isset($_GET['ok']) && $_GET['ok'] === '1');
$okNom  = trim($_GET['nom'] ?? '');
$okSeg  = isset($_GET['seg']) && ctype_digit((string)$_GET['seg']) ? (int)$_GET['seg'] : 0;
$errMsg = trim($_GET['err'] ?? '');
?>
<!doctype html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="alternate icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <meta http-equiv="Content-Language" content="<?php echo current_lang(); ?>">
    <title><?php te('rps.pageTitle'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="./main.css" rel="stylesheet">
    <style>
        .seg-card { border:1px solid #e6e9f0; border-radius:10px; }
        .seg-card .seg-head { background:#f6f8fc; border-bottom:1px solid #e6e9f0; padding:8px 12px; border-radius:10px 10px 0 0; }
        .foto-preview { max-height:120px; border:1px solid #dee2e6; border-radius:8px; margin-top:6px; display:none; }
        .foto-box label { font-size:.8rem; font-weight:600; color:#33475b; }
        /* select2 a la altura de los inputs de Bootstrap 5 */
        .select2-container .select2-selection--single { height: calc(2.375rem + 2px); display:flex; align-items:center; }
        .select2-container--default .select2-selection--single { border:1px solid #ced4da; border-radius:.375rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:1.5; padding-left:.75rem; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(2.375rem); }
        .select2-container { width: 100% !important; }
        .form-label { margin-bottom:.25rem; font-size:.85rem; color:#33475b; }
    </style>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    <div class="app-header header-shadow">
        <div class="app-header__logo">
            <div class="logo-src"></div>
            <div class="header__pane ml-auto">
                <div>
                    <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                        <span class="hamburger-box"><span class="hamburger-inner"></span></span>
                    </button>
                </div>
            </div>
        </div>
        <div class="app-header__mobile-menu">
            <div>
                <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                    <span class="hamburger-box"><span class="hamburger-inner"></span></span>
                </button>
            </div>
        </div>
        <div class="app-header__menu">
            <span>
                <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                    <span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v fa-w-6"></i></span>
                </button>
            </span>
        </div>
        <div class="app-header__content">
            <div class="app-header-left"></div>
            <div class="app-header-right">
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div class="btn-group">
                                    <a data-toggle="dropdown" class="p-0 btn">
                                        <img width="42" class="rounded-circle">
                                        <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                    </a>
                                    <div tabindex="-1" role="menu" class="dropdown-menu dropdown-menu-right">
                                        <a href="perfil.php" class="dropdown-item"><?php te('hdr.userProfile'); ?></a>
                                        <div class="dropdown-divider"></div>
                                        <a href="salir.php" class="dropdown-item"><?php te('menu.logout'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-main">
        <div class="app-sidebar sidebar-shadow">
            <?php include("./menu/menu_adm.php"); ?>
        </div>
        <div class="app-main__outer">
            <div class="app-main__inner">
                <div class="app-page-title">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon">
                                <i class="pe-7s-add-user icon-gradient bg-warm-flame"></i>
                            </div>
                            <div><?php te('rps.title'); ?>
                                <div class="page-title-subheading"><?php te('rps.subtitle'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($okMsg): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i>
                        <?php te('pcreate.createdOk'); ?>: <strong><?php echo htmlspecialchars($okNom); ?></strong>
                        <?php if ($okSeg > 0): ?> — <?php echo (int)$okSeg; ?> <?php te('rps.insurancesSaved'); ?><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($errMsg !== ''): ?>
                    <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errMsg); ?></div>
                <?php endif; ?>

                <form method="post" action="registrar_paciente_seguro_guardar.php" enctype="multipart/form-data" class="needs-validation" novalidate>

                    <!-- ═══ Datos del paciente ═══ -->
                    <div class="main-card mb-3 card">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="fa fa-user"></i> <?php te('pcreate.info'); ?></h5>

                            <!-- Fila 1: nombres/apellidos anchos -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold"><?php te('pf.firstName'); ?> *</label>
                                    <input type="text" class="form-control" name="nombres" required>
                                    <div class="invalid-feedback"><?php te('pf.firstName'); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold"><?php te('pf.lastName'); ?> *</label>
                                    <input type="text" class="form-control" name="apellidos" required>
                                    <div class="invalid-feedback"><?php te('pf.lastName'); ?></div>
                                </div>
                            </div>

                            <!-- Fila 2: email ancho + phone + dob -->
                            <div class="row g-3 mt-0">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold"><?php te('pf.email'); ?></label>
                                    <input type="email" class="form-control" name="email" placeholder="name@mail.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pf.phone'); ?> *</label>
                                    <input type="text" class="form-control" name="telefono" required>
                                    <div class="invalid-feedback"><?php te('pf.phone'); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pf.dob'); ?></label>
                                    <input type="date" name="feNac" class="form-control">
                                </div>
                            </div>

                            <!-- Fila 3: ID + Title + Sex + Gender -->
                            <div class="row g-3 mt-0">
                                <div class="col-md-3">
                                    <label class="form-label">ID</label>
                                    <input type="text" class="form-control" name="cedula" placeholder="09923006589">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pcreate.title'); ?></label>
                                    <select name="title" class="form-select js-select2">
                                        <option value="">Default Select</option>
                                        <option value="Dr">Dr</option><option value="Fr">Fr</option>
                                        <option value="Master">Master</option><option value="Miss">Miss</option>
                                        <option value="Mr">Mr</option><option value="Mrs">Mrs</option>
                                        <option value="Ms">Ms</option><option value="Mx">Mx</option>
                                        <option value="Pr">Pr</option><option value="Prof">Prof</option><option value="Rev">Rev</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pf.sex'); ?></label>
                                    <select name="sex" class="form-select js-select2">
                                        <option value="N/A">Default Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pcreate.genderIdentity'); ?></label>
                                    <select name="gender" class="form-select js-select2">
                                        <option value="Default Select">Default Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Transgender man/trans man/female-to-male(FTM)">Transgender man (FTM)</option>
                                        <option value="Transgender woman/trans woman/male-to-female(MTF)">Transgender woman (MTF)</option>
                                        <option value="Genderqueer/gender nonconforming">Genderqueer / nonconforming</option>
                                        <option value="Decline to answer">Decline to answer</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Fila 4: address + language + important notes -->
                            <div class="row g-3 mt-0">
                                <div class="col-md-6">
                                    <label class="form-label"><?php te('pf.address'); ?></label>
                                    <input type="text" class="form-control" name="address">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pf.language'); ?></label>
                                    <select name="idioma" class="form-select js-select2">
                                        <option value="es"><?php te('lang.spanish'); ?></option>
                                        <option value="en"><?php te('lang.english'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php te('pf.importantNotes'); ?></label>
                                    <input type="text" class="form-control" name="notes">
                                </div>
                            </div>

                            <!-- Fila 5: billing + ICD-10 -->
                            <div class="row g-3 mt-0">
                                <div class="col-md-4">
                                    <label class="form-label"><?php te('pf.billingNotes'); ?></label>
                                    <input type="text" class="form-control" name="addNotes">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label"><i class="bi bi-clipboard2-pulse"></i> ICD-10</label>
                                    <select name="idicd10" class="form-select js-select2-search">
                                        <option value="">—</option>
                                        <?php foreach ($icd10 as $ic): ?>
                                            <option value="<?php echo (int)$ic['ID_ENFE_DIAG_COD']; ?>">
                                                <?php echo htmlspecialchars($ic['CODIGO'] . ' — ' . $ic['DESCRIPCION']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══ Seguros (dinámico, con fotos) ═══ -->
                    <div class="main-card mb-3 card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">🛡️ <?php te('pcreate.insurance'); ?></h5>
                                <button type="button" class="btn btn-sm btn-success" onclick="agregarFilaSeguro()">
                                    <i class="fa fa-plus"></i> <?php te('rps.addInsurance'); ?>
                                </button>
                            </div>
                            <p class="text-muted small"><?php te('rps.insuranceHint'); ?></p>
                            <div id="segurosContenedor"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button class="btn btn-primary btn-lg" type="submit"><i class="fa fa-save"></i> <?php te('pcreate.save'); ?></button>
                        <a href="listado_pacientes.php" class="btn btn-link"><?php te('pcreate.tab.list'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Plantilla de una fila de seguro (se clona por JS) -->
<template id="tplSeguro">
    <div class="seg-card mb-3" data-seg-row>
        <div class="seg-head d-flex justify-content-between align-items-center">
            <strong><i class="fa fa-shield-alt"></i> <span data-seg-num></span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarFilaSeguro(this)">
                <i class="fa fa-trash"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label small"><?php te('pcreate.insurer'); ?></label>
                    <select name="seg_id[]" class="form-select js-seg-insurer">
                        <option value=""><?php te('pcreate.selectDash'); ?></option>
                        <?php foreach ($seguros as $s): ?>
                            <option value="<?php echo (int)$s['Id_seguro']; ?>"><?php echo htmlspecialchars($s['Empresa_seguro']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label small"><?php te('pcreate.policyNo'); ?></label>
                    <input type="text" name="seg_poliza[]" class="form-control" maxlength="60">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label small"><?php te('pcreate.priority'); ?></label>
                    <select name="seg_prioridad[]" class="form-select js-seg-priority">
                        <option value="Primario"><?php te('pcreate.priorityPrimary'); ?></option>
                        <option value="Secundario"><?php te('pcreate.prioritySecondary'); ?></option>
                        <option value="Terciario"><?php te('pcreate.priorityTertiary'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2 foto-box">
                    <label><i class="fa fa-image"></i> <?php te('rps.frontPhoto'); ?></label>
                    <input type="file" name="seg_frente[]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" onchange="previsualizar(this)">
                    <img class="foto-preview" alt="frente">
                </div>
                <div class="col-md-6 mb-2 foto-box">
                    <label><i class="fa fa-image"></i> <?php te('rps.backPhoto'); ?></label>
                    <input type="file" name="seg_reverso[]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" onchange="previsualizar(this)">
                    <img class="foto-preview" alt="reverso">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    // Inicializa select2 en un contenedor (o en todo el documento por defecto).
    function initSelect2(scope) {
        var $scope = scope ? $(scope) : $(document);
        // Selects normales con búsqueda
        $scope.find('.js-select2').each(function(){
            if ($(this).data('select2')) return;
            $(this).select2({ width: '100%' });
        });
        // Selects con lista larga (ICD-10, aseguradora)
        $scope.find('.js-select2-search, .js-seg-insurer').each(function(){
            if ($(this).data('select2')) return;
            $(this).select2({ width: '100%', placeholder: <?php echo json_encode(t('pcreate.selectDash')); ?>, allowClear: true });
        });
        // Prioridad del seguro
        $scope.find('.js-seg-priority').each(function(){
            if ($(this).data('select2')) return;
            $(this).select2({ width: '100%' });
        });
    }

    // Cada fila mantiene su índice de nombre estable por posición del array.
    function agregarFilaSeguro() {
        const tpl  = document.getElementById('tplSeguro');
        const cont = document.getElementById('segurosContenedor');
        const node = tpl.content.cloneNode(true);
        cont.appendChild(node);
        // La última fila agregada es el último hijo del contenedor
        const nuevaFila = cont.lastElementChild;
        renumerar();
        initSelect2(nuevaFila);
    }
    function quitarFilaSeguro(btn) {
        const row = btn.closest('[data-seg-row]');
        if (row) row.remove();
        renumerar();
    }
    function renumerar() {
        const label = <?php echo json_encode(t('rps.insuranceN')); ?>;
        document.querySelectorAll('#segurosContenedor [data-seg-row]').forEach(function(row, i){
            row.querySelector('[data-seg-num]').textContent = label + ' ' + (i + 1);
        });
    }
    function previsualizar(input) {
        const img = input.parentElement.querySelector('.foto-preview');
        if (input.files && input.files[0]) {
            img.src = URL.createObjectURL(input.files[0]);
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
        }
    }
    // Al cargar: inicializar select2 del paciente y una fila de seguro
    document.addEventListener('DOMContentLoaded', function(){
        initSelect2(document);   // selects del paciente
        agregarFilaSeguro();     // primera fila de seguro (ya inicializa su select2)
    });

    // Validación Bootstrap
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            Array.prototype.filter.call(document.getElementsByClassName('needs-validation'), function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
</body>
</html>
