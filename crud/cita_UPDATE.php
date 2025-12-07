<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../php/conexionDB.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "msg" => "Método no permitido"]);
    exit;
}

$id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;

if ($id_cita <= 0) {
    echo json_encode(["status" => "error", "msg" => "ID de cita inválido"]);
    exit;
}

// Obtener datos del formulario
$fecha_cita = $_POST['fecha_cita'] ?? null;
$hora_cita = $_POST['hora_cita'] ?? null;
$id_consultas = isset($_POST['id_consultas']) ? (int)$_POST['id_consultas'] : null;
$id_doctor = isset($_POST['id_doctor']) ? (int)$_POST['id_doctor'] : null;
$estado = $_POST['estado'] ?? 'I';
$descripcion = $_POST['descripcion'] ?? '';
$medicina = $_POST['medicina'] ?? '';

// Validar campos requeridos
if (!$fecha_cita || !$hora_cita || !$id_consultas || !$id_doctor) {
    echo json_encode(["status" => "error", "msg" => "Faltan datos obligatorios"]);
    exit;
}

// Actualizar cita
$stmt = $link->prepare("
    UPDATE citas 
    SET fecha_cita = ?, 
        hora_cita = ?, 
        id_consultas = ?, 
        id_doctor = ?, 
        estado = ? 
    WHERE id_cita = ?
");

if (!$stmt) {
    echo json_encode(["status" => "error", "msg" => "Error en prepare: " . $link->error]);
    exit;
}

$stmt->bind_param("ssiisi", $fecha_cita, $hora_cita, $id_consultas, $id_doctor, $estado, $id_cita);

if ($stmt->execute()) {
    // Si el estado es "Realizada" (A), guardar diagnóstico
    if ($estado === 'A' && !empty($descripcion)) {
        // Verificar si ya existe diagnóstico
        $checkDiag = $link->prepare("SELECT id FROM paciente_diagnostico WHERE id_cita = ?");
        $checkDiag->bind_param('i', $id_cita);
        $checkDiag->execute();
        $existeDiag = $checkDiag->get_result()->num_rows > 0;
        $checkDiag->close();

        if ($existeDiag) {
            // Actualizar diagnóstico existente
            $diagStmt = $link->prepare("UPDATE paciente_diagnostico SET descripcion = ?, medicina = ? WHERE id_cita = ?");
            $diagStmt->bind_param('ssi', $descripcion, $medicina, $id_cita);
            $diagStmt->execute();
            $diagStmt->close();
        } else {
            // Insertar nuevo diagnóstico
            $diagStmt = $link->prepare("INSERT INTO paciente_diagnostico (id_cita, descripcion, medicina) VALUES (?, ?, ?)");
            $diagStmt->bind_param('iss', $id_cita, $descripcion, $medicina);
            $diagStmt->execute();
            $diagStmt->close();
        }
    }

    echo json_encode(["status" => "success", "msg" => "Cita actualizada correctamente"]);
} else {
    echo json_encode(["status" => "error", "msg" => "Error al actualizar: " . $stmt->error]);
}

$stmt->close();
?>