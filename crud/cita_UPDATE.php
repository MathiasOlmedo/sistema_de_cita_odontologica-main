<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../php/conexionDB.php';  // ruta segura

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "msg"    => "Método no permitido"
    ]);
    exit;
}

$id     = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;
$estado = $_POST['estado'] ?? '';

if ($id <= 0 || $estado === '') {
    echo json_encode([
        "status" => "error",
        "msg"    => "Datos incompletos"
    ]);
    exit;
}

// Solo actualizamos el estado, no tocamos fecha/hora
$stmt = $link->prepare("UPDATE citas SET estado = ? WHERE id_cita = ?");
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg"    => "Error en prepare: " . $link->error
    ]);
    exit;
}

$stmt->bind_param("si", $estado, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode([
        "status" => "error",
        "msg"    => "Error al actualizar: " . $stmt->error
    ]);
}

$stmt->close();
