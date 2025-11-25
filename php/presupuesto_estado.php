<?php
// php/presupuesto_estado.php - Versión con depuración mejorada
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en output (solo en log)
ini_set('log_errors', 1);

include_once("conexionDB.php");
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// Verificar conexión a base de datos
if (!isset($link) || !$link) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a base de datos.']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$id     = (int)($_POST['id_presupuesto'] ?? 0);

// Validar ID
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de presupuesto inválido.']);
    exit;
}

// ACCIÓN: Cambiar estado manualmente
if ($accion === 'cambiar_estado') {
    $nuevo = $_POST['nuevo_estado'] ?? 'pendiente';
    
    // Validar estado
    $estados_validos = ['pendiente', 'enviado', 'aprobado', 'rechazado'];
    if (!in_array($nuevo, $estados_validos)) {
        echo json_encode(['status' => 'error', 'message' => 'Estado inválido.']);
        exit;
    }
    
    $stmt = $link->prepare("UPDATE presupuesto SET estado = ? WHERE id_presupuesto = ?");
    
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar consulta: ' . $link->error]);
        exit;
    }
    
    $stmt->bind_param("si", $nuevo, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'ok', 
                'message' => 'Estado actualizado correctamente.',
                'nuevo_estado' => $nuevo
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el presupuesto o el estado ya era el mismo.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar: ' . $stmt->error]);
    }
    
    $stmt->close();
    exit;
}

// ACCIÓN: Marcar como enviado
if ($accion === 'marcar_enviado') {
    $via  = $_POST['via'] ?? 'whatsapp';
    $dest = $_POST['enviado_a'] ?? null;
    $now  = date('Y-m-d H:i:s');
    
    $stmt = $link->prepare("
        UPDATE presupuesto 
        SET estado = 'enviado', 
            enviado_at = ?, 
            enviado_via = ?, 
            enviado_a = ? 
        WHERE id_presupuesto = ?
    ");
    
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar consulta: ' . $link->error]);
        exit;
    }
    
    $stmt->bind_param("sssi", $now, $via, $dest, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'ok',
                'message' => 'Presupuesto marcado como enviado.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el presupuesto.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar: ' . $stmt->error]);
    }
    
    $stmt->close();
    exit;
}

// Si llegamos aquí, la acción no es válida
echo json_encode([
    'status' => 'error', 
    'message' => 'Acción inválida o no especificada.',
    'accion_recibida' => $accion
]);
?>