<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre   = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    $sexo     = mysqli_real_escape_string($link, $_POST['sexo']);
    $correo   = mysqli_real_escape_string($link, $_POST['correo']);
    $clave    = mysqli_real_escape_string($link, $_POST['clave']);
    $especialidad = intval($_POST['especialidad']);
    $fecha    = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : "2000-01-01";

    $sql = "INSERT INTO doctor (nombreD, apellido, sexo, fecha_nacimiento, telefono, correo_eletronico, clave, id_especialidad)
            VALUES ('$nombre', '$apellido', '$sexo', '$fecha', '$telefono', '$correo', '$clave', $especialidad)";

    if (mysqli_query($link, $sql)) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    }
    exit();
}

echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
