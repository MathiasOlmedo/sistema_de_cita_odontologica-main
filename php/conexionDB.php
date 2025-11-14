<?php

include_once('configuracion.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//creando objeto de conexion a base de datos
$link = new mysqli(host, user, password, database);

if ($link->connect_errno) {
    error_log("Database connection error: (" . $link->connect_errno . ") " . $link->connect_error);
    // Redirect to a generic error page without exposing details.
    // Using an absolute path from the web root is most reliable.
    header("Location: /app/error.php");
    exit();
} else {
    $link->set_charset('utf8'); // Corregido: Usar $link en lugar de $conn
}
