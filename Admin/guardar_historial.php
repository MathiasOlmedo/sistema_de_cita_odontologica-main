<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);

include_once('conexionDB.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

header('Content-Type: application/json');

// Log de inicio
error_log("=== Inicio guardar_historial.php ===");

if (!isset($_SESSION['id_doctor'])) {
  error_log("Error: Sesión no válida");
  echo json_encode(['success' => false, 'message' => 'Acceso no autorizado - Sesión no válida']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  error_log("Error: Método no es POST: " . $_SERVER['REQUEST_METHOD']);
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

// Verificar datos recibidos
error_log("POST data: " . print_r($_POST, true));

$id_paciente = isset($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : 0;
$id_doctor = isset($_POST['id_doctor']) ? (int)$_POST['id_doctor'] : 0;

if ($id_paciente === 0 || $id_doctor === 0) {
  error_log("Error: Datos incompletos - Paciente: $id_paciente, Doctor: $id_doctor");
  echo json_encode([
    'success' => false, 
    'message' => 'Datos incompletos',
    'debug' => [
      'id_paciente' => $id_paciente,
      'id_doctor' => $id_doctor
    ]
  ]);
  exit;
}

// Verificar conexión a BD
if (!$link || mysqli_connect_errno()) {
  error_log("Error de conexión a BD: " . mysqli_connect_error());
  echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
  exit;
}

// Sanitizar datos
$grupo_sanguineo = mysqli_real_escape_string($link, trim($_POST['grupo_sanguineo'] ?? ''));
$alergias = mysqli_real_escape_string($link, trim($_POST['alergias'] ?? ''));
$enfermedades_cronicas = mysqli_real_escape_string($link, trim($_POST['enfermedades_cronicas'] ?? ''));
$medicamentos_actuales = mysqli_real_escape_string($link, trim($_POST['medicamentos_actuales'] ?? ''));
$cirugias_previas = mysqli_real_escape_string($link, trim($_POST['cirugias_previas'] ?? ''));
$observaciones = mysqli_real_escape_string($link, trim($_POST['observaciones'] ?? ''));

// Construir SQL
$sql = "
  INSERT INTO historial_medico (
    id_paciente, 
    id_doctor, 
    grupo_sanguineo, 
    alergias, 
    enfermedades_cronicas, 
    medicamentos_actuales, 
    cirugias_previas, 
    observaciones,
    fecha_registro
  ) VALUES (
    $id_paciente,
    $id_doctor,
    " . ($grupo_sanguineo ? "'$grupo_sanguineo'" : "NULL") . ",
    " . ($alergias ? "'$alergias'" : "NULL") . ",
    " . ($enfermedades_cronicas ? "'$enfermedades_cronicas'" : "NULL") . ",
    " . ($medicamentos_actuales ? "'$medicamentos_actuales'" : "NULL") . ",
    " . ($cirugias_previas ? "'$cirugias_previas'" : "NULL") . ",
    " . ($observaciones ? "'$observaciones'" : "NULL") . ",
    NOW()
  )
";

error_log("SQL a ejecutar: $sql");

if (mysqli_query($link, $sql)) {
  $nuevo_id = mysqli_insert_id($link);
  error_log("✅ Registro guardado correctamente. ID: $nuevo_id");
  
  echo json_encode([
    'success' => true, 
    'message' => 'Registro médico guardado correctamente',
    'id' => $nuevo_id
  ]);
} else {
  $error = mysqli_error($link);
  error_log("❌ Error SQL: $error");
  
  echo json_encode([
    'success' => false, 
    'message' => 'Error al guardar: ' . $error,
    'sql' => $sql
  ]);
}

mysqli_close($link);
error_log("=== Fin guardar_historial.php ===");
?>