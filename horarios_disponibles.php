<?php
/**
 * ✅ HORARIOS DISPONIBLES - VERSIÓN SIMPLE
 * 
 * Consulta qué horarios están ocupados para un doctor en una fecha
 * Como NO hay estado "Cancelada", simplemente consulta todas las citas que existen
 * Las citas canceladas se ELIMINAN de la BD, por lo que no aparecen aquí
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

include_once(__DIR__ . '/../php/conexionDB.php');

// Obtener parámetros
$doctor = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;
$fecha  = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';

// Validar parámetros
if ($doctor === 0 || $fecha === '') {
    echo json_encode([]);
    exit;
}

// Horarios base (8:00 AM - 7:00 PM)
$horarios_base = [
    '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
    '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00',
    '18:00:00', '19:00:00'
];

// Consultar citas ocupadas (TODAS las citas que existen)
// Como eliminamos las canceladas, no necesitamos filtrar por estado
$stmt = $link->prepare("
    SELECT hora_cita
    FROM citas 
    WHERE id_doctor = ? 
      AND fecha_cita = ?
    ORDER BY hora_cita ASC
");

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('is', $doctor, $fecha);
$stmt->execute();
$res = $stmt->get_result();

$ocupados = [];
while ($row = $res->fetch_assoc()) {
    // Normalizar hora a formato HH:MM:SS
    $ocupados[] = substr($row['hora_cita'], 0, 8);
}
$stmt->close();

// Generar lista de horarios con disponibilidad
$resultado = [];
foreach ($horarios_base as $h) {
    $resultado[] = [
        'hora' => substr($h, 0, 5), // HH:MM
        'disponible' => !in_array($h, $ocupados)
    ];
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
mysqli_close($link);
?>