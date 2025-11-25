<?php
include_once("../php/conexionDB.php");

$doctor_id = (int)($_GET['doctor'] ?? 0);
$fecha = $_GET['fecha'] ?? '';

if (!$doctor_id || !$fecha) {
    echo json_encode([]);
    exit;
}

// Rango de horarios disponibles (puedes ajustar)
$horarios = [
    "08:00", "09:00", "10:00", "11:00", "12:00",
    "13:00", "14:00", "15:00", "16:00", "17:00", "18:00"
];

// Buscar citas ya ocupadas
$sql = "SELECT hora_cita FROM citas WHERE id_doctor = ? AND fecha_cita = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("is", $doctor_id, $fecha);
$stmt->execute();
$res = $stmt->get_result();

$ocupadas = [];
while ($row = $res->fetch_assoc()) {
    $ocupadas[] = substr($row['hora_cita'], 0, 5);
}
$stmt->close();

// Generar respuesta
$response = [];
foreach ($horarios as $hora) {
    $response[] = [
        "hora" => $hora,
        "disponible" => !in_array($hora, $ocupadas)
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
