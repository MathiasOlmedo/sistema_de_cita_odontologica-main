<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../php/conexionDB.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Estandarizar control de acceso
if (
  empty($_SESSION['id_usuario']) ||
  ($_SESSION['tipo'] ?? '') !== 'Secretaria'
) {
  header('Location: ../index.php');
  exit;
}

$usuario = $_SESSION['nombre'] ?? 'Secretaría';

// Búsqueda segura (ya estaba bien implementada)
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  $qLike = "%$q%";
  $stmt = $link->prepare("
    SELECT p.id_presupuesto, p.folio, p.paciente_nombre, p.total,
           COALESCE(SUM(pg.monto),0) AS pagado,
           (p.total - COALESCE(SUM(pg.monto),0)) AS saldo
    FROM presupuesto p
    LEFT JOIN pagos pg ON p.id_presupuesto = pg.id_presupuesto
    WHERE p.paciente_nombre LIKE ? OR p.folio LIKE ?
    GROUP BY p.id_presupuesto
    ORDER BY p.fecha DESC
  ");
  $stmt->bind_param('ss', $qLike, $qLike);
} else {
  $stmt = $link->prepare("
    SELECT p.id_presupuesto, p.folio, p.paciente_nombre, p.total,
           COALESCE(SUM(pg.monto),0) AS pagado,
           (p.total - COALESCE(SUM(pg.monto),0)) AS saldo
    FROM presupuesto p
    LEFT JOIN pagos pg ON p.id_presupuesto = pg.id_presupuesto
    GROUP BY p.id_presupuesto
    ORDER BY p.fecha DESC
    LIMIT 50
  ");
}
$stmt->execute();
$res = $stmt->get_result();
$presupuestos = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Registrar nuevo pago (ya estaba bien implementado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_presupuesto'], $_POST['monto'])) {
  $id_presupuesto = (int)$_POST['id_presupuesto'];
  $monto = (float)$_POST['monto'];
  $metodo = $_POST['metodo'] ?? 'efectivo';
  $obs = $_POST['observacion'] ?? '';

  $insert = $link->prepare("INSERT INTO pagos (id_presupuesto, monto, metodo, observacion) VALUES (?,?,?,?)");
  $insert->bind_param('idss', $id_presupuesto, $monto, $metodo, $obs);
  $insert->execute();
  $insert->close();

  $_SESSION['MensajeTexto'] = "Pago registrado correctamente.";
  header("Location: pagos.php?q=" . urlencode($q));
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Pagos — Secretaría</title>
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
.card{border-radius:var(--radius);border:1px solid rgba(0,0,0,.06);box-shadow:0 6px 16px rgba(15,23,42,.06);}
</style>
</head>
<body>

<!-- Sidebar -->
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
    <a href="pagos.php" class="nav-link active"><i class="bi bi-cash-stack"></i> Pagos</a>
    <a href="gestionar_citas.php" class="nav-link"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="presupuestos_pendientes.php" class="nav-link"><i class="bi bi-clock-history"></i> Presupuestos</a>
    <a href="#" class="nav-link"><i class="bi bi-person-gear"></i> Perfil</a>
    <a href="../php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 text-primary"><i class="bi bi-cash-stack me-2"></i>Gestión de Pagos</h4>
    <small class="text-muted">Registrar abonos y saldos</small>
  </div>

  <form class="mb-3" method="get">
    <div class="input-group">
      <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre o folio">
      <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </div>
  </form>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Folio</th>
              <th>Paciente</th>
              <th>Total</th>
              <th>Pagado</th>
              <th>Saldo</th>
              <th>Registrar Pago</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($presupuestos as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['folio']) ?></td>
                <td><?= htmlspecialchars($p['paciente_nombre']) ?></td>
                <td>$ <?= number_format($p['total'],2) ?></td>
                <td class="text-success">$ <?= number_format($p['pagado'],2) ?></td>
                <td class="text-danger fw-bold">$ <?= number_format($p['saldo'],2) ?></td>
                <td>
                  <?php if($p['saldo'] > 0): ?>
                    <button class="btn btn-sm btn-primary btnPagar" 
                            data-id="<?= $p['id_presupuesto'] ?>"
                            data-paciente="<?= htmlspecialchars($p['paciente_nombre']) ?>"
                            data-saldo="<?= $p['saldo'] ?>">
                      <i class="bi bi-plus-circle me-1"></i> Registrar abono
                    </button>
                  <?php else: ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Cancelado</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(empty($presupuestos)): ?>
              <tr><td colspan="6" class="text-center text-muted p-4">No se encontraron presupuestos.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-currency-dollar me-2"></i>Registrar abono</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_presupuesto" id="id_presupuesto">
        <div class="mb-2">
          <label class="form-label">Paciente</label>
          <input type="text" id="paciente" class="form-control" readonly>
        </div>
        <div class="mb-2">
          <label class="form-label">Saldo pendiente</label>
          <input type="text" id="saldo" class="form-control" readonly>
        </div>
        <div class="mb-2">
          <label class="form-label">Monto a abonar</label>
          <input type="number" name="monto" class="form-control" step="0.01" min="0" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Método de pago</label>
          <select name="metodo" class="form-select">
            <option value="efectivo">Efectivo</option>
            <option value="transferencia">Transferencia</option>
            <option value="tarjeta">Tarjeta</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Observación</label>
          <textarea name="observacion" class="form-control" placeholder="Ej: abono parcial, transferencia..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Registrar Pago</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){
  $('.btnPagar').on('click', function(){
    $('#id_presupuesto').val($(this).data('id'));
    $('#paciente').val($(this).data('paciente'));
    $('#saldo').val('$ '+$(this).data('saldo'));
    $('#modalPago').modal('show');
  });
});
</script>

</body>
</html>
