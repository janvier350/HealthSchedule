<?php
/**
 * class/tickets.php — Módulo de Solicitudes / Tickets.
 *
 * Centraliza requerimientos (errores, correcciones, mejoras, preguntas) que
 * antes llegaban por WhatsApp. Cualquier usuario con sesión puede crear y ver
 * sus propios tickets; quien tenga el permiso 'panel.tickets' (SISTEMA y la
 * Dra. por defecto) ve y gestiona todos.
 *
 * Patrón self-healing: las tablas se crean al primer uso (no depende de correr
 * la migración). Tolerante a fallos.
 */

if (!function_exists('tickets_ensure_tablas')) {
    /** Crea las tablas del módulo si no existen. Devuelve true si están listas. */
    function tickets_ensure_tablas($conexion) {
        if (!$conexion) return false;
        static $ready = false;
        if ($ready) return true;
        @$conexion->query("CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(160) NOT NULL,
            descripcion TEXT NULL,
            modulo VARCHAR(40) NOT NULL DEFAULT 'Otro',
            tipo VARCHAR(20) NOT NULL DEFAULT 'Error',
            prioridad VARCHAR(10) NOT NULL DEFAULT 'Media',
            estado VARCHAR(20) NOT NULL DEFAULT 'Abierto',
            id_solicitante INT NULL,
            solicitante VARCHAR(160) NULL,
            rol_solicitante VARCHAR(40) NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion DATETIME NULL,
            fecha_cierre DATETIME NULL,
            INDEX idx_estado (estado),
            INDEX idx_sol (id_solicitante),
            INDEX idx_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @$conexion->query("CREATE TABLE IF NOT EXISTS ticket_comentarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_ticket INT NOT NULL,
            id_usuario INT NULL,
            usuario VARCHAR(160) NULL,
            rol VARCHAR(40) NULL,
            comentario TEXT NOT NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (id_ticket)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        @$conexion->query("CREATE TABLE IF NOT EXISTS ticket_adjuntos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_ticket INT NOT NULL,
            id_comentario INT NULL,
            nombre_original VARCHAR(255) NULL,
            archivo VARCHAR(200) NOT NULL,
            mime VARCHAR(100) NULL,
            tamano INT NULL,
            id_usuario INT NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (id_ticket)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ready = true;
        return true;
    }
}

if (!function_exists('tickets_catalogos')) {
    /** Valores canónicos (internos, en español) de cada campo de selección. */
    function tickets_catalogos() {
        return [
            'modulo'    => ['Agenda','Pacientes','Informes','Documentos','Facturación','ICD-10','Sesión/Acceso','Otro'],
            'tipo'      => ['Error','Corrección','Mejora','Pregunta'],
            'prioridad' => ['Baja','Media','Alta'],
            'estado'    => ['Abierto','En progreso','Resuelto','Cerrado'],
        ];
    }
}

if (!function_exists('tickets_puede_gestionar')) {
    /** ¿El usuario actual gestiona TODOS los tickets? (SISTEMA y Dra. por defecto) */
    function tickets_puede_gestionar() {
        return function_exists('puede') ? puede('panel.tickets') : false;
    }
}

if (!function_exists('tickets_badge_estado')) {
    function tickets_badge_estado($estado, $en = false) {
        $map = [
            'Abierto'      => ['danger',    $en?'Open':'Abierto'],
            'En progreso'  => ['warning',   $en?'In progress':'En progreso'],
            'Resuelto'     => ['success',   $en?'Resolved':'Resuelto'],
            'Cerrado'      => ['secondary', $en?'Closed':'Cerrado'],
        ];
        $m = $map[$estado] ?? ['secondary', htmlspecialchars($estado)];
        return '<span class="badge bg-'.$m[0].'">'.htmlspecialchars($m[1]).'</span>';
    }
}

if (!function_exists('tickets_badge_prioridad')) {
    function tickets_badge_prioridad($prioridad, $en = false) {
        $map = [
            'Alta'  => ['danger',  $en?'High':'Alta'],
            'Media' => ['info',    $en?'Medium':'Media'],
            'Baja'  => ['light text-dark border', $en?'Low':'Baja'],
        ];
        $m = $map[$prioridad] ?? ['secondary', htmlspecialchars($prioridad)];
        return '<span class="badge bg-'.$m[0].'">'.htmlspecialchars($m[1]).'</span>';
    }
}

if (!function_exists('tickets_label_tipo')) {
    function tickets_label_tipo($tipo, $en = false) {
        $map = [
            'Error'      => $en?'Bug':'Error',
            'Corrección' => $en?'Fix':'Corrección',
            'Mejora'     => $en?'Improvement':'Mejora',
            'Pregunta'   => $en?'Question':'Pregunta',
        ];
        return $map[$tipo] ?? $tipo;
    }
}

if (!function_exists('tickets_label_modulo')) {
    function tickets_label_modulo($modulo, $en = false) {
        if (!$en) return $modulo;
        $map = [
            'Agenda'=>'Schedule','Pacientes'=>'Patients','Informes'=>'Reports',
            'Documentos'=>'Documents','Facturación'=>'Billing','ICD-10'=>'ICD-10',
            'Sesión/Acceso'=>'Session/Access','Otro'=>'Other',
        ];
        return $map[$modulo] ?? $modulo;
    }
}

if (!function_exists('tickets_guardar_adjunto')) {
    /**
     * Guarda un archivo subido ($_FILES entry) como adjunto de un ticket.
     * Valida tipo real (imágenes + PDF), tamaño (10MB) y nombre aleatorio.
     * Devuelve el id del adjunto o null si falla/omite.
     */
    function tickets_guardar_adjunto($conexion, $file, $idTicket, $idComentario = null) {
        if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
        if ($file['size'] > 10 * 1024 * 1024) return null;
        $permitidos = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
        ];
        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
        }
        if (!isset($permitidos[$mime])) return null;
        $ext = $permitidos[$mime];

        $dir = dirname(__DIR__) . '/tickets_adjuntos';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) return null;
        // Blindaje de la carpeta (por si no se subió el .htaccess).
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        $indexp = $dir . '/index.php';
        if (!is_file($indexp)) @file_put_contents($indexp, "<?php http_response_code(403); exit('Forbidden');");

        try { $rand = bin2hex(random_bytes(8)); } catch (Exception $e) { $rand = substr(md5(uniqid('', true)), 0, 16); }
        $almacen = 'tk_' . (int)$idTicket . '_' . time() . '_' . $rand . '.' . $ext;
        $destAbs = $dir . '/' . $almacen;
        if (!move_uploaded_file($file['tmp_name'], $destAbs)) return null;
        @chmod($destAbs, 0640);

        $nombreOrig = function_exists('mb_substr') ? mb_substr((string)($file['name'] ?? $almacen), 0, 255) : substr((string)($file['name'] ?? $almacen), 0, 255);
        $tam    = (int)$file['size'];
        $idUser = (int)($_SESSION['iduser'] ?? 0);
        $ic     = $idComentario !== null ? (int)$idComentario : null;

        $ins = $conexion->prepare(
            "INSERT INTO ticket_adjuntos (id_ticket, id_comentario, nombre_original, archivo, mime, tamano, id_usuario)
             VALUES (?,?,?,?,?,?,?)"
        );
        if (!$ins) { @unlink($destAbs); return null; }
        // tipos: id_ticket(i) id_comentario(i) nombre_original(s) archivo(s) mime(s) tamano(i) id_usuario(i)
        $ins->bind_param('iisssii', $idTicket, $ic, $nombreOrig, $almacen, $mime, $tam, $idUser);
        if (!$ins->execute()) { $ins->close(); @unlink($destAbs); return null; }
        $nuevoId = (int)$conexion->insert_id;
        $ins->close();
        return $nuevoId;
    }
}
