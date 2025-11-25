<?php
include_once('../php/conexionDB.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id_paciente']);

    // Verificar si tiene citas asociadas
    $check = mysqli_query($link, "SELECT COUNT(*) AS total FROM citas WHERE id_paciente=$id");
    $row = mysqli_fetch_assoc($check);
    if ($row['total'] > 0) {
        echo json_encode(["status"=>"error","msg"=>"❌ No se puede eliminar: el paciente tiene citas registradas."]);
        exit();
    }

    $sql = "DELETE FROM pacientes WHERE id_paciente=$id";
    if (mysqli_query($link, $sql)) {
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","msg"=>mysqli_error($link)]);
    }
    exit();
}

echo json_encode(["status"=>"error","msg"=>"Petición inválida"]);
