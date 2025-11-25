<?php
/**
 * ✅ ELIMINAR CITA (CANCELAR)
 * 
 * Elimina permanentemente una cita de la base de datos
 * El horario queda automáticamente disponible
 */

include_once('../php/conexionDB.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status" => "error",
        "msg" => "Método no permitido"
    ]);
    exit();
}

// Obtener ID de la cita
$id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;

// Validar ID
if ($id_cita === 0) {
    echo json_encode([
        "status" => "error",
        "msg" => "ID de cita inválido"
    ]);
    exit();
}

// Verificar que la cita existe antes de eliminar
$check = $link->prepare("SELECT id_cita, fecha_cita, hora_cita FROM citas WHERE id_cita = ?");
$check->bind_param('i', $id_cita);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "msg" => "La cita no existe"
    ]);
    exit();
}

$cita = $result->fetch_assoc();
$check->close();

// Eliminar la cita
$stmt = $link->prepare("DELETE FROM citas WHERE id_cita = ?");
$stmt->bind_param('i', $id_cita);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "msg" => "✅ Cita cancelada exitosamente. El horario ahora está disponible.",
        "data" => [
            "id_cita" => $id_cita,
            "fecha" => $cita['fecha_cita'],
            "hora" => $cita['hora_cita']
        ]
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "msg" => "Error al eliminar la cita: " . mysqli_error($link)
    ]);
}

$stmt->close();
mysqli_close($link);
?>