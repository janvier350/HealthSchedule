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

    return $link;
}
?>