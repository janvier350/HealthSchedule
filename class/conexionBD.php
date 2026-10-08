<?php
function conectarse()
{
    // Esta aplicación está escrita para el modo clásico de mysqli: cada consulta
    // devuelve false ante un error y el código lo comprueba (o usa "or die").
    // En PHP 8.1+ el modo por defecto lanza excepciones, lo que convertía
    // cualquier error de consulta en un HTTP 500. Restauramos el modo clásico.
    if (function_exists('mysqli_report')) { mysqli_report(MYSQLI_REPORT_OFF); }

    $db_host   = "localhost";
    $db_nombre = "srossnut_agenda";
    $db_user   = "srossnut_agenda";
    $db_pass   = "nAGTDbMpym6nNv9aedHJ";

    $link = mysqli_connect($db_host, $db_user, $db_pass);
    mysqli_select_db($link, $db_nombre) or die("Error seleccionando la base de datos.");
    mysqli_set_charset($link, 'utf8mb4');   // ← línea correcta, procedural

    // Zona horaria única de la aplicación (hora de la clínica). Se usa la misma
    // en PHP y en MySQL para que las horas (bitácora, "creado el", etc.) sean
    // consistentes sin importar la zona del servidor de hosting.
    // NOTA: si la clínica opera en otra zona, cambiar sólo esta constante
    // (p. ej. 'America/Los_Angeles' para la costa oeste, 'America/Chicago' centro).
    if (!defined('APP_TZ')) define('APP_TZ', 'America/New_York');
    @date_default_timezone_set(APP_TZ);
    try {
        // Offset actual con horario de verano correcto (p. ej. -04:00 / -05:00).
        $off = (new DateTime('now', new DateTimeZone(APP_TZ)))->format('P');
        @mysqli_query($link, "SET time_zone = '$off'");
    } catch (Exception $e) { /* si falla, se mantiene la zona del servidor */ }

    return $link;
}
?>