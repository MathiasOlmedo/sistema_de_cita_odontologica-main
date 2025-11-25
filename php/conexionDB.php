<?php

include_once('configuracion.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//creando objeto de conexion a base de datos
$link = new mysqli(host, user, password, database);

if ($link->connect_errno) { // Corregido: Usar $link->connect_errno en lugar de mysqli_connect_errno()
    $_SESSION['MensajeTexto'] = "El sistema está en mantenimiento, intente más tarde";
    $_SESSION['MensajeTipo'] = "bg-warning text-dark";
} else {
    $link->set_charset('utf8'); // Corregido: Usar $link en lugar de $conn
}
