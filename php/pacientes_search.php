<?php
// php/pacientes_search.php
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/conexionDB.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id_doctor']) && !isset($_SESSION['id_admin']) && !isset($_SESSION['id_secretaria'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

/*
  Tabla pacientes (perfect_teeth.sql):
  id_paciente, nombre, apellido, telefono, sexo, fecha_nacimiento, correo_electronico, clave
*/

$q       = trim($_GET['q'] ?? '');
$sexo    = trim($_GET['sexo'] ?? '');
$limit   = (int)($_GET['limit'] ?? 25);
$offset  = (int)($_GET['offset'] ?? 0);
$limit = ($limit > 0 && $limit <= 100) ? $limit : 25;

$where = " WHERE 1=1 ";
$params = [];
$types  = '';

if ($q !== '') {
    $where .= " AND (nombre LIKE ? OR apellido LIKE ? OR CONCAT(nombre,' ',apellido) LIKE ? OR correo_electronico LIKE ? OR telefono LIKE ?) ";
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sssss';
}

if ($sexo !== '' && in_array($sexo, ['Femenino','Masculino'], true)) {
    $where .= " AND sexo = ? ";
    $params[] = $sexo;
    $types .= 's';
}

$sql = "
  SELECT SQL_CALC_FOUND_ROWS
         id_paciente, nombre, apellido, telefono, sexo, fecha_nacimiento, correo_electronico
  FROM pacientes
  $where
  ORDER BY apellido ASC, nombre ASC
  LIMIT ?, ?
";
$params[] = $offset;  $types .= 'i';
$params[] = $limit;   $types .= 'i';

$stmt = $link->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = [
        'id_paciente' => (int)$row['id_paciente'],
        'nombre' => $row['nombre'],
        'apellido' => $row['apellido'],
        'telefono' => $row['telefono'],
        'sexo' => $row['sexo'],
        'fecha_nacimiento' => $row['fecha_nacimiento'],
        'correo_electronico' => $row['correo_electronico'],
    ];
}
$stmt->close();

$totalRes = $link->query("SELECT FOUND_ROWS() AS total")->fetch_assoc();
$total = (int)$totalRes['total'];
$next_offset = ($offset + $limit < $total) ? $offset + $limit : null;

echo json_encode([
    'status' => 'ok',
    'items' => $items,
    'total' => $total,
    'next_offset' => $next_offset
]);
