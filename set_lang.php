<?php
/**
 * set_lang.php — Cambia el idioma de la interfaz (en|es) y regresa a la página.
 * Guarda la preferencia en sesión y en una cookie (1 año).
 */
session_start();

$lang = (isset($_GET['lang']) && in_array($_GET['lang'], array('en', 'es'), true)) ? $_GET['lang'] : 'en';
$_SESSION['lang'] = $lang;
setcookie('lang', $lang, time() + 60 * 60 * 24 * 365, '/');

// Redirección segura: solo rutas locales relativas
$redir = isset($_GET['redir']) ? $_GET['redir'] : 'home.php';
if (preg_match('#^https?://#i', $redir) || strpos($redir, '..') !== false
    || !preg_match('#^[A-Za-z0-9_\-./?=&%]+$#', $redir)) {
    $redir = 'home.php';
}

header('Location: ' . $redir);
exit;
