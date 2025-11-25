<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id       = intval($_POST['id_doctor']);
    $nombre   = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    $sexo     = mysqli_real_escape_string($link, $_POST['sexo']);
    $correo   = mysqli_real_escape_string($link, $_POST['correo']);
    $clave    = mysqli_real_escape_string($link, $_POST['clave']);
    $especialidad = intval($_POST['especialidad']);
    $fecha    = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : "2000-01-01";

    $sql = "UPDATE doctor 
            SET nombreD='$nombre', apellido='$apellido', sexo='$sexo',
                fecha_nacimiento='$fecha', telefono='$telefono',
                correo_eletronico='$correo', clave='$clave', id_especialidad=$especialidad
            WHERE id_doctor=$id";

    if (mysqli_query($link, $sql)) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    }
    exit();
}

echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
