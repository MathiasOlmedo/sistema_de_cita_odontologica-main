<?php
header('Content-Type: application/json');
include_once("conexionDB.php");
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id_doctor'])) {
    echo json_encode(['status'=>'error','message'=>'No hay doctor identificado']);
    exit;
}

$id_doctor = (int)$_SESSION['id_doctor'];
$procedimientos = json_decode($_POST['procedimientos'] ?? '', true);

if (!$procedimientos || !is_array($procedimientos)){
    echo json_encode(['status'=>'error','message'=>'Datos incompletos.']);
    exit;
}

// Insertar cada procedimiento
$stmt = mysqli_prepare($link, "INSERT INTO resumen_procedimientos (id_doctor,diente,lado,procedimiento,precio,observacion) VALUES (?,?,?,?,?,?)");

foreach($procedimientos as $p){
    mysqli_stmt_bind_param($stmt, "iissds", $id_doctor, $p['diente'], $p['lado'], $p['procedimiento'], $p['precio'], $p['observacion']);
    mysqli_stmt_execute($stmt);
}

mysqli_stmt_close($stmt);
echo json_encode(['status'=>'ok','message'=>'Resumen guardado correctamente']);
