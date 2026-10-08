<?php
/**
 * class/auditoria.php
 * Bitácora de cambios (auditoría). Incluir donde se registran cambios:
 *   require_once(__DIR__ . '/auditoria.php');   // desde class/
 *   require_once(__DIR__ . '/class/auditoria.php'); // desde la raíz
 * y llamar:
 *   auditar($conexion, 'Pacientes', 'editar', 'AG_PACIENTE', $id, 'Detalle opcional');
 *
 * Acciones sugeridas: 'crear' | 'editar' | 'eliminar' | 'cancelar' | 'restaurar'
 *                     | 'reagendar' | 'atender' | 'estado'
 * Es tolerante a fallos: nunca interrumpe la operación principal.
 * Crea la tabla `auditoria` automáticamente si no existe (self-healing).
 */
if (!function_exists('auditar_dispositivo')) {
    /** Deduce el tipo de dispositivo a partir del User-Agent. */
    function auditar_dispositivo($ua) {
        $ua = (string)$ua;
        if ($ua === '') return '';
        if (preg_match('/iPad|Tablet|Nexus 7|SM-T|Kindle|PlayBook/i', $ua)) return 'Tablet';
        if (preg_match('/Mobi|Android|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini|IEMobile/i', $ua)) return 'Teléfono';
        return 'Computadora';
    }
}

if (!function_exists('auditar')) {
    function auditar($conexion, $modulo, $accion, $entidad = '', $entidad_id = null, $detalle = '') {
        if (!$conexion) return;
        static $ready = false;
        if (!$ready) {
            @$conexion->query("CREATE TABLE IF NOT EXISTS auditoria (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                id_usuario INT NULL,
                usuario VARCHAR(100) NULL,
                nombre VARCHAR(160) NULL,
                rol VARCHAR(40) NULL,
                modulo VARCHAR(60) NOT NULL,
                accion VARCHAR(30) NOT NULL,
                entidad VARCHAR(60) NULL,
                entidad_id VARCHAR(40) NULL,
                detalle VARCHAR(500) NULL,
                ip VARCHAR(45) NULL,
                dispositivo VARCHAR(20) NULL,
                user_agent VARCHAR(255) NULL,
                INDEX idx_fecha (fecha),
                INDEX idx_user (id_usuario),
                INDEX idx_modulo (modulo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // Auto-provisión de columnas nuevas en instalaciones existentes.
            $dbR = @$conexion->query("SELECT DATABASE() AS db");
            $db  = $dbR ? ($dbR->fetch_assoc()['db'] ?? '') : '';
            if ($db !== '') {
                $dbE = $conexion->real_escape_string($db);
                $colExiste = function($c) use ($conexion, $dbE) {
                    $q = @$conexion->query("SELECT COUNT(*) n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbE' AND TABLE_NAME='auditoria' AND COLUMN_NAME='$c'");
                    return $q ? ((int)$q->fetch_assoc()['n'] > 0) : true;
                };
                if (!$colExiste('dispositivo')) @$conexion->query("ALTER TABLE auditoria ADD COLUMN dispositivo VARCHAR(20) NULL");
                if (!$colExiste('user_agent'))  @$conexion->query("ALTER TABLE auditoria ADD COLUMN user_agent VARCHAR(255) NULL");
            }
            $ready = true;
        }
        $idu = isset($_SESSION['iduser'])   ? (int)$_SESSION['iduser']       : null;
        $usr = isset($_SESSION['username']) ? (string)$_SESSION['username']  : '';
        $rol = isset($_SESSION['rol'])      ? (string)$_SESSION['rol']       : '';
        $nom = trim((($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? '')));
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
        $dev = auditar_dispositivo($ua);
        $eid = ($entidad_id === null || $entidad_id === '') ? null : (string)$entidad_id;
        $det = function_exists('mb_substr') ? mb_substr((string)$detalle, 0, 500) : substr((string)$detalle, 0, 500);

        $stmt = @$conexion->prepare(
            "INSERT INTO auditoria (id_usuario, usuario, nombre, rol, modulo, accion, entidad, entidad_id, detalle, ip, dispositivo, user_agent)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        if (!$stmt) return;
        $stmt->bind_param('isssssssssss', $idu, $usr, $nom, $rol, $modulo, $accion, $entidad, $eid, $det, $ip, $dev, $ua);
        @$stmt->execute();
        $stmt->close();
    }
}
