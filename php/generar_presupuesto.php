<?php
// php/generar_presupuesto.php
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/conexionDB.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id_doctor'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada.']);
    exit;
}

$id_doctor = (int)$_SESSION['id_doctor'];

$id_paciente       = isset($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : null;
$paciente_nombre   = trim($_POST['paciente_nombre'] ?? '');
$paciente_correo   = trim($_POST['paciente_correo'] ?? '');
$paciente_telefono = trim($_POST['paciente_telefono'] ?? '');
$paciente_documento= trim($_POST['paciente_documento'] ?? '');
$procedimientosRaw = $_POST['procedimientos'] ?? '[]';

$procedimientos = json_decode($procedimientosRaw, true);
if (!is_array($procedimientos) || count($procedimientos) === 0) {
    echo json_encode(['status'=>'error','message'=>'No hay procedimientos para generar.']); exit;
}
if ($paciente_nombre === '') {
    echo json_encode(['status'=>'error','message'=>'El nombre del paciente es obligatorio.']); exit;
}

// Calcular total
$total = 0;
foreach ($procedimientos as $p) $total += (float)($p['precio'] ?? 0);

// Folio simple
$folio = 'PT-' . date('Y') . '-' . substr((string)time(), -5);

// Insertar cabecera (incluye id_paciente)
$stmt = $link->prepare("INSERT INTO presupuesto (folio, id_doctor, id_paciente, paciente_nombre, paciente_correo, paciente_telefono, paciente_documento, total, estado) VALUES (?,?,?,?,?,?,?,?, 'pendiente')");
$stmt->bind_param('siissssd', $folio, $id_doctor, $id_paciente, $paciente_nombre, $paciente_correo, $paciente_telefono, $paciente_documento, $total);
if (!$stmt->execute()) {
    echo json_encode(['status'=>'error','message'=>'Error al guardar presupuesto (cabecera).']); exit;
}
$id_presupuesto = $stmt->insert_id;
$stmt->close();

// Insertar detalles
$stmtDet = $link->prepare("INSERT INTO presupuesto_detalle (id_presupuesto, diente, lado, procedimiento, precio, observacion) VALUES (?,?,?,?,?,?)");
foreach ($procedimientos as $p) {
    $diente = substr((string)($p['diente'] ?? ''), 0, 5);
    $lado   = substr((string)($p['lado'] ?? ''), 0, 20);
    $proc   = substr((string)($p['procedimiento'] ?? ''), 0, 80);
    $precio = (float)($p['precio'] ?? 0);
    $obs    = (string)($p['observacion'] ?? '');
    $stmtDet->bind_param('isssds', $id_presupuesto, $diente, $lado, $proc, $precio, $obs);
    $stmtDet->execute();
}
$stmtDet->close();

// Datos doctor
$doctorNombre = 'Doctor'; $doctorCorreo = '';
$doctorSql = "SELECT CONCAT(nombreD,' ',apellido) AS nombre, correo_eletronico FROM doctor WHERE id_doctor=$id_doctor LIMIT 1";
if ($res = $link->query($doctorSql)) {
    if ($row = $res->fetch_assoc()) { $doctorNombre = $row['nombre']; $doctorCorreo = $row['correo_eletronico']; }
    $res->free();
}

// Logo
$logoPath = __DIR__ . '/../src/img/logo.png';
$logoDataUri = '';
if (file_exists($logoPath)) $logoDataUri = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));

// HTML del PDF
$fecha = date('d/m/Y H:i');
$rows = '';
foreach ($procedimientos as $p) {
    $rows .= '<tr>
        <td style="text-align:center;">'.htmlspecialchars($p['diente']).'</td>
        <td style="text-align:center;">'.htmlspecialchars($p['lado']).'</td>
        <td>'.htmlspecialchars($p['procedimiento']).'</td>
        <td style="text-align:right;">$ '.number_format((float)$p['precio'], 2).'</td>
        <td>'.htmlspecialchars($p['observacion']).'</td>
    </tr>';
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<style>
  body{ font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#222; }
  .header{ display:flex; align-items:center; gap:12px; }
  .brand{ font-size:18px; font-weight:bold; color:#0d6efd; }
  .meta{ margin-top:6px; font-size:12px; color:#555; }
  .box{ border:1px solid #ddd; border-radius:8px; padding:12px; margin:10px 0; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ border:1px solid #ddd; padding:6px; }
  th{ background:#f2f5ff; }
  .total{ text-align:right; font-weight:bold; }
  .foot{ margin-top:16px; font-size:11px; color:#666; }
</style></head><body>
  <div class="header">'.($logoDataUri?'<img src="'.$logoDataUri.'" height="42" />':'').'
    <div><div class="brand">Perfect Teeth</div>
      <div class="meta">Presupuesto #'.$folio.' · Fecha: '.$fecha.'</div>
      <div class="meta">Odontólogo: '.htmlspecialchars($doctorNombre).'</div>
    </div>
  </div>
  <div class="box">
    <strong>Datos del paciente</strong><br/>
    Nombre: '.htmlspecialchars($paciente_nombre).'<br/>
    Correo: '.htmlspecialchars($paciente_correo).'<br/>
    Teléfono: '.htmlspecialchars($paciente_telefono).'<br/>
    Documento: '.htmlspecialchars($paciente_documento).'
  </div>
  <table>
     <thead><tr>
       <th style="width:9%;">Diente</th>
       <th style="width:13%;">Lado</th>
       <th>Procedimiento</th>
       <th style="width:12%;">Precio</th>
       <th>Observación</th>
     </tr></thead>
     <tbody>'.$rows.'</tbody>
  </table>
  <p class="total">Total: $ '.number_format($total,2).'</p>
  <div class="foot">Este presupuesto tiene validez referencial y puede requerir evaluación clínica complementaria.</div>
</body></html>';

// Render PDF
$pdfDir = realpath(__DIR__ . '/..') . '/presupuestos'; // Ruta desde la raíz
if (!is_dir($pdfDir)) { @mkdir($pdfDir, 0775, true); }

$filename = 'presupuesto_'.$id_presupuesto.'.pdf';
$fullPath = $pdfDir . '/' . $filename;

try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // require_once __DIR__ . '/../dompdf/autoload.inc.php';
    }
    if (!class_exists('\Dompdf\Dompdf')) throw new Exception('Dompdf no está instalado.');

    $options = new Dompdf\Options(); $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8'); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();

    $output = $dompdf->output();
    if (file_put_contents($fullPath, $output) === false) throw new Exception('No se pudo escribir el PDF.');

    $relPath = 'presupuestos/' . $filename;
    $stmtU = $link->prepare("UPDATE presupuesto SET pdf_path=?, total=? WHERE id_presupuesto=?");
    $stmtU->bind_param('sdi', $relPath, $total, $id_presupuesto);
    $stmtU->execute(); $stmtU->close();

    echo json_encode([
        'status' => 'ok',
        'message' => 'Presupuesto generado.',
        'id_presupuesto' => $id_presupuesto,
        'folio' => $folio,
        'pdf_url' => '/' . $relPath // Ruta desde la raíz
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo generar el PDF: '.$e->getMessage(),
        'html_fallback' => $html
    ]);
}
?>
