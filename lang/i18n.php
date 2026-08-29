<?php
/**
 * lang/i18n.php — Base del sistema bilingüe (EN por defecto / ES).
 *
 * Uso:
 *   require_once(__DIR__ . '/lang/i18n.php');   // desde la raíz
 *   echo t('menu.calendar');                     // devuelve la traducción
 *   te('menu.calendar');                         // imprime escapada (htmlspecialchars)
 *
 * El idioma se resuelve por $_SESSION['lang'] o la cookie 'lang' (no inicia
 * sesión por sí mismo, para no interferir con el orden de session_start()).
 * Cambiar idioma: set_lang.php?lang=en|es&redir=<url>
 */

if (!function_exists('current_lang')) {
    function current_lang() {
        static $lang = null;
        if ($lang !== null) return $lang;
        $l = null;
        if (isset($_SESSION['lang']))      $l = $_SESSION['lang'];
        elseif (isset($_COOKIE['lang']))   $l = $_COOKIE['lang'];
        $lang = in_array($l, array('en', 'es'), true) ? $l : 'en'; // por defecto: inglés
        return $lang;
    }
}

if (!function_exists('t')) {
    function t($key, $default = null) {
        static $dict = null;
        if ($dict === null) {
            $file = __DIR__ . '/' . current_lang() . '.php';
            $dict = file_exists($file) ? include $file : array();
            if (!is_array($dict)) $dict = array();
        }
        if (array_key_exists($key, $dict)) return $dict[$key];
        return ($default !== null) ? $default : $key;
    }
}

if (!function_exists('te')) {
    function te($key, $default = null) {
        echo htmlspecialchars(t($key, $default), ENT_QUOTES, 'UTF-8');
    }
}
