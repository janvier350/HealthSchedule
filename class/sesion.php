<?php
/**
 * sesion.php — Arranque de sesión con COOKIE PERSISTENTE (24 h).
 *
 * Problema que resuelve: en el hosting la cookie de sesión salía con
 * lifetime=0 (cookie "de sesión"), por lo que al cambiar de app o cerrar el
 * navegador —sobre todo en iPhone/Safari, que descarta la pestaña en segundo
 * plano— la cookie se perdía y al volver la sacaba (tenía que iniciar sesión
 * otra vez), perdiendo la nota no guardada. La configuración .user.ini de 24 h
 * no estaba surtiendo efecto en este servidor LiteSpeed, así que se fija por
 * código, que es garantizado e independiente del hosting.
 *
 * Usar en lugar de session_start().
 */
if (!function_exists('iniciar_sesion_persistente')) {
    function iniciar_sesion_persistente($dur = 86400) {
        if (session_status() === PHP_SESSION_ACTIVE) { return; }

        $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        // La cookie debe quedar persistente (Max-Age) ANTES de iniciar la sesión.
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => $dur,
                'path'     => '/',
                'secure'   => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params($dur, '/', '', $https, true);
        }
        // Que PHP no borre el archivo de sesión por inactividad antes de 24 h.
        @ini_set('session.gc_maxlifetime', (string)$dur);

        session_start();

        // Ventana deslizante: si ya hay sesión iniciada, refrescar la cookie en
        // cada carga para que los 24 h se cuenten desde la última actividad.
        if (isset($_SESSION['rol'])) {
            if (PHP_VERSION_ID >= 70300) {
                @setcookie(session_name(), session_id(), [
                    'expires'  => time() + $dur,
                    'path'     => '/',
                    'secure'   => $https,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } else {
                @setcookie(session_name(), session_id(), time() + $dur, '/', '', $https, true);
            }
        }
    }
}
