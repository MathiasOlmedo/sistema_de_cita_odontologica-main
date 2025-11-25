<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $paciente = $_POST['id_paciente'];
    $doctor   = $_POST['id_doctor'];
    $fecha    = $_POST['fecha_cita'];
    $hora     = $_POST['hora_cita'];
    $estado   = $_POST['estado'];

    $id_consultas = $_POST['id_consultas'];  // VIENE DEL FORMULARIO

$sql = "INSERT INTO citas (id_paciente, id_doctor, fecha_cita, hora_cita, id_consultas, estado)
        VALUES ('$paciente', '$doctor', '$fecha', '$hora', '$id_consultas', '$estado')";


    if (mysqli_query($link, $sql)) echo json_encode(["status"=>"success"]);
    else echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    exit();
}
echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
