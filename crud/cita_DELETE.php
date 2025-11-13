<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_cita'];

    $sql = "DELETE FROM citas WHERE id_cita=$id";
    if (mysqli_query($link, $sql)) echo json_encode(["status"=>"success"]);
    else echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    exit();
}
echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
