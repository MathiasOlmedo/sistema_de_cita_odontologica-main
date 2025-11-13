<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = $_POST['id_cita'];
    $fecha  = $_POST['fecha_cita'];
    $hora   = $_POST['hora_cita'];
    $estado = $_POST['estado'];

    $sql = "UPDATE citas 
            SET fecha_cita='$fecha', hora_cita='$hora', estado='$estado'
            WHERE id_cita=$id";

    if (mysqli_query($link, $sql)) echo json_encode(["status"=>"success"]);
    else echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    exit();
}
echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
