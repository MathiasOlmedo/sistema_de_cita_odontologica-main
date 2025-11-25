<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ====== CONTROL DE SESIÓN ====== */
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] !== 'Secretaria') {
    $_SESSION['MensajeTexto'] = "Acceso no autorizado.";
    $_SESSION['MensajeTipo']  = "p-3 mb-2 bg-danger text-white";
    header("Location: ../index.php");
    exit;
}

$usuario = $_SESSION['nombre'] ?? 'Secretaría';

date_default_timezone_set("America/Asuncion");
$fecha = date("d/m/Y");
$hora  = date("H:i");

/* ====== Sidebar variables (estándar) ====== */
$AVATAR_IMG = "../src/img/secretaria.png";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Secretaría - Perfect Teeth</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <style>
    :root{
      --brand:#0d6efd; --brand-100:#e7f1ff;
      --surface:#f8f9fa; --text:#212529;
      --sidebar-w:260px; --maxw:1200px; --radius:12px;
    }
    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{
      margin:0; background:var(--surface); color:var(--text);
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }
    /* Sidebar */
    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff; padding:1.25rem 1rem;
      border-right:0; box-shadow:none; overflow:hidden;
      z-index:1030;
    }
    .sidebar .brand{ display:flex; align-items:center; gap:.75rem; padding:.5rem .75rem; }
    .brand-title{ font-weight:700; color:var(--brand); font-size:1.05rem; margin:0; }
    .profile{text-align:center; margin:1rem 0 1.25rem;}
    .profile img{width:96px;height:96px;object-fit:cover;}
    .profile .name{margin:.6rem 0 .1rem;font-weight:600;}
    .nav-menu .nav-link{
      display:flex;align-items:center;gap:.6rem;
      border-radius:.6rem;padding:.6rem .75rem;
      color:#495057;text-decoration:none;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100);
      color:var(--brand);font-weight:600;text-decoration:none;
    }
    /* Main */
    .main{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;}
    .container-max{width:100%;max-width:var(--maxw);margin:0 auto;padding:0 1.25rem;}
    .topbar{
      background:#fff;border-bottom:1px solid rgba(0,0,0,.06);
      padding:.75rem 0;position:sticky;top:0;z-index:10;
    }
    .content{padding:1.25rem 0 2rem;}
    .card{border-radius:var(--radius);border:1px solid rgba(0,0,0,.06);box-shadow:0 6px 16px rgba(15,23,42,.06);}
    .footer{text-align:right;font-size:.9rem;color:#6c757d;padding:1rem 0;}
    @media (max-width:992px){:root{--sidebar-w:240px}.sidebar{width:var(--sidebar-w)}.main{margin-left:var(--sidebar-w)}}
    @media (max-width:575.98px){:root{--sidebar-w:220px}.sidebar{width:var(--sidebar-w)}.main{margin-left:var(--sidebar-w)}}
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
    <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle border" alt="Perfil">
    <div class="name"><?php echo htmlspecialchars($usuario); ?></div>
    <div class="text-muted small">Panel Secretaría</div>
  </div>

  <nav class="nav-menu">
    <a class="nav-link active" href="#" onclick="loadContent('gestionar_pacientes.php')">
      <i class="bi bi-people"></i><span>Pacientes</span>
    </a>
    <a class="nav-link" href="#" onclick="loadContent('pagos.php')">
      <i class="bi bi-cash-stack"></i><span>Pagos</span>
    </a>
    <a class="nav-link" href="#" onclick="loadContent('gestionar_citas.php')">
      <i class="bi bi-calendar-check"></i><span>Citas</span>
    </a>
    <a class="nav-link" href="#" onclick="loadContent('presupuestos_pendientes.php')">
      <i class="bi bi-clock-history"></i><span>Presupuestos Pendientes</span>
    </a>
  
    <a class="nav-link" href="#" onclick="loadContent('perfil_secretaria.php')">
      <i class="bi bi-person-gear"></i><span>Perfil</span>
    </a>
    <a class="nav-link text-danger" href="../php/cerrar.php">
      <i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>
    </a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <!-- Topbar -->
  <header class="topbar">
    <div class="container-max d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-speedometer2 text-primary"></i>
        <span class="text-muted">Panel de Secretaría</span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-sm-inline"><?php echo $fecha . " • " . $hora; ?></span>
        <span class="small"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($usuario); ?></span>
      </div>
    </div>
  </header>

  <!-- Content -->
  <main class="content">
    <div class="container-max">
      <div id="dynamic-content">
        <div class="card">
          <div class="card-body">
            <h5 class="text-primary fw-bold mb-2"><i class="bi bi-info-circle"></i> Bienvenida</h5>
            <p class="mb-0">Selecciona un módulo en el menú lateral para gestionar pacientes, citas, pagos o presupuestos.</p>
          </div>
        </div>
      </div>
      <div class="footer">
        Sistema de Citas Odontológicas © <?php echo date("Y"); ?>
      </div>
    </div>
  </main>
</div>

<script>
function loadContent(page) {
  $('.nav-menu .nav-link').removeClass('active');
  $('.nav-menu .nav-link[onclick*="'+page+'"]').addClass('active');

  $.ajax({
    url: page,
    type: "GET",
    success: function(response) {
      $("#dynamic-content").html(response);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    error: function() {
      $("#dynamic-content").html("<div class='card'><div class='card-body'><p class='text-danger mb-0'>Error al cargar el contenido.</p></div></div>");
    }
  });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
