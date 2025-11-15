<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Verificar sesión de doctor
if (!isset($_SESSION['id_doctor'])) {
  $_SESSION['MensajeTexto'] = "Error: acceso no autorizado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

if (!empty($_GET['id'])) {
    $id = $_GET['id'];
    $row = ConsultarCitas($link, $id);
    $vUsuario = $_SESSION['id_doctor'];
    $row1 = consultarDoctor($link, $vUsuario);
    
    /* ====== Variables del sidebar estandarizado ====== */
    $SIDEBAR_ACTIVE = 'citas';
    $DOCTOR_NAME = htmlspecialchars(utf8_decode(($row1['nombreD'] ?? '').' '.($row1['apellido'] ?? '')));
    $DOCTOR_SEX = $row1['sexo'] ?? 'Masculino';
    $AVATAR_IMG = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';
} else {
    $_SESSION['MensajeTexto'] = "Error: ID de cita no válido.";
    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
    header("Location: inicioAdmin.php");
    exit;
}
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Perfect Teeth — Realizar Consulta</title>
    
    <!-- ICONO -->
    <link rel="icon" href="../src/img/logo.png" type="image/png" />
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="../src/css/lib/bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.min.css">
    
    <!-- ===== Diseño estandarizado mejorado ===== -->
    <style>
    :root{
      --brand:#0d6efd;
      --brand-hover:#0b5ed7;
      --brand-100:#e7f1ff;
      --brand-50:#f0f7ff;
      --surface:#f5f7fa;
      --text:#212529;
      --text-muted:#6c757d;
      --sidebar-w:240px;
      --maxw:1600px;
      --radius:10px;
      --transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    *{ box-sizing:border-box; margin:0; padding:0; }
    html, body { height:100%; }
    html{ overflow-y:auto; overflow-x:hidden; }
    body{
      margin:0;
      background:var(--surface);
      color:var(--text);
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    /* ===== Sidebar mejorado ===== */
    .sidebar{
      background:linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
      border-right:1px solid rgba(0,0,0,.05);
      box-shadow:2px 0 12px rgba(0,0,0,.03);
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      padding:1rem 0.75rem;
      overflow-y:auto;
      overflow-x:hidden;
      z-index:1030;
      transition:var(--transition);
    }
    .sidebar::-webkit-scrollbar{ width:4px; }
    .sidebar::-webkit-scrollbar-track{ background:transparent; }
    .sidebar::-webkit-scrollbar-thumb{ background:rgba(0,0,0,.1); border-radius:2px; }
    .sidebar::-webkit-scrollbar-thumb:hover{ background:rgba(0,0,0,.2); }
    .toggle, .js-menu-toggle{ display:none !important; }

    .brand{ 
      display:flex; align-items:center; gap:.65rem; 
      padding:.75rem .65rem; border-radius:var(--radius);
      margin-bottom:1rem;
      transition:var(--transition);
      cursor:pointer;
    }
    .brand:hover{ background:var(--brand-50); transform:translateX(2px); }
    .brand img{ transition:var(--transition); }
    .brand:hover img{ transform:scale(1.05); }
    .brand-title{ margin:0; font-weight:700; letter-spacing:.2px; color:var(--brand); font-size:1rem; }

    .side-inner{ padding-bottom:0.5rem; }
    .profile{ 
      text-align:center; 
      margin:0.75rem 0 1rem;
      padding:0.75rem;
      background:var(--brand-50);
      border-radius:var(--radius);
      transition:var(--transition);
    }
    .profile:hover{ background:var(--brand-100); box-shadow:0 2px 8px rgba(13,110,253,.1); }
    .profile img{ 
      width:80px; height:80px; 
      object-fit:cover;
      border:3px solid #fff;
      box-shadow:0 2px 8px rgba(0,0,0,.1);
      border-radius:50%;
      transition:var(--transition);
    }
    .profile:hover img{ transform:scale(1.05); box-shadow:0 4px 12px rgba(0,0,0,.15); }
    .profile .name{ margin:.5rem 0 .2rem; font-weight:600; font-size:0.95rem; }
    .profile .country{ font-size:0.8rem; color:var(--text-muted); }

    .nav-menu{ display:flex; flex-direction:column; gap:0.25rem; list-style:none; }
    .nav-menu li{ margin:0; }
    .nav-menu .nav-link{
      display:flex; align-items:center; gap:.65rem;
      border-radius:var(--radius); padding:.7rem .75rem;
      color:var(--text); text-decoration:none;
      font-size:0.9rem;
      transition:var(--transition);
      position:relative;
      margin:0 0.25rem;
    }
    .nav-menu .nav-link i{
      width:20px;
      text-align:center;
      transition:var(--transition);
    }
    .nav-menu .nav-link:hover{
      background:var(--brand-100); color:var(--brand);
      text-decoration:none; font-weight:600;
      transform:translateX(4px);
      box-shadow:0 2px 6px rgba(13,110,253,.15);
    }
    .nav-menu .nav-link:hover i{
      transform:scale(1.15);
      color:var(--brand);
    }
    .nav-menu .nav-link.active{
      background:linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%);
      color:#fff;
      font-weight:600;
      box-shadow:0 4px 12px rgba(13,110,253,.3);
    }
    .nav-menu .nav-link.active i{ color:#fff; }

    /* ===== Main mejorado ===== */
    main{ 
      margin-left:var(--sidebar-w); 
      min-height:100vh; 
      display:flex; 
      flex-direction:column;
      transition:var(--transition);
    }
    .container-max{ 
      width:100%; 
      max-width:var(--maxw); 
      margin:0 auto; 
      padding:2rem; 
    }

    /* Topbar */
    .topbar{
      background:#fff;
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:1rem 0;
      position:sticky; top:0; z-index:10;
      box-shadow:0 2px 8px rgba(0,0,0,.03);
    }

    /* Content */
    .content{ padding:1.5rem 0 2rem; }
    
    /* Card mejorado */
    .card{
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 4px 16px rgba(0,0,0,.08);
      transition:var(--transition);
      overflow:hidden;
      margin-bottom:1.5rem;
    }
    .card:hover{ 
      box-shadow:0 8px 24px rgba(0,0,0,.12); 
      transform:translateY(-2px);
    }
    .card-header{
      background:linear-gradient(135deg, #fff 0%, #fafbfc 100%);
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:1.25rem 1.75rem;
    }
    .card-body{
      padding:1.75rem;
    }
    .card-title{
      color:var(--brand);
      font-weight:700;
      font-size:1.25rem;
      margin-bottom:0.5rem;
    }

    /* Form mejorado */
    .form-group{
      margin-bottom:1.5rem;
    }
    .form-label{
      font-weight:600;
      color:var(--text);
      margin-bottom:0.5rem;
      font-size:0.95rem;
      display:block;
    }
    .form-control{
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.15);
      padding:0.75rem 1rem;
      transition:var(--transition);
      font-size:0.95rem;
      width:100%;
    }
    .form-control:focus{
      border-color:var(--brand);
      box-shadow:0 0 0 0.2rem rgba(13,110,253,.15);
      outline:none;
      transform:translateY(-1px);
    }
    textarea.form-control{
      resize:vertical;
      min-height:120px;
    }

    /* Buttons mejorados */
    .btn{
      border-radius:var(--radius);
      padding:0.75rem 1.5rem;
      font-weight:600;
      transition:var(--transition);
      border:none;
    }
    .btn-success{
      background:linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color:#fff;
      box-shadow:0 4px 12px rgba(40,167,69,.3);
    }
    .btn-success:hover{
      transform:translateY(-2px);
      box-shadow:0 6px 16px rgba(40,167,69,.4);
    }
    .btn-outline-secondary{
      border:1px solid rgba(0,0,0,.15);
      color:var(--text);
    }
    .btn-outline-secondary:hover{
      background:var(--brand-100);
      border-color:var(--brand);
      color:var(--brand);
      transform:translateY(-2px);
    }

    .alert{
      border-radius:var(--radius);
      border:none;
      box-shadow:0 2px 8px rgba(0,0,0,.1);
    }

    .breadcrumb{
      background:transparent !important;
      padding:0;
      margin-bottom:1.5rem;
    }

    /* Responsive */
    @media (max-width:992px){
      :root{ --sidebar-w:220px; }
      .sidebar{ padding:0.75rem 0.5rem; }
      .container-max{ padding:0 1rem; }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:200px; }
      .container-max{ padding:0 0.75rem; }
      .content{ padding:1rem 0 1.5rem; }
    }
    </style>
</head>

<body>
<div class="app">
  <!-- ===== Sidebar estandarizado ===== -->
  <aside class="sidebar">
    <div class="brand mb-2">
      <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="side-inner">
      <div class="profile">
        <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle border" alt="Perfil">
        <div class="name"><?php echo $DOCTOR_NAME; ?></div>
        <div class="country">Panel de odontólogo</div>
      </div>

      <nav class="nav-menu">
        <ul>
          <li>
            <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='citas'?'active':''); ?>" href="inicioAdmin.php">
              <i class="far fa-calendar-check"></i><span>Citas pendientes</span>
            </a>
          </li>
          <li>
            <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='calendario'?'active':''); ?>" href="calendar.php">
              <i class="far fa-calendar-alt"></i><span>Calendario</span>
            </a>
          </li>
          <li>
            <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='odontograma'?'active':''); ?>" href="../odontograma.php">
              <i class="fas fa-tooth"></i><span>Odontograma</span>
            </a>
          </li>
          <li>
            <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='presupuestos'?'active':''); ?>" href="presupuestos_doctor.php">
              <i class="fas fa-file-invoice-dollar"></i><span>Presupuestos</span>
            </a>
          </li>
          <li>
            <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='reportes'?'active':''); ?>" href="reportes_doctor.php">
              <i class="fas fa-chart-line"></i><span>Reportes</span>
            </a>
          </li>
          <li>
            <a class="nav-link" href="../php/cerrar.php">
              <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- ===== Main ===== -->
  <main>
    <div class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-stethoscope text-primary"></i>
          <span class="text-muted">Realizar Consulta</span>
        </div>
        <div class="small text-muted d-none d-sm-block">
          Usuario:  <strong><?php echo htmlspecialchars($row1['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-max">
        <?php if (!empty($_SESSION['MensajeTexto'])): ?>
          <div class="alert <?php echo $_SESSION['MensajeTipo'] ?? 'alert-info'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['MensajeTexto']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>
          <?php $_SESSION['MensajeTexto'] = null; $_SESSION['MensajeTipo'] = null; ?>
        <?php endif; ?>

        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="inicioAdmin.php">Inicio</a></li>
            <li class="breadcrumb-item active">Realizar Consulta</li>
          </ol>
        </nav>

        <div class="card">
          <div class="card-header">
            <h2 class="card-title mb-0">
              <i class="fas fa-stethoscope me-2"></i>Realizar Diagnóstico
            </h2>
          </div>
          <div class="card-body">
            <form action="../crud/realizar_consultasUPDATE.php?accion=UDT" method="POST" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars($row['id_cita'] ?? ''); ?>">

              <div class="alert alert-info mb-4">
                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Información del Paciente</h5>
                <p class="mb-0">
                  <strong>Paciente:</strong> <?php echo htmlspecialchars(($row['nombre'] ?? '').' '.($row['apellido'] ?? '')); ?><br>
                  <strong>Fecha de cita:</strong> <?php echo htmlspecialchars($row['fecha_cita'] ?? ''); ?> 
                  a las <?php echo htmlspecialchars($row['hora_cita'] ?? ''); ?><br>
                  <strong>Tipo de consulta:</strong> <?php echo htmlspecialchars($row['tipo'] ?? ''); ?>
                </p>
              </div>

              <div class="card mb-4">
                <div class="card-body">
                  <h5 class="card-title"><i class="fas fa-clipboard-check me-2"></i>Diagnóstico</h5>
                  <p class="card-text text-muted">
                    Con el fin de identificar dicha enfermedad o afección mediante una buena interpretación de los resultados obtenidos. 
                    En ocasiones asisten pacientes a la consulta dental que solo desean resolver un problema que les aqueja en ese momento.
                  </p>
                </div>
              </div>

              <div class="row g-4 mb-4">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="descripcion" class="form-label">
                      <i class="fas fa-file-medical me-2"></i>Descripción del Diagnóstico
                    </label>
                    <textarea 
                      class="form-control" 
                      id="descripcion" 
                      name="Descripción" 
                      rows="5" 
                      placeholder="Ingrese la descripción del diagnóstico..."
                      required></textarea>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="medicina" class="form-label">
                      <i class="fas fa-pills me-2"></i>Medicina (Opcional)
                    </label>
                    <textarea 
                      class="form-control" 
                      id="medicina" 
                      name="Medicina" 
                      rows="5" 
                      placeholder="Ingrese medicamentos o tratamientos prescritos..."></textarea>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="inicioAdmin.php" class="btn btn-outline-secondary">
                  <i class="fas fa-arrow-left me-2"></i>Atrás
                </a>
                <button class="btn btn-success btn-lg" type="submit" name="guardar" value="Guardar">
                  <i class="far fa-save me-2"></i>Guardar Diagnóstico
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="../src/js/jquery.js"></script>
<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>