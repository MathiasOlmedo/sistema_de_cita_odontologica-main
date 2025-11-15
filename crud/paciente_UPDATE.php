<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_paciente'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $sexo = $_POST['sexo'];
    $correo = $_POST['correo'];
    $cedula = mysqli_real_escape_string($link, $_POST['cedula']);

    $sql = "UPDATE pacientes 
        SET nombre='$nombre', apellido='$apellido', cedula='$cedula', telefono='$telefono', sexo='$sexo', correo_electronico='$correo'
        WHERE id_paciente=$id";

    if (mysqli_query($link, $sql)) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    }
    exit();
}
echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
