<?php
include_once("conexionDB.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Forzar salida JSON siempre
header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? '';
$id     = (int)($_POST['id_presupuesto'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de presupuesto inválido.']);
    exit;
}

try {

    // --- Acción: marcar como enviado ---
    if ($accion === 'marcar_enviado') {
        $via  = $_POST['via'] ?? 'whatsapp';
        $dest = $_POST['enviado_a'] ?? '';
        $now  = date('Y-m-d H:i:s');

        $stmt = $link->prepare("
            UPDATE presupuesto 
            SET estado = 'enviado', 
                enviado_at = ?, 
                enviado_via = ?, 
                enviado_a = ?
            WHERE id_presupuesto = ?
        ");
        if (!$stmt) throw new Exception('Error preparando consulta.');

        $stmt->bind_param("sssi", $now, $via, $dest, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'message' => 'Presupuesto marcado como enviado.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado.']);
        }
        $stmt->close();
        exit;
    }

    // --- Acción: cambiar estado manualmente ---
    if ($accion === 'cambiar_estado') {
        $nuevo_estado = $_POST['nuevo_estado'] ?? 'pendiente';
        $validos = ['pendiente','enviado','aprobado','rechazado'];
        if (!in_array($nuevo_estado, $validos)) {
            echo json_encode(['status'=>'error','message'=>'Estado no válido.']);
            exit;
        }

        $stmt = $link->prepare("UPDATE presupuesto SET estado = ? WHERE id_presupuesto = ?");
        if (!$stmt) throw new Exception('Error preparando consulta.');

        $stmt->bind_param("si", $nuevo_estado, $id);
        if ($stmt->execute()) {
            echo json_encode(['status'=>'ok','message'=>'Estado actualizado correctamente.']);
        } else {
            echo json_encode(['status'=>'error','message'=>'No se pudo cambiar el estado.']);
        }
        $stmt->close();
        exit;
    }

    // --- Si llega una acción no reconocida ---
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Excepción: '.$e->getMessage()]);
}
