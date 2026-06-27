<?php
function conectarse()
{
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