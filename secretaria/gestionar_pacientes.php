<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../php/conexionDB.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ===== Control de acceso ===== */
if (
  empty($_SESSION['id_usuario']) ||
  ($_SESSION['tipo'] ?? '') !== 'Secretaria'
) {
  header('Location: ../index.php');
  exit;
}

$usuario = $_SESSION['nombre'] ?? 'Secretaría';

/* ===== Consultar pacientes ===== */
$where = "1=1";
if (!empty($_GET['nombre'])) {
  $nombre = mysqli_real_escape_string($link, $_GET['nombre']);
  $where .= " AND nombre LIKE '%$nombre%'";
}
if (!empty($_GET['apellido'])) {
  $apellido = mysqli_real_escape_string($link, $_GET['apellido']);
  $where .= " AND apellido LIKE '%$apellido%'";
}
$pacientes = mysqli_query($link, "SELECT * FROM pacientes WHERE $where ORDER BY id_paciente DESC");

date_default_timezone_set("America/Asuncion");
$fecha = date("d/m/Y");
$hora = date("H:i");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Pacientes — Secretaría</title>
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
    <a href="gestionar_pacientes.php" class="nav-link active"><i class="bi bi-people"></i> Pacientes</a>
    <a href="pagos.php" class="nav-link"><i class="bi bi-cash-stack"></i> Pagos</a>
    <a href="gestionar_citas.php" class="nav-link"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="presupuestos_pendientes.php" class="nav-link"><i class="bi bi-clock-history"></i> Presupuestos Pendientes</a>
    <a href="perfil_secretaria.php" class="nav-link"><i class="bi bi-person-gear"></i> Perfil</a>
    <a href="../php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-primary"><i class="bi bi-people"></i> Gestión de Pacientes</h5>
    <small class="text-muted"><?= $fecha ?> • <?= $hora ?></small>
  </div>

  <!-- Filtros -->
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-5"><input type="text" name="nombre" class="form-control" placeholder="Buscar por nombre"></div>
        <div class="col-md-5"><input type="text" name="apellido" class="form-control" placeholder="Buscar por apellido"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button></div>
      </form>
    </div>
  </div>

  <!-- Lista -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="text-primary mb-0">Pacientes Registrados</h6>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarPaciente">
          <i class="bi bi-plus-circle"></i> Nuevo
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Nombre</th><th>Apellido</th><th>Cédula</th><th>Teléfono</th><th>Sexo</th><th>Correo</th><th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($p = mysqli_fetch_assoc($pacientes)) { ?>
            <tr>
              
              <td><?= htmlspecialchars($p['nombre']) ?></td>
              <td><?= htmlspecialchars($p['apellido']) ?></td>
              <td><?= htmlspecialchars($p['cedula']) ?></td>
              <td><?= htmlspecialchars($p['telefono']) ?></td>
              <td><?= htmlspecialchars($p['sexo']) ?></td>
              <td><?= htmlspecialchars($p['correo_electronico']) ?></td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm" 
                  data-bs-toggle="modal" data-bs-target="#modalEditarPaciente"
                  data-id="<?= $p['id_paciente'] ?>"
                  data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                  data-apellido="<?= htmlspecialchars($p['apellido']) ?>"
                  data-cedula="<?= htmlspecialchars($p['cedula']) ?>"
                  data-telefono="<?= htmlspecialchars($p['telefono']) ?>"
                  data-sexo="<?= htmlspecialchars($p['sexo']) ?>"
                  data-correo="<?= htmlspecialchars($p['correo_electronico']) ?>">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-danger btn-sm"
                  data-bs-toggle="modal" data-bs-target="#modalEliminarPaciente"
                  data-id="<?= $p['id_paciente'] ?>">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <p class="text-muted small mb-0">Total de pacientes: <strong><?= mysqli_num_rows($pacientes) ?></strong></p>
    </div>
  </div>
</div>

<!-- Modales -->
<div class="modal fade" id="modalAgregarPaciente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAgregarPaciente" class="modal-content">
      <div class="modal-header bg-success text-white"><h5 class="modal-title">Agregar Paciente</h5></div>
      <div class="modal-body">
        <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required>
        <input type="text" name="apellido" class="form-control mb-2" placeholder="Apellido" required>
        <input type="text" name="cedula" id="edit-cedula" class="form-control mb-2" placeholder="Cédula">
        <input type="text" name="telefono" class="form-control mb-2" placeholder="Teléfono">
        <input type="text" name="sexo" class="form-control mb-2" placeholder="Sexo">
        <input type="email" name="correo" class="form-control mb-2" placeholder="Correo electrónico" required>
        <input type="text" name="clave" class="form-control mb-2" placeholder="Contraseña" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEditarPaciente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEditarPaciente" class="modal-content">
      <div class="modal-header bg-warning text-white"><h5 class="modal-title">Editar Paciente</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_paciente" id="edit-id">
        <input type="text" name="nombre" id="edit-nombre" class="form-control mb-2">
        <input type="text" name="apellido" id="edit-apellido" class="form-control mb-2">
        <input type="text" name="telefono" id="edit-telefono" class="form-control mb-2">
        <input type="text" name="sexo" id="edit-sexo" class="form-control mb-2">
        <input type="email" name="correo" id="edit-correo" class="form-control mb-2">
        <input type="text" name="clave" id="edit-clave" class="form-control mb-2" placeholder="Nueva contraseña (opcional)">
      </div>
      <div class="modal-footer">
        <button class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEliminarPaciente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEliminarPaciente" class="modal-content">
      <div class="modal-header bg-danger text-white"><h5 class="modal-title">Eliminar Paciente</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_paciente" id="delete-id">
        <p>¿Seguro que deseas eliminar este paciente?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger">Eliminar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ===== Modales ===== */
document.getElementById('modalEditarPaciente').addEventListener('show.bs.modal', function (e) {
  const b = e.relatedTarget;
  $('#edit-id').val(b.dataset.id);
  $('#edit-nombre').val(b.dataset.nombre);
  $('#edit-apellido').val(b.dataset.apellido);
  $('#edit-cedula').val(b.dataset.cedula);
  $('#edit-telefono').val(b.dataset.telefono);
  $('#edit-sexo').val(b.dataset.sexo);
  $('#edit-correo').val(b.dataset.correo);
});
document.getElementById('modalEliminarPaciente').addEventListener('show.bs.modal', e => {
  $('#delete-id').val(e.relatedTarget.dataset.id);
});

/* ===== AJAX CRUD ===== */
$("#formAgregarPaciente").submit(function(e){
  e.preventDefault();
  $.post("../crud/paciente_INSERT.php", $(this).serialize(), function(r){
    if(r.status=="success") location.reload();
    else alert("Error: "+r.msg);
  },"json");
});
$("#formEditarPaciente").submit(function(e){
  e.preventDefault();
  $.post("../crud/paciente_UPDATE.php", $(this).serialize(), function(r){
    if(r.status=="success") location.reload();
    else alert("Error: "+r.msg);
  },"json");
});
$("#formEliminarPaciente").submit(function(e){
  e.preventDefault();
  $.post("../crud/paciente_DELETE.php", $(this).serialize(), function(r){
    if(r.status=="success") location.reload();
    else alert("Error: "+r.msg);
  },"json");
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
