<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre   = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    $sexo     = mysqli_real_escape_string($link, $_POST['sexo']);
    $correo   = mysqli_real_escape_string($link, $_POST['correo']);
    $clave    = mysqli_real_escape_string($link, $_POST['clave']); // ⚠️ sin hash
    $cedula = mysqli_real_escape_string($link, $_POST['cedula']);

$sql = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, sexo, correo_electronico, clave)
        VALUES ('$nombre', '$apellido', '$cedula', '$telefono', '$sexo', '$correo', '$clave')";

    if (mysqli_query($link, $sql)) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    }
    exit();
}

echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
