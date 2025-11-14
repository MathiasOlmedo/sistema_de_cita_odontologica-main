<?php
include_once("conexionDB.php");
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? '';
$id     = (int)($_POST['id_presupuesto'] ?? 0);
$via    = $_POST['via'] ?? null;
$dest   = $_POST['enviado_a'] ?? null;

if ($accion === 'marcar_enviado' && $id > 0) {
    $now = date('Y-m-d H:i:s');
    $stmt = $link->prepare("
        UPDATE presupuesto 
        SET estado = 'enviado', 
            enviado_at = ?, 
            enviado_via = ?, 
            enviado_a = ? 
        WHERE id_presupuesto = ?
    ");
if ($accion === 'cambiar_estado' && $id > 0) {
    $nuevo = $_POST['nuevo_estado'] ?? 'pendiente';
    $stmt = $link->prepare("UPDATE presupuesto SET estado = ? WHERE id_presupuesto = ?");
    $stmt->bind_param("si", $nuevo, $id);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'ok']);
    } else {
        echo json_encode(['status'=>'error','message'=>'No se pudo cambiar el estado.']);
    }
    $stmt->close();
    exit;
}

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.']);
        exit;
    }

    $stmt->bind_param("sssi", $now, $via, $dest, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
