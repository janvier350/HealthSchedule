<?php
require_once(__DIR__ . '/../lang/i18n.php');

$rol       = $_SESSION['rol'] ?? '';
$esSistema = ($rol === 'SISTEMA');
$esDoctor  = ($rol === 'DOCTOR');
$esUsuario = ($rol === 'USUARIO');

$paginaActual = basename($_SERVER['PHP_SELF'] ?? '');
function menuActivo($paginas, $actual) {
    $paginas = is_array($paginas) ? $paginas : [$paginas];
    return in_array($actual, $paginas) ? 'mm-active' : '';
}

// URL actual para regresar tras cambiar de idioma
$lang      = current_lang();
$redirLang = $_SERVER['REQUEST_URI'] ?? 'home.php';
?>
<style>
    /* Corrige sidebar sin scroll: garantiza que todos los ítems sean alcanzables */
    .app-sidebar       { height: 100vh !important; display: flex !important; flex-direction: column !important; }
    .scrollbar-sidebar  { flex: 1 1 auto; min-height: 0; overflow-y: auto !important; overflow-x: hidden !important; }
    .app-sidebar__inner { padding-bottom: 12px; }

    .sidebar-logout-footer {
        flex: 0 0 auto;
        border-top: 1px solid rgba(0,0,0,.08);
        padding: 10px 14px;
    }
    .sidebar-logout-footer a.logout-link {
        display: flex; align-items: center; gap: 8px;
        color: #dc3545; font-size: .85rem; font-weight: 600;
        text-decoration: none; padding: 7px 10px; border-radius: 6px;
        transition: background .15s;
    }
    .sidebar-logout-footer a.logout-link:hover { background: rgba(220,53,69,.08); }

    .sidebar-lang { display:flex; align-items:center; gap:6px; margin-bottom:8px; font-size:.8rem; }
    .sidebar-lang .lang-label { color:#6c757d; font-weight:600; }
    .sidebar-lang a {
        text-decoration:none; color:#0d6efd; padding:2px 8px; border-radius:6px; font-weight:600;
        border:1px solid transparent;
    }
    .sidebar-lang a:hover { background:rgba(13,110,253,.08); }
    .sidebar-lang a.active { background:#0d6efd; color:#fff; }
</style>

<div class="app-header__logo">
    <div class="logo-src"></div>
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
<div class="app-header__menu">
    <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
        <span class="btn-icon-wrapper"><i class="fa fa-ellipsis-v fa-w-6"></i></span>
    </button>
</div>

<div class="scrollbar-sidebar">
    <div class="app-sidebar__inner">
        <ul class="vertical-nav-menu">

            <!-- ══ DASHBOARD ══════════════════════════════════════════ -->
            <li class="app-sidebar__heading"><?php te('menu.heading.dashboard'); ?></li>
            <li>
                <a href="home.php" class="<?php echo menuActivo('home.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-house-door"></i> <?php te('menu.home'); ?>
                </a>
            </li>
            <li>
                <a href="perfil.php" class="<?php echo menuActivo('perfil.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-person-circle"></i> <?php te('menu.myProfile'); ?>
                </a>
            </li>

            <!-- ══ AGENDA ══════════════════════════════════════════════ -->
            <li class="app-sidebar__heading"><?php te('menu.heading.schedule'); ?></li>
            <li>
                <a href="SCH_Calendar.php" class="<?php echo menuActivo('SCH_Calendar.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-calendar3"></i> <?php te('menu.calendar'); ?>
                </a>
            </li>
            <li>
                <a href="Agenda_Pendientes.php" class="<?php echo menuActivo('Agenda_Pendientes.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-calendar-check"></i> <?php te('menu.pending'); ?>
                </a>
            </li>
            <?php if ($esSistema || $esDoctor): ?>
            <li>
                <a href="historial_atenciones.php" class="<?php echo menuActivo('historial_atenciones.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-calendar-check"></i> <?php te('menu.attended'); ?>
                </a>
            </li>
            <li>
                <a href="body_weight_planner.php" class="<?php echo menuActivo('body_weight_planner.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-graph-down-arrow"></i> <?php te('menu.weightPlanner'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($esSistema): ?>
            <li>
                <a href="VTA_Concretado.php" class="<?php echo menuActivo('VTA_Concretado.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-x-octagon"></i> <?php te('menu.cancelled'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($esSistema || $esDoctor): ?>
            <li>
                <a href="Enviar_Notificacion.php" class="<?php echo menuActivo('Enviar_Notificacion.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-envelope"></i> <?php te('menu.sendNotification'); ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- ══ PACIENTES ═══════════════════════════════════════════ -->
            <li class="app-sidebar__heading"><?php te('menu.heading.patients'); ?></li>
            <li>
                <a href="#">
                    <i class="metismenu-icon bi bi-person-plus"></i>
                    <?php te('menu.patients'); ?>
                    <i class="metismenu-state-icon bi bi-chevron-down caret-left"></i>
                </a>
                <ul>
                    <li>
                        <a href="listado_pacientes.php" class="<?php echo menuActivo('listado_pacientes.php', $paginaActual); ?>">
                            <i class="metismenu-icon"></i> <?php te('menu.patientList'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="PNC_PacienteCrear.php">
                            <i class="metismenu-icon"></i> <?php te('menu.createPatient'); ?>
                        </a>
                    </li>
                    <?php if ($esSistema || $esDoctor): ?>
                    <li>
                        <a href="visor_plantillas.php">
                            <i class="metismenu-icon"></i> <?php te('menu.templateList'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php if ($esSistema): ?>
            <li>
                <a href="gestionar_documentos.php" class="<?php echo menuActivo('gestionar_documentos.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-file-earmark-text"></i> <?php te('menu.documents'); ?>
                </a>
            </li>
            <li>
                <a href="documentos_enviados.php" class="<?php echo menuActivo('documentos_enviados.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-send-check"></i> <?php te('menu.sentDocuments'); ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- ══ SÓLO SISTEMA ════════════════════════════════════════ -->
            <?php if ($esSistema): ?>
            <li>
                <a href="#">
                    <i class="metismenu-icon bi bi-people"></i>
                    <?php te('menu.doctor'); ?>
                    <i class="metismenu-state-icon bi bi-chevron-down caret-left"></i>
                </a>
                <ul>
                    <li>
                        <a href="PNC_DoctorCrear.php">
                            <i class="metismenu-icon"></i> <?php te('menu.createNew'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#">
                    <i class="metismenu-icon bi bi-file-earmark-text"></i>
                    <?php te('menu.cie10'); ?>
                    <i class="metismenu-state-icon bi bi-chevron-down caret-left"></i>
                </a>
                <ul>
                    <li>
                        <a href="PNC_CIE-10Crear.php">
                            <i class="metismenu-icon"></i> <?php te('menu.createCie10'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="gestionar_tipos_consulta.php" class="<?php echo menuActivo('gestionar_tipos_consulta.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-palette"></i> <?php te('menu.consultTypes'); ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- ══ BILLS (solo SISTEMA) ════════════════════════════════ -->
            <?php if ($esSistema): ?>
            <li class="app-sidebar__heading"><?php te('menu.heading.billing'); ?></li>
            <li>
                <a href="#">
                    <i class="metismenu-icon bi bi-receipt"></i>
                    <?php te('menu.register'); ?>
                    <i class="metismenu-state-icon bi bi-chevron-down caret-left"></i>
                </a>
                <ul>
                    <li>
                        <a href="BILLS_FacturaCrear.php">
                            <i class="metismenu-icon"></i> <?php te('menu.registerBills'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="BILLS_FacturaAbonos.php">
                            <i class="metismenu-icon"></i> <?php te('menu.registerPayments'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="DashBoardReportesCuentasPorCobrar.php">
                            <i class="metismenu-icon"></i> <?php te('menu.reports'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="gestionar_seguros.php" class="<?php echo menuActivo('gestionar_seguros.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-shield-check"></i> <?php te('menu.insurance'); ?>
                </a>
            </li>
            <li>
                <a href="gestionar_tipos_seguro.php" class="<?php echo menuActivo('gestionar_tipos_seguro.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-bookmark-star"></i> <?php te('menu.insuranceTypes'); ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- ══ REPORTES ════════════════════════════════════════════ -->
            <li class="app-sidebar__heading"><?php te('menu.heading.reports'); ?></li>
            <li>
                <a href="RPT_Vendedor_Vta.php" class="<?php echo menuActivo('RPT_Vendedor_Vta.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-display"></i> <?php te('menu.myAppointments'); ?>
                </a>
            </li>
            <?php if ($esSistema || $esDoctor): ?>
            <li>
                <a href="visor_plantillas.php" class="<?php echo menuActivo('visor_plantillas.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-file-earmark-text"></i> <?php te('menu.templates'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($esSistema): ?>
            <li>
                <a href="RPT_General_vta.php" class="<?php echo menuActivo('RPT_General_vta.php', $paginaActual); ?>">
                    <i class="metismenu-icon bi bi-graph-up"></i> <?php te('menu.allAppointments'); ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- ══ PANEL DE CONTROL (solo SISTEMA) ═══════════════════ -->
            <?php if ($esSistema): ?>
            <li class="app-sidebar__heading"><?php te('menu.heading.controlPanel'); ?></li>
            <li>
                <a href="#">
                    <i class="metismenu-icon bi bi-people"></i>
                    <?php te('menu.users'); ?>
                    <i class="metismenu-state-icon bi bi-chevron-down caret-left"></i>
                </a>
                <ul>
                    <li>
                        <a href="PNC_UsuarioCrear.php">
                            <i class="metismenu-icon"></i> <?php te('menu.createNew'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="PNC_UsuarioListado.php">
                            <i class="metismenu-icon"></i> <?php te('menu.list'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

        </ul>
    </div>
</div>

<!-- ══ PIE: idioma + cerrar sesión ══════════════════════════════════ -->
<div class="sidebar-logout-footer">
    <div class="sidebar-lang">
        <span class="lang-label"><i class="bi bi-translate"></i> <?php te('lang.language'); ?>:</span>
        <a href="set_lang.php?lang=en&redir=<?php echo urlencode($redirLang); ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
        <a href="set_lang.php?lang=es&redir=<?php echo urlencode($redirLang); ?>" class="<?php echo $lang === 'es' ? 'active' : ''; ?>">ES</a>
    </div>
    <a href="salir.php" class="logout-link">
        <i class="metismenu-icon bi bi-power"></i> <?php te('menu.logout'); ?>
    </a>
</div>
