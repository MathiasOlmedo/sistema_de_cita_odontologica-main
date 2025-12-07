<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (isset($_SESSION['id_paciente'])) {
     $vUsuario = $_SESSION['id_paciente'];
     $row = consultarPaciente($link, $vUsuario);
} else {
     $_SESSION['MensajeTexto'] = "Error: acceso al sistema no registrado.";
     $_SESSION['MensajeTipo'] = "danger";
     header("Location: ./index.php");
     exit();
}

/* ====== Variables del sidebar ====== */
$SIDEBAR_ACTIVE = 'perfil';
$PATIENT_NAME = htmlspecialchars($row['nombre'].' '.$row['apellido']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth – Mi Perfil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="./src/img/logo.png" type="image/png" />
  
  <link rel="stylesheet" href="src/css/bootstrap.min.css">
  <link rel="stylesheet" href="src/css/font-awesome.min.css">
  
  <style>
    :root{
      --brand:#0d6efd; --brand-100:#e7f1ff;
      --surface:#f8f9fa; --text:#212529;
      --sidebar-w:260px; --maxw:1400px; --radius:12px;
    }
    *{ box-sizing:border-box; }
    html, body { height:100%; }
    html{ overflow-y:auto; overflow-x:hidden; }
    body{
      margin:0; background:var(--surface); color:var(--text);
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* ===== Sidebar fijo SIEMPRE visible ===== */
    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff;
      border-right:0 !important;
      box-shadow:2px 0 10px rgba(0,0,0,.05);
      transform:none !important;
      z-index:1030;
      padding:1.25rem 1rem;
      overflow-y:auto;
      overflow-x:hidden;
    }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
    
    .brand{ display:flex; align-items:center; gap:.75rem; padding:.5rem .75rem; border-radius:.75rem; }
    .brand-title{ margin:0; font-weight:700; letter-spacing:.3px; color:var(--brand); font-size:1.05rem; }

    .patient-info{
      text-align:center; 
      margin:1.5rem 0;
      padding:1rem;
      background:linear-gradient(135deg, var(--brand) 0%, #0056d2 100%);
      border-radius:var(--radius);
      color:white;
      box-shadow:0 4px 15px rgba(13,110,253,0.3);
    }
    .patient-info .name{ margin:.5rem 0 .2rem; font-weight:600; font-size:1.1rem; }
    .patient-info .email{ font-size:0.85rem; opacity:0.9; word-break:break-word; }

    .nav-menu{ display:flex; flex-direction:column; gap:.25rem; }
    .nav-menu .nav-link{
      display:flex; align-items:center; gap:.6rem;
      border-radius:.6rem; padding:.6rem .75rem;
      color:#495057; text-decoration:none;
      transition:all 0.2s ease;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100); color:var(--brand);
      text-decoration:none; font-weight:600;
    }
    .nav-menu .nav-link i{
      width:18px; text-align:center;
    }

    /* ===== Main empujado por sidebar ===== */
    main{
      margin-left:var(--sidebar-w);
      min-height:100vh;
      display:flex; flex-direction:column;
    }
    .container-max{ width:100%; max-width:var(--maxw); margin:0 auto; padding:0 1rem; }

    .topbar{
      background:#fff; border-bottom:1px solid rgba(0,0,0,.06);
      padding:.75rem 0; position:sticky; top:0; z-index:10;
      box-shadow:0 2px 4px rgba(0,0,0,.04);
    }

    .content{ padding:1.5rem 0 2rem; }

    /* ===== Cards ===== */
    .card{
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 6px 16px rgba(15,23,42,.06);
      background:white;
      margin-bottom:1.5rem;
      transition:all 0.3s ease;
    }
    .card:hover{
      transform:translateY(-3px);
      box-shadow:0 10px 25px rgba(15,23,42,.12);
    }

    /* ===== Perfil ===== */
    .profile-card{
      text-align:center;
      padding:2rem;
    }

    .profile-card img{
      width:150px;
      height:150px;
      object-fit:cover;
      border-radius:50%;
      border:5px solid var(--brand);
      box-shadow:0 8px 25px rgba(13,110,253,0.3);
      margin-bottom:1.5rem;
    }

    .profile-card h3{
      margin:1rem 0 0.5rem;
      font-size:1.8rem;
      font-weight:700;
      color:#333;
    }

    .profile-card .subtitle{
      color:#6c757d;
      font-size:1rem;
      margin-bottom:0.5rem;
    }

    .profile-card .email-badge{
      display:inline-block;
      background:var(--brand-100);
      color:var(--brand);
      padding:0.5rem 1rem;
      border-radius:20px;
      font-size:0.9rem;
      margin-top:0.5rem;
    }

    .action-buttons{
      display:grid;
      grid-template-columns:1fr;
      gap:1rem;
      margin-top:2rem;
    }

    .btn-gradient{
      background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color:white;
      border:none;
      padding:12px 30px;
      border-radius:25px;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:1px;
      transition:all 0.3s ease;
      box-shadow:0 4px 15px rgba(102,126,234,0.3);
      text-decoration:none;
      display:inline-block;
      text-align:center;
    }
    
    .btn-gradient:hover{
      transform:translateY(-3px);
      box-shadow:0 6px 25px rgba(102,126,234,0.5);
      color:white;
      text-decoration:none;
    }

    .btn-gradient.success{
      background:linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      box-shadow:0 4px 15px rgba(17,153,142,0.3);
    }

    .btn-gradient.success:hover{
      box-shadow:0 6px 25px rgba(17,153,142,0.5);
    }

    .btn-gradient.warning{
      background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      box-shadow:0 4px 15px rgba(240,147,251,0.3);
    }

    .btn-gradient.warning:hover{
      box-shadow:0 6px 25px rgba(240,147,251,0.5);
    }

    /* ===== Info Card ===== */
    .info-card{
      padding:1.5rem;
    }

    .info-row{
      display:flex;
      padding:1rem 0;
      border-bottom:1px solid #f0f0f0;
      align-items:center;
    }

    .info-row:last-child{
      border-bottom:none;
    }

    .info-label{
      font-weight:700;
      color:#333;
      width:180px;
      flex-shrink:0;
    }

    .info-value{
      color:#6c757d;
      flex:1;
    }

    /* ===== Alertas ===== */
    .alert{
      padding:1rem 1.25rem;
      border-radius:var(--radius);
      border:1px solid transparent;
      margin-bottom:1.5rem;
    }
    .alert-success{
      background:#d1f4e0;
      border-color:#b8ecd0;
      color:#0f6938;
    }
    .alert-danger{
      background:#ffe3e8;
      border-color:#ffd0d9;
      color:#c41e3a;
    }
    .alert .close{
      float:right;
      background:none;
      border:none;
      font-size:1.5rem;
      cursor:pointer;
      opacity:0.5;
      color:inherit;
    }
    .alert .close:hover{
      opacity:1;
    }

    /* ===== Utilidades ===== */
    .d-flex{ display:flex !important; }
    .align-items-center{ align-items:center !important; }
    .justify-content-between{ justify-content:space-between !important; }
    .gap-2{ gap:0.5rem !important; }
    .gap-3{ gap:1rem !important; }
    .text-muted{ color:#6c757d !important; }

    /* ===== Responsive ===== */
    @media (max-width:992px){
      :root{ --sidebar-w:240px; }
      .sidebar{ width:var(--sidebar-w); }
      main{ margin-left:var(--sidebar-w); }
    }
    @media (max-width:768px){
      .info-row{
        flex-direction:column;
        align-items:flex-start;
      }
      .info-label{
        width:100%;
        margin-bottom:0.5rem;
      }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:220px; }
      .sidebar{ width:var(--sidebar-w); }
      main{ margin-left:var(--sidebar-w); }
    }
  </style>
</head>

<body>
  <!-- ===== Sidebar ===== -->
  <aside class="sidebar">
    <div class="brand mb-3">
      <img src="./src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="patient-info">
      <div class="name"><?php echo $PATIENT_NAME; ?></div>
      <div class="email"><?php echo htmlspecialchars($row['correo_electronico']); ?></div>
    </div>

    <nav class="nav-menu">
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='panel'?'active':''); ?>" href="principal.php">
        <i class="fa fa-tachometer"></i><span>Mi Panel</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='citas'?'active':''); ?>" href="mis_citas.php">
        <i class="fa fa-calendar-check-o"></i><span>Mis Citas</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='agendar'?'active':''); ?>" href="agendar_cita.php">
        <i class="fa fa-calendar-plus-o"></i><span>Nueva Cita</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='presupuestos'?'active':''); ?>" href="mis_presupuestos.php">
        <i class="fa fa-file-text-o"></i><span>Mis Presupuestos</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='tratamientos'?'active':''); ?>" href="mis_tratamientos.php">
        <i class="fa fa-medkit"></i><span>Mis Tratamientos</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='perfil'?'active':''); ?>" href="mi_perfil.php">
        <i class="fa fa-user-circle"></i><span>Mi Perfil</span>
      </a>
      <a class="nav-link" href="./Reportes/reporte.php" target="_blank">
        <i class="fa fa-file-pdf-o"></i><span>Reporte de Citas</span>
      </a>
      <a class="nav-link" href="./Reportes/reporteH.php" target="_blank">
        <i class="fa fa-history"></i><span>Historial Completo</span>
      </a>
      <a class="nav-link" href="./php/cerrar.php">
        <i class="fa fa-sign-out"></i><span>Cerrar Sesión</span>
      </a>
    </nav>
  </aside>

  <!-- ===== Main ===== -->
  <main>
    <!-- Topbar -->
    <header class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fa fa-user-circle" style="color:#0d6efd;"></i>
          <span style="font-weight:600;">Mi Perfil</span>
        </div>
      </div>
    </header>

    <!-- Contenido -->
    <div class="content">
      <div class="container-max">

        <!-- Mensajes -->
        <?php if (isset($_SESSION['MensajeTexto'])): ?>
          <div class="alert <?php echo strpos($_SESSION['MensajeTipo'], 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
            <button type="button" class="close" onclick="this.parentElement.style.display='none'"><span>&times;</span></button>
            <strong><i class="fa fa-info-circle"></i></strong> <?php echo htmlspecialchars($_SESSION['MensajeTexto']); ?>
          </div>
          <?php 
            $_SESSION['MensajeTexto'] = null;
            $_SESSION['MensajeTipo'] = null;
          ?>
        <?php endif; ?>

        <div class="row">
          
          <!-- Columna Izquierda: Foto y Acciones -->
          <div class="col-md-4">
            <div class="card">
              <div class="profile-card">
                <?php if ($row['sexo'] == 'Masculino'): ?>
                  <img src="./src/img/iconoH.jpg" alt="Perfil">
                <?php else: ?>
                  <img src="./src/img/iconoM.jpg" alt="Perfil">
                <?php endif; ?>

                <h3><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></h3>
                <p class="subtitle">Perfect Teeth</p>
                <div class="email-badge">
                  <i class="fa fa-envelope"></i> 
                  <?php echo htmlspecialchars($row['correo_electronico']); ?>
                </div>

                <div class="action-buttons">
                  <a class="btn-gradient" href="./editar_paciente.php">
                    <i class="fa fa-edit"></i> Editar Perfil
                  </a>
                  
                  <a class="btn-gradient success" target="_blank" href="./Reportes/reporte.php">
                    <i class="fa fa-file-pdf-o"></i> Reporte de Citas
                  </a>
                  
                  <a class="btn-gradient warning" target="_blank" href="./Reportes/reporteH.php">
                    <i class="fa fa-history"></i> Historial Completo
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Columna Derecha: Información Personal -->
          <div class="col-md-8">
            <div class="card">
              <div class="info-card">
                <h4 style="margin:0 0 1.5rem; color:#333; font-weight:700;">
                  <i class="fa fa-info-circle" style="color:#0d6efd;"></i> 
                  Información Personal
                </h4>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-user"></i> Nombre
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($row['nombre']); ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-user"></i> Apellido
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($row['apellido']); ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-id-card"></i> Cédula
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($row['cedula']); ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-venus-mars"></i> Sexo
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($row['sexo']); ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-envelope"></i> Correo
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($row['correo_electronico']); ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-phone"></i> Teléfono
                  </div>
                  <div class="info-value">
                    <?php echo htmlspecialchars($row['telefono']); ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-birthday-cake"></i> Fecha de Nacimiento
                  </div>
                  <div class="info-value">
                    <?php echo date('d/m/Y', strtotime($row['fecha_nacimiento'])); ?>
                  </div>
                </div>

                <?php
                // Calcular edad
                $fecha_nac = new DateTime($row['fecha_nacimiento']);
                $hoy = new DateTime();
                $edad = $hoy->diff($fecha_nac)->y;
                ?>

                <div class="info-row">
                  <div class="info-label">
                    <i class="fa fa-calendar"></i> Edad
                  </div>
                  <div class="info-value">
                    <?php echo $edad; ?> años
                  </div>
                </div>

              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </main>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="src/js/bootstrap.min.js"></script>
</body>
</html>