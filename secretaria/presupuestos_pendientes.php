<?php
include_once __DIR__ . '/../php/conexionDB.php';
include_once __DIR__ . '/../php/configuracion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Estandarizar control de acceso
if (
  empty($_SESSION['id_usuario']) ||
  ($_SESSION['tipo'] ?? '') !== 'Secretaria'
) {
  header('Location: ../index.php');
  exit;
}
$usuario = $_SESSION['nombre'] ?? 'Secretaría';

/** URL absoluta respetando subcarpeta del proyecto */
function abs_url_from_path($relPath) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'];
  $base   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
  $clean  = ltrim($relPath, '/');
  return $scheme . '://' . $host . $base . '/' . $clean;
}

/** Normaliza teléfonos para wa.me (por defecto Paraguay 595) */
function normalize_tel_for_wa($tel, $default_cc = '595') {
  $digits = preg_replace('/\D+/', '', (string)$tel);
  if ($digits === '') return '';
  if (strpos($digits, $default_cc) === 0) return $digits;
  if ($digits[0] === '0') $digits = substr($digits, 1);
  return $default_cc . $digits;
}

// Filtros (ya eran seguros)
$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? 'pendiente');
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');

$where = " WHERE 1=1 ";
$params = []; $types='';

if ($q !== '') {
  $where .= " AND (p.paciente_nombre LIKE ? OR p.paciente_correo LIKE ?) ";
  $like = "%$q%"; $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($estado !== '') {
  $where .= " AND p.estado = ? ";
  $params[] = $estado; $types .= 's';
}
if ($desde !== '') {
  $where .= " AND DATE(p.fecha) >= ? ";
  $params[] = $desde; $types .= 's';
}
if ($hasta !== '') {
  $where .= " AND DATE(p.fecha) <= ? ";
  $params[] = $hasta; $types .= 's';
}

$sql = "
  SELECT p.id_presupuesto, p.folio, p.fecha, p.paciente_nombre, p.paciente_correo, p.paciente_telefono,
         CASE 
           WHEN p.paciente_telefono IS NOT NULL AND p.paciente_telefono <> '' THEN p.paciente_telefono
           ELSE pac.telefono
         END AS tel_efectivo,
         p.total, p.estado, p.pdf_path, p.enviado_at, p.enviado_via, p.enviado_a,
         CONCAT(d.nombreD,' ',d.apellido) AS doctor
  FROM presupuesto p
  LEFT JOIN doctor d ON d.id_doctor = p.id_doctor
  LEFT JOIN pacientes pac ON pac.id_paciente = p.id_paciente
  $where
  ORDER BY p.fecha DESC
  LIMIT 300
";
$stmt = $link->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

$estados = ['pendiente'=>'Pendiente', 'enviado'=>'Enviado', 'aprobado'=>'Aprobado', 'rechazado'=>'Rechazado'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Perfect Teeth — Presupuestos</title>
<link rel="icon" href="../src/img/logo.png" type="image/png" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
:root{
  --brand:#0d6efd; --brand-100:#e7f1ff;
  --surface:#f8f9fa; --text:#212529;
  --sidebar-w:260px; --radius:12px;
}
body{margin:0;background:var(--surface);color:var(--text);}
.sidebar{
  position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;
  background:#fff;padding:1.25rem 1rem;border-right:1px solid rgba(0,0,0,.08);box-shadow:0 6px 16px rgba(15,23,42,.04);z-index:1030;
}
.brand{display:flex;align-items:center;gap:.75rem;}
.brand-title{margin:0;font-weight:700;color:var(--brand);}
.profile{text-align:center;margin:1rem 0;}
.profile img{width:96px;height:96px;object-fit:cover;}
.nav-menu{display:flex;flex-direction:column;gap:.25rem;}
.nav-menu .nav-link{
  display:flex;align-items:center;gap:.6rem;
  border-radius:.6rem;padding:.6rem .75rem;color:#495057;text-decoration:none;
}
.nav-menu .nav-link:hover,.nav-menu .nav-link.active{
  background:var(--brand-100);color:var(--brand);font-weight:600;
}
.main{margin-left:var(--sidebar-w);min-height:100vh;padding:1.5rem;}
.topbar{
  background:#fff;border-radius:var(--radius);border:1px solid rgba(0,0,0,.06);
  padding:.75rem 1rem;position:sticky;top:0;z-index:10;
}
</style>
</head>

<body>

<!-- Sidebar unificado -->
<aside class="sidebar">
  <div class="brand mb-2">
    <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
    <h1 class="brand-title">Perfect Teeth</h1>
  </div>
  <div class="profile">
    <img src="../src/img/secretaria.png" class="rounded-circle border" alt="Perfil">
    <div class="name"><?= htmlspecialchars($usuario) ?></div>
    <div class="small text-muted">Panel Secretaría</div>
  </div>
  <nav class="nav-menu">
    <a href="gestionar_pacientes.php" class="nav-link"><i class="bi bi-people"></i> Pacientes</a>
    <a href="../gestionar_dentistas.php" class="nav-link"><i class="bi bi-person-badge"></i> Dentistas</a>
    <a href="pagos.php" class="nav-link"><i class="bi bi-cash-stack"></i> Pagos</a>
    <a href="gestionar_citas.php" class="nav-link"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="presupuestos_pendientes.php" class="nav-link active"><i class="bi bi-clock-history"></i> Presupuestos</a>
    <a href="#" class="nav-link"><i class="bi bi-person-gear"></i> Perfil</a>
    <a href="../php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 text-primary"><i class="bi bi-file-earmark-text me-2"></i>Gestión de Presupuestos</h4>
    <small class="text-muted">Administración de presupuestos y envíos</small>
  </div>

  <form class="card mb-3" method="get">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Buscar (paciente/correo)</label>
          <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Ej: Juan, @correo.com">
        </div>
        <div class="col-md-3">
          <label class="form-label">Estado</label>
          <select name="estado" class="form-select">
            <option value="">Todos</option>
            <?php foreach($estados as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $estado===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Desde</label>
          <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Hasta</label>
          <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button class="btn btn-primary w-100"><i class="bi bi-filter"></i></button>
        </div>
      </div>
    </div>
  </form>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Folio</th>
              <th>Fecha</th>
              <th>Paciente</th>
              <th>Teléfono</th>
              <th class="text-end">Total</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $row):
              $pdf_abs  = $row['pdf_path'] ? abs_url_from_path($row['pdf_path']) : '';
              $telRaw   = (string)$row['tel_efectivo'];
              $wa_number = normalize_tel_for_wa($telRaw, '595');
              $detalles = [];
              $detQuery = $link->prepare("SELECT diente, procedimiento, precio FROM presupuesto_detalle WHERE id_presupuesto = ?");
              $detQuery->bind_param("i", $row['id_presupuesto']);
              $detQuery->execute();
              $detRes = $detQuery->get_result();
              while ($det = $detRes->fetch_assoc()) {
                $detalles[] = "🦷 Diente {$det['diente']} — {$det['procedimiento']} ($" . number_format((float)$det['precio'], 0, ',', '.') . ")";
              }
              $detQuery->close();
              $detalleTexto = !empty($detalles) ? implode("\n", $detalles) : "Sin detalles registrados.";
              $msg = "Hola {$row['paciente_nombre']}, su presupuesto total es de $" . number_format((float)$row['total'], 0, ',', '.') . ".\n\n" .
                     "📋 Detalles:\n{$detalleTexto}\n\n" .
                     "Si desea recibir este presupuesto por correo electrónico, por favor responda este mensaje con su dirección de correo.\n" .
                     ($pdf_abs ? "\n Muchas Gracias aguardo su respuesta" : "");
              $wa_web = ($wa_number ? ('https://wa.me/'.$wa_number.'?text='.rawurlencode($msg)) : '');
            ?>
            <tr id="row-<?= (int)$row['id_presupuesto'] ?>">
              <td><?= htmlspecialchars($row['folio']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
              <td>
                <?= htmlspecialchars($row['paciente_nombre']) ?><br>
                <?php if($pdf_abs): ?>
                  <a href="<?= htmlspecialchars($pdf_abs) ?>" target="_blank" class="small"><i class="bi bi-file-earmark-pdf"></i> Ver PDF</a>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['tel_efectivo']) ?></td>
              <td class="text-end">$ <?= number_format((float)$row['total'],2) ?></td>
              <td>
                <select class="form-select form-select-sm estado-select" data-id="<?= (int)$row['id_presupuesto'] ?>">
                  <option value="pendiente" <?= $row['estado']==='pendiente'?'selected':'' ?>>Pendiente</option>
                  <option value="enviado" <?= $row['estado']==='enviado'?'selected':'' ?>>Enviado</option>
                  <option value="aprobado" <?= $row['estado']==='aprobado'?'selected':'' ?>>Aprobado</option>
                  <option value="rechazado" <?= $row['estado']==='rechazado'?'selected':'' ?>>Rechazado</option>
                </select>
                <?php if($row['enviado_at']): ?>
                  <div class="small text-muted" title="Enviado a <?= htmlspecialchars($row['enviado_a']) ?> vía <?= htmlspecialchars($row['enviado_via']) ?>">
                    <?= date('d/m/Y H:i', strtotime($row['enviado_at'])) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if($wa_web): ?>
                  <a class="btn btn-sm btn-success" href="<?= htmlspecialchars($wa_web) ?>" target="_blank"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                <?php else: ?>
                  <button class="btn btn-sm btn-secondary" disabled>Sin teléfono</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-primary btnMarcarEnviado"
                  data-id="<?= htmlspecialchars($row['id_presupuesto'] ?? '') ?>"
                  data-tel="<?= htmlspecialchars($wa_number) ?>">
                  Marcar enviado
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($rows)): ?>
              <tr><td colspan="7" class="text-center text-muted p-4">No se encontraron presupuestos.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){
  $('.btnMarcarEnviado').on('click', function(){
    const id = $(this).data('id');
    const tel = $(this).data('tel') || '';
    if (!confirm('¿Marcar este presupuesto como ENVIADO?')) return;
    $.ajax({
      url: '../php/presupuesto_estado.php',
      method: 'POST',
      data: { accion: 'marcar_enviado', id_presupuesto: id, via: 'whatsapp', enviado_a: tel },
      success: function(res){
        try {
          const r = JSON.parse(res);
          if (r.status === 'ok') {
            alert('✅ Estado actualizado a "Enviado".');
            location.reload();
          } else alert(r.message || 'Error al actualizar.');
        } catch(e){ alert('Respuesta inesperada.'); }
      },
      error:()=>alert('Error de conexión.')
    });
  });

  $('.estado-select').on('change', function(){
    const id = $(this).data('id');
    const estado = $(this).val();
    $.ajax({
      url: '../php/presupuesto_estado.php',
      method: 'POST',
      data: { accion: 'cambiar_estado', id_presupuesto: id, nuevo_estado: estado },
      success: function(res){
        try {
          const r = JSON.parse(res);
          if (r.status === 'ok') {
            alert('✅ Estado actualizado correctamente.');
          } else {
            alert('❌ ' + (r.message || 'Error al actualizar.'));
          }
        } catch(e){
          alert('Respuesta inesperada del servidor.');
        }
      },
      error: ()=> alert('Error de conexión.')
    });
  });
});
</script>

</body>
</html>
