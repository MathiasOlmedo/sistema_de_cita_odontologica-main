<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION['id_doctor'])) {
  $_SESSION['MensajeTexto'] = "Error: acceso no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

$id_doctor = (int)$_SESSION['id_doctor'];
$row = consultarDoctor($link, $id_doctor);

/* ====== Variables del sidebar ====== */
$SIDEBAR_ACTIVE = 'historial';
$DOCTOR_NAME    = htmlspecialchars(utf8_decode(($row['nombreD'] ?? '').' '.($row['apellido'] ?? '')));
$DOCTOR_SEX     = $row['sexo'] ?? 'Masculino';
$AVATAR_IMG     = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';

/* ====== Buscar pacientes ====== */
$search = trim($_GET['search'] ?? '');
$where = "1=1";
if ($search !== '') {
  $search_safe = mysqli_real_escape_string($link, $search);
  $where .= " AND (p.nombre LIKE '%$search_safe%' OR p.apellido LIKE '%$search_safe%' OR p.cedula LIKE '%$search_safe%')";
}

$sql = "
  SELECT p.id_paciente, p.nombre, p.apellido, p.cedula, p.telefono, p.sexo,
         COUNT(h.id_historial) AS total_historiales,
         MAX(h.fecha_registro) AS ultimo_registro
  FROM pacientes p
  LEFT JOIN historial_medico h ON h.id_paciente = p.id_paciente AND h.id_doctor = $id_doctor
  WHERE $where
  GROUP BY p.id_paciente
  ORDER BY p.apellido ASC, p.nombre ASC
  LIMIT 50
";
$pacientes = mysqli_query($link, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth — Historial Médico</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="../src/img/logo.png" type="image/png" />
  <link rel="stylesheet" href="../src/css/lib/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <style>
    :root{
      --brand:#0d6efd; --brand-100:#e7f1ff;
      --surface:#f8f9fa; --text:#212529;
      --sidebar-w:260px; --maxw:1200px; --radius:12px;
    }
    *{ box-sizing:border-box; }
    html, body { height:100%; }
    body{ margin:0; background:var(--surface); color:var(--text); }

    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff; border-right:0 !important;
      padding:1.25rem 1rem; overflow:hidden; z-index:1030;
    }
    .brand{ display:flex; align-items:center; gap:.75rem; }
    .brand-title{ margin:0; font-weight:700; color:var(--brand); font-size:1.05rem; }
    .profile{ text-align:center; margin:1rem 0 1.25rem; }
    .profile img{ width:96px; height:96px; object-fit:cover; }
    .profile .name{ margin:.6rem 0 .1rem; font-weight:600; }
    .nav-menu .nav-link{
      display:flex; align-items:center; gap:.6rem;
      border-radius:.6rem; padding:.6rem .75rem;
      color:#495057; text-decoration:none;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100); color:var(--brand); font-weight:600;
    }

    .main{ margin-left:var(--sidebar-w); min-height:100vh; padding:1.5rem; }
    .topbar{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); padding:.75rem 1rem; position:sticky; top:0; z-index:10; }
    .card{ border-radius:var(--radius); border:1px solid rgba(0,0,0,.06); box-shadow:0 6px 16px rgba(15,23,42,.06); }
    .patient-card{
      transition: all 0.3s ease;
      cursor: pointer;
      border-left: 4px solid transparent;
    }
    .patient-card:hover{
      border-left-color: var(--brand);
      transform: translateX(5px);
      box-shadow: 0 4px 12px rgba(13,110,253,0.2);
    }
   .badge-historial {
  background: linear-gradient(135deg, #5baeff 0%, #0d6efd 100%) !important;
  color: white;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.85rem;
}
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
    <img src="<?= $AVATAR_IMG ?>" class="rounded-circle" alt="Perfil">
    <h3 class="name"><?= $DOCTOR_NAME ?></h3>
    <span class="country text-muted">Perfect Teeth</span>
  </div>

  <nav class="nav-menu">
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='citas'?'active':'') ?>" href="inicioAdmin.php">
      <i class="far fa-calendar-check"></i><span>Citas pendientes</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='calendario'?'active':'') ?>" href="calendar.php">
      <i class="far fa-calendar-alt"></i><span>Calendario</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='historial'?'active':'') ?>" href="historial_medico.php">
      <i class="fas fa-notes-medical"></i><span>Historial Médico</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='odontograma'?'active':'') ?>" href="../odontograma.php">
      <i class="fas fa-tooth"></i><span>Odontograma</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='presupuestos'?'active':'') ?>" href="presupuestos_doctor.php">
      <i class="fas fa-file-invoice-dollar"></i><span>Presupuestos</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='reportes'?'active':'') ?>" href="reportes_doctor.php">
      <i class="fas fa-chart-line"></i><span>Reportes</span>
    </a>
    <a class="nav-link" href="../php/cerrar.php">
      <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
    </a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-primary"><i class="fas fa-notes-medical"></i> Historial Médico</h5>
    <small class="text-muted"><?= htmlspecialchars($row['correo_eletronico'] ?? '') ?></small>
  </div>

  <!-- Buscador -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <div class="col-md-10">
          <input type="text" name="search" class="form-control" 
                 placeholder="🔍 Buscar paciente por nombre, apellido o cédula..."
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search"></i> Buscar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Lista de Pacientes -->
  <div class="card">
    <div class="card-header bg-white">
      <h6 class="mb-0 text-primary">
        <i class="fas fa-users"></i> Selecciona un paciente para gestionar su historial
      </h6>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <?php 
        if (mysqli_num_rows($pacientes) === 0): ?>
          <div class="col-12 text-center text-muted py-4">
            <i class="fas fa-user-slash fa-3x mb-3"></i>
            <p>No se encontraron pacientes</p>
          </div>
        <?php else: 
          while ($p = mysqli_fetch_assoc($pacientes)): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card patient-card h-100" 
                 onclick="location.href='historial_paciente.php?id=<?= $p['id_paciente'] ?>'">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h6 class="mb-0">
                    <?= htmlspecialchars($p['nombre'].' '.$p['apellido']) ?>
                  </h6>
                  <span class="badge-historial">
                    <i class="fas fa-file-medical"></i> <?= $p['total_historiales'] ?>
                  </span>
                </div>
                <div class="small text-muted">
                  <div><i class="fas fa-id-card"></i> CI: <?= htmlspecialchars($p['cedula']) ?></div>
                  <div><i class="fas fa-phone"></i> <?= htmlspecialchars($p['telefono']) ?></div>
                  <div><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($p['sexo']) ?></div>
                  <?php if ($p['ultimo_registro']): ?>
                    <div class="mt-2 text-success">
                      <i class="fas fa-clock"></i> Último registro: 
                      <?= date('d/m/Y H:i', strtotime($p['ultimo_registro'])) ?>
                    </div>
                  <?php else: ?>
                    <div class="mt-2 text-warning">
                      <i class="fas fa-exclamation-triangle"></i> Sin registros
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; 
        endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>