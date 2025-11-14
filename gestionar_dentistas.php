<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/php/conexionDB.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Asumiendo que esta página es para la secretaria también
if (
  empty($_SESSION['id_usuario']) ||
  ($_SESSION['tipo'] ?? '') !== 'Secretaria'
) {
  header('Location: index.php');
  exit;
}

$usuario = $_SESSION['nombre'] ?? 'Secretaría';

// Traer especialidades para los modales
$especialidades = mysqli_query($link, "SELECT id_especialidad, tipo FROM especialidad");

/* ===== Búsqueda Segura de Dentistas ===== */
$params = [];
$types = '';
$where = "1=1";

if (!empty($_GET['nombre'])) {
  $where .= " AND nombreD LIKE ?";
  $params[] = "%" . $_GET['nombre'] . "%";
  $types .= 's';
}
if (!empty($_GET['apellido'])) {
  $where .= " AND apellido LIKE ?";
  $params[] = "%" . $_GET['apellido'] . "%";
  $types .= 's';
}

$stmt = $link->prepare("SELECT * FROM doctor WHERE $where ORDER BY id_doctor DESC");
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$dentistas = $stmt->get_result();


date_default_timezone_set("America/Asuncion");
$fecha = date("d/m/Y");
$hora = date("H:i");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Dentistas — Secretaría</title>
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
    <img src="./src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
    <h1 class="brand-title">Perfect Teeth</h1>
  </div>

  <div class="profile">
    <img src="./src/img/secretaria.png" class="rounded-circle border" alt="Perfil">
    <div class="name"><?= htmlspecialchars($usuario) ?></div>
    <div class="small text-muted">Panel Secretaría</div>
  </div>

  <nav class="nav-menu">
    <a href="secretaria/gestionar_pacientes.php" class="nav-link"><i class="bi bi-people"></i> Pacientes</a>
    <a href="gestionar_dentistas.php" class="nav-link active"><i class="bi bi-person-badge"></i> Dentistas</a>
    <a href="secretaria/pagos.php" class="nav-link"><i class="bi bi-cash-stack"></i> Pagos</a>
    <a href="secretaria/gestionar_citas.php" class="nav-link"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="secretaria/presupuestos_pendientes.php" class="nav-link"><i class="bi bi-clock-history"></i> Presupuestos</a>
    <a href="#" class="nav-link"><i class="bi bi-person-gear"></i> Perfil</a>
    <a href="./php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-primary"><i class="bi bi-person-badge"></i> Gestión de Dentistas</h5>
    <small class="text-muted"><?= $fecha ?> • <?= $hora ?></small>
  </div>

  <!-- Filtros -->
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-5"><input type="text" name="nombre" class="form-control" placeholder="Buscar por nombre" value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>"></div>
        <div class="col-md-5"><input type="text" name="apellido" class="form-control" placeholder="Buscar por apellido" value="<?= htmlspecialchars($_GET['apellido'] ?? '') ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button></div>
      </form>
    </div>
  </div>

  <!-- Lista -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="text-primary mb-0">Dentistas Registrados</h6>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarDentista">
          <i class="bi bi-plus-circle"></i> Nuevo Dentista
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th><th>Nombre</th><th>Apellido</th><th>Teléfono</th>
              <th>Correo</th><th>Especialidad</th><th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($d = mysqli_fetch_assoc($dentistas)) { ?>
            <tr>
              <td><?= $d['id_doctor'] ?></td>
              <td><?= htmlspecialchars($d['nombreD']) ?></td>
              <td><?= htmlspecialchars($d['apellido']) ?></td>
              <td><?= htmlspecialchars($d['telefono']) ?></td>
              <td><?= htmlspecialchars($d['correo_eletronico']) ?></td>
              <td><?= htmlspecialchars($d['id_especialidad']) ?></td>
              <td class="text-center">
                <button class="btn btn-warning btn-sm"
                  data-bs-toggle="modal" data-bs-target="#modalEditarDentista"
                  data-id="<?= $d['id_doctor'] ?>"
                  data-nombre="<?= htmlspecialchars($d['nombreD']) ?>"
                  data-apellido="<?= htmlspecialchars($d['apellido']) ?>"
                  data-telefono="<?= htmlspecialchars($d['telefono']) ?>"
                  data-sexo="<?= htmlspecialchars($d['sexo']) ?>"
                  data-correo="<?= htmlspecialchars($d['correo_eletronico']) ?>"
                  data-especialidad="<?= $d['id_especialidad'] ?>">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-danger btn-sm"
                  data-bs-toggle="modal" data-bs-target="#modalEliminarDentista"
                  data-id="<?= $d['id_doctor'] ?>">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <p class="text-muted small mb-0">Total de dentistas: <strong><?= $dentistas->num_rows ?></strong></p>
    </div>
  </div>
</div>

<!-- Modales (Agregar, Editar, Eliminar) -->
<!-- ... (El código de los modales y el script AJAX se mantiene similar, pero ahora dentro de una página completa) ... -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
