<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../php/conexionDB.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ======= Control de acceso ======= */
if (
  empty($_SESSION['id_usuario']) ||
  ($_SESSION['tipo'] ?? '') !== 'Secretaria'
) {
  header('Location: ../index.php');
  exit;
}

$usuario = $_SESSION['nombre'] ?? 'Secretaría';

/* ======= Cargar doctores para el filtro ======= */
$doctores = mysqli_query($link, "SELECT id_doctor, CONCAT(nombreD, ' ', apellido) AS nombre FROM doctor ORDER BY nombreD ASC");

/* ======= Filtros Seguros con Consultas Preparadas ======= */
$where = "1=1";
$params = [];
$types = '';

if (!empty($_GET['nombre'])) {
  $where .= " AND p.nombre LIKE ?";
  $params[] = "%" . $_GET['nombre'] . "%";
  $types .= 's';
}
if (!empty($_GET['fecha'])) {
  $where .= " AND c.fecha_cita = ?";
  $params[] = $_GET['fecha'];
  $types .= 's';
}
if (!empty($_GET['doctor'])) {
  $where .= " AND c.id_doctor = ?";
  $params[] = (int)$_GET['doctor'];
  $types .= 'i';
}

/* ======= Consultar citas de forma segura ======= */
$sql = "
  SELECT 
    c.id_cita, c.fecha_cita, c.hora_cita, c.estado,
    p.nombre AS nombre_paciente, p.apellido AS apellido_paciente,
    d.nombreD AS nombre_doctor, d.apellido AS apellido_doctor
  FROM citas c
  INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
  INNER JOIN doctor d ON c.id_doctor = d.id_doctor
  WHERE $where
  ORDER BY c.fecha_cita DESC, c.hora_cita ASC
";

$stmt = $link->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$citas = $stmt->get_result();
if (!$citas) die("<b>Error SQL:</b> " . $stmt->error);


date_default_timezone_set("America/Asuncion");
$fecha = date("d/m/Y");
$hora = date("H:i");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Citas — Secretaría</title>
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
  background:#fff;padding:1.25rem 1rem;border-right:0;box-shadow:none;z-index:1030;
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
  background:#fff;border-bottom:1px solid rgba(0,0,0,.06);
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
    <a href="pagos.php" class="nav-link"><i class="bi bi-cash-stack"></i> Pagos</a>
    <a href="gestionar_citas.php" class="nav-link active"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="presupuestos_pendientes.php" class="nav-link"><i class="bi bi-clock-history"></i> Presupuestos Pendientes</a>
    <a href="perfil_secretaria.php" class="nav-link"><i class="bi bi-person-gear"></i> Perfil</a>
    <a href="../php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-primary"><i class="bi bi-calendar-check"></i> Gestión de Citas</h5>
    <small class="text-muted"><?= $fecha ?> • <?= $hora ?></small>
  </div>

  <!-- Filtros -->
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-3"><input type="text" name="nombre" class="form-control" placeholder="Buscar por paciente" value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>"></div>
        <div class="col-md-3"><input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>"></div>
        <div class="col-md-4">
          <select name="doctor" class="form-select">
            <option value="">-- Todos los doctores --</option>
            <?php 
            mysqli_data_seek($doctores, 0); // Reset pointer
            while ($d = mysqli_fetch_assoc($doctores)) { ?>
              <option value="<?= $d['id_doctor'] ?>" <?= (isset($_GET['doctor']) && $_GET['doctor'] == $d['id_doctor']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['nombre']) ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button></div>
      </form>
    </div>
  </div>

  <!-- Lista -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="text-primary mb-0">Citas Registradas</h6>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarCita">
          <i class="bi bi-plus-circle"></i> Nueva Cita
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th><th>Paciente</th><th>Doctor</th><th>Fecha</th><th>Hora</th><th>Estado</th><th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($c = $citas->fetch_assoc()) { ?>
            <tr>
              <td><?= $c['id_cita'] ?></td>
              <td><?= htmlspecialchars($c['nombre_paciente'] . " " . $c['apellido_paciente']) ?></td>
              <td><?= htmlspecialchars($c['nombre_doctor'] . " " . $c['apellido_doctor']) ?></td>
              <td><?= htmlspecialchars($c['fecha_cita']) ?></td>
              <td><?= htmlspecialchars($c['hora_cita']) ?></td>
              <td>
                <?php if ($c['estado'] === 'A'): ?>
                  <span class="badge bg-success">Atendida</span>
                <?php elseif ($c['estado'] === 'I'): ?>
                  <span class="badge bg-warning text-dark">Pendiente</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($c['estado']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarCita"
                  data-id="<?= $c['id_cita'] ?>"
                  data-fecha="<?= $c['fecha_cita'] ?>"
                  data-hora="<?= $c['hora_cita'] ?>"
                  data-estado="<?= $c['estado'] ?>">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEliminarCita"
                  data-id="<?= $c['id_cita'] ?>">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <p class="text-muted small mb-0">Total de citas: <strong><?= $citas->num_rows ?></strong></p>
    </div>
  </div>
</div>

<!-- CRUD Modals -->
<div class="modal fade" id="modalAgregarCita" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAgregarCita" class="modal-content">
      <div class="modal-header bg-success text-white"><h5 class="modal-title">Agregar Cita</h5></div>
      <div class="modal-body">
        <input type="number" name="id_paciente" class="form-control mb-2" placeholder="ID Paciente" required>
        <input type="number" name="id_doctor" class="form-control mb-2" placeholder="ID Doctor" required>
        <input type="date" name="fecha_cita" class="form-control mb-2" required>
        <input type="time" name="hora_cita" class="form-control mb-2" required>
        <select name="estado" class="form-control mb-2">
          <option value="I">Pendiente</option>
          <option value="A">Atendida</option>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Editar y Eliminar -->
<div class="modal fade" id="modalEditarCita" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEditarCita" class="modal-content">
      <div class="modal-header bg-warning text-white"><h5 class="modal-title">Editar Cita</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_cita" id="edit-id">
        <input type="date" name="fecha_cita" id="edit-fecha" class="form-control mb-2" required>
        <input type="time" name="hora_cita" id="edit-hora" class="form-control mb-2" required>
        <select name="estado" id="edit-estado" class="form-control mb-2">
          <option value="I">Pendiente</option>
          <option value="A">Atendida</option>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEliminarCita" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEliminarCita" class="modal-content">
      <div class="modal-header bg-danger text-white"><h5 class="modal-title">Eliminar Cita</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_cita" id="delete-id">
        <p>¿Seguro que deseas eliminar esta cita?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger">Eliminar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('modalEditarCita').addEventListener('show.bs.modal', e=>{
  const b=e.relatedTarget;
  $('#edit-id').val(b.dataset.id);
  $('#edit-fecha').val(b.dataset.fecha);
  $('#edit-hora').val(b.dataset.hora);
  $('#edit-estado').val(b.dataset.estado);
});
document.getElementById('modalEliminarCita').addEventListener('show.bs.modal', e=>{
  $('#delete-id').val(e.relatedTarget.dataset.id);
});

/* ===== CRUD AJAX ===== */
$("#formAgregarCita").submit(function(e){
  e.preventDefault();
  $.post("../crud/cita_INSERT.php", $(this).serialize(), function(r){
    if(r.status=="success") location.reload(); else alert("Error: "+r.msg);
  },"json");
});
$("#formEditarCita").submit(function(e){
  e.preventDefault();
  $.post("../crud/cita_UPDATE.php", $(this).serialize(), function(r){
    if(r.status=="success") location.reload(); else alert("Error: "+r.msg);
  },"json");
});
$("#formEliminarCita").submit(function(e){
  e.preventDefault();
  $.post("../crud/cita_DELETE.php", $(this).serialize(), function(r){
    if(r.status=="success") location.reload(); else alert("Error: "+r.msg);
  },"json");
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
