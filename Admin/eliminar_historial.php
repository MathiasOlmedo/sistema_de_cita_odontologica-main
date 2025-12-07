<?php
include_once('../php/conexionDB.php');
include_once('conexionDB.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

header('Content-Type: application/json');

if (!isset($_SESSION['id_doctor'])) {
  echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
  echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
  exit;
}

$id_historial = (int)$_POST['id'];
$id_doctor = (int)$_SESSION['id_doctor'];

// Verificar que el registro pertenece al doctor actual
$sql_check = "SELECT id_historial FROM historial_medico WHERE id_historial = $id_historial AND id_doctor = $id_doctor";
$result_check = mysqli_query($link, $sql_check);

if (mysqli_num_rows($result_check) === 0) {
  echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar este registro']);
  exit;
}

// Eliminar registro
$sql_delete = "DELETE FROM historial_medico WHERE id_historial = $id_historial";

if (mysqli_query($link, $sql_delete)) {
  echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);
} else {
  echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . mysqli_error($link)]);
}

mysqli_close($link);
?>