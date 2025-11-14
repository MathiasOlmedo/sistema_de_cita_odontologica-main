<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Verificar si hay sesión de doctor (compatibilidad con id_doctor o id_usuario + tipo Doctor)
if (isset($_SESSION['id_doctor'])) {
  $vUsuario = $_SESSION['id_doctor'];
} elseif (isset($_SESSION['id_usuario']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'Doctor') {
  // Si hay id_usuario con tipo Doctor, establecer id_doctor para compatibilidad
  $_SESSION['id_doctor'] = $_SESSION['id_usuario'];
  $vUsuario = $_SESSION['id_doctor'];
} else {
  $_SESSION['MensajeTexto'] = "Error: acceso al sistema no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

// Obtener datos del doctor y sus citas
$row = consultarDoctor($link, $vUsuario);
$resultadoCitas = MostrarCitas($link, $vUsuario); // mostrar citas

/* ====== Variables estandarizadas para el sidebar ====== */
$SIDEBAR_ACTIVE = 'citas'; // 'citas' | 'calendario' | 'odontograma'
$DOCTOR_NAME    = utf8_decode(($row['nombreD'] ?? '').' '.($row['apellido'] ?? ''));
$DOCTOR_SEX     = $row['sexo'] ?? 'Masculino';
$AVATAR_IMG     = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth — Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- Favicon -->
  <link rel="icon" href="../src/img/logo.png" type="image/png" />
  <!-- Bootstrap -->
  <link rel="stylesheet" href="../src/css/lib/bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../src/js/lib/datatable/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="../src/js/lib/datatable/css/responsive.dataTables.min.css">

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
      font-feature-settings:"liga" 1, "calt" 1;
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
      transition:var(--transition);
    }
    .profile:hover img{ transform:scale(1.05); box-shadow:0 4px 12px rgba(0,0,0,.15); }
    .profile .name{ margin:.5rem 0 .2rem; font-weight:600; font-size:0.95rem; color:var(--text); }
    .profile .text-muted{ font-size:0.8rem; color:var(--text-muted); }

    .nav-menu{ display:flex; flex-direction:column; gap:0.25rem; }
    .nav-menu .nav-link{
      border-radius:var(--radius);
      color:var(--text);
      display:flex; align-items:center; gap:.65rem;
      padding:.7rem .75rem;
      text-decoration:none;
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
      background:var(--brand-100);
      color:var(--brand);
      text-decoration:none;
      font-weight:600;
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
    .nav-menu .nav-link.active::before{
      content:'';
      position:absolute;
      left:-0.75rem;
      top:50%;
      transform:translateY(-50%);
      width:4px;
      height:60%;
      background:var(--brand);
      border-radius:0 4px 4px 0;
    }

    /* ===== Main mejorado con menos espacio ===== */
    .main{
      margin-left:var(--sidebar-w);
      min-height:100vh;
      display:flex; flex-direction:column;
      transition:var(--transition);
    }
    .container-max{
      width:100%; 
      max-width:var(--maxw);
      margin:0 auto; 
      padding:0 2rem;
    }

    /* ===== Topbar mejorado ===== */
    .topbar{
      background:#fff;
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:1rem 0;
      position:sticky; top:0; z-index:10;
      box-shadow:0 2px 8px rgba(0,0,0,.03);
    }
    .topbar .d-flex{ transition:var(--transition); }
    .topbar i{ transition:var(--transition); }
    .topbar:hover i{ transform:scale(1.1); }

    /* ===== Contenido mejorado ===== */
    .content{ padding:1.5rem 0 2rem; }
    .card{
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 4px 16px rgba(0,0,0,.08);
      transition:var(--transition);
      overflow:hidden;
    }
    .card:hover{ box-shadow:0 8px 24px rgba(0,0,0,.12); transform:translateY(-2px); }
    .card-header{
      background:linear-gradient(135deg, #fff 0%, #fafbfc 100%);
      border-bottom:1px solid rgba(0,0,0,.06);
      border-top-left-radius:var(--radius);
      border-top-right-radius:var(--radius);
      padding:1.25rem 1.75rem !important;
    }
    .section-title{
      margin:0; font-size:1.25rem; font-weight:700; 
      color:var(--brand); letter-spacing:.2px;
      transition:var(--transition);
    }
    .card:hover .section-title{ color:var(--brand-hover); }

    /* ===== Tabla mejorada ===== */
    table.dataTable thead th{
      border-bottom:2px solid var(--brand-100);
      white-space:nowrap; 
      font-weight:700;
      padding:1rem 1.5rem !important;
      background:#fff;
      color:var(--brand);
      font-size:0.85rem;
      text-transform:uppercase;
      letter-spacing:0.5px;
      transition:var(--transition);
    }
    table.dataTable thead th:hover{
      background:var(--brand-50);
      color:var(--brand-hover);
    }
    #tabla-citas tbody tr{ 
      transition:var(--transition);
      border-bottom:1px solid rgba(0,0,0,.03);
    }
    #tabla-citas tbody tr:hover{
      background:var(--brand-50) !important;
      transform:scale(1.01);
      box-shadow:0 2px 8px rgba(13,110,253,.1);
    }
    #tabla-citas tbody td{ 
      vertical-align:middle;
      padding:1.1rem 1.5rem !important;
      font-size:0.9rem;
    }
    #tabla-citas { width:100% !important; }
    .card-body { padding:1.5rem 1.75rem !important; }
    .table-responsive { padding:0; margin:0; }
    .text-truncate{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .badge-estado{ 
      font-weight:600; 
      padding:0.4rem 0.75rem;
      font-size:0.8rem;
      border-radius:6px;
      transition:var(--transition);
    }
    .badge-estado:hover{ transform:scale(1.05); }

    .action-btn{
      display:inline-flex; 
      align-items:center; 
      justify-content:center;
      width:2.5rem; height:2.5rem; 
      border-radius:8px;
      border:1px solid rgba(13,110,253,.25);
      transition:var(--transition);
      text-decoration:none;
    }
    .action-btn:hover{
      transform:translateY(-2px) scale(1.1);
      box-shadow:0 4px 12px rgba(13,110,253,.3);
      border-color:var(--brand);
    }
    .action-btn i{ transition:var(--transition); }
    .action-btn:hover i{ transform:rotate(5deg); }

    .alert-dismissible{ 
      border-radius:var(--radius);
      border:none;
      box-shadow:0 2px 8px rgba(0,0,0,.1);
      transition:var(--transition);
    }
    .alert-dismissible:hover{ box-shadow:0 4px 12px rgba(0,0,0,.15); }

    /* ===== Responsive mejorado ===== */
    @media (max-width:992px){
      :root{ --sidebar-w:220px; }
      .sidebar{ padding:0.75rem 0.5rem; }
      .container-max{ padding:0 1rem; }
      .card-body{ padding:1rem 1.25rem !important; }
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

  <!-- ===== Sidebar estandarizado (idéntico en todas) ===== -->
  <aside class="sidebar">
    <div class="brand mb-2">
      <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="profile">
      <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle border" alt="Perfil">
      <div class="name"><?php echo htmlspecialchars($DOCTOR_NAME); ?></div>
      <div class="text-muted small">Panel de odontólogo</div>
    </div>

    <nav class="nav-menu">
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='citas'?'active':''); ?>" href="inicioAdmin.php">
        <i class="far fa-calendar-check"></i><span>Citas pendientes</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='calendario'?'active':''); ?>" href="calendar.php">
        <i class="far fa-calendar-alt"></i><span>Calendario</span>
      </a>
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='odontograma'?'active':''); ?>" href="../odontograma.php">
        <i class="fas fa-tooth"></i><span>Odontograma</span>
      </a>
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='presupuestos'?'active':''); ?>" href="presupuestos_doctor.php">
    <i class="fas fa-file-invoice-dollar"></i><span>Presupuestos</span>
  </a>
       <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='presupuestos'?'active':''); ?>" href="reportes_doctor.php">
    <i class="fas fa-file-invoice-dollar"></i><span>Reportes</span>
  </a>
      <a class="nav-link" href="../php/cerrar.php">
        <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
      </a>
    </nav>
  </aside>
  <!-- ===== /Sidebar ===== -->

  <!-- Main -->
  <div class="main">
    <header class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-home text-primary"></i>
          <span class="text-muted">Inicio</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Sesión:</span>
          <strong><?php echo htmlspecialchars($row['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </header>

    <main class="content">
      <div class="container-max">
        <?php if (!empty($_SESSION['MensajeTexto'])): ?>
          <div class="alert <?php echo $_SESSION['MensajeTipo'] ?? 'alert-info'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['MensajeTexto']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>
          <?php $_SESSION['MensajeTexto'] = null; $_SESSION['MensajeTipo'] = null; ?>
        <?php endif; ?>

        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h2 class="section-title mb-0">Citas pendientes</h2>
            <div class="text-muted small">
              <i class="far fa-clock me-1"></i>Listado de citas asignadas
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tabla-citas" class="table table-hover table-borderless align-middle nowrap" style="width:100%">
                <thead class="bg-light">
                  <tr>
                    <th>Nombre completo</th>
                    <th>Edad</th>
                    <th>Consulta</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Diagnóstico</th>
                    <th class="text-center">Editar</th>
                    <th class="text-center">Anular</th>
                  </tr>
                </thead>
                <tbody>
                <?php while ($c = mysqli_fetch_array($resultadoCitas, MYSQLI_ASSOC)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(($c['nombre'] ?? '').' '.($c['apellido'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($c['años'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['tipo'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['fecha_cita'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['hora_cita'] ?? ''); ?></td>
                    <td>
                      <?php if (($c['estado'] ?? '') === 'A'): ?>
                        <span class="badge bg-success badge-estado">Realizada</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark badge-estado">Pendiente</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-truncate" style="max-width:260px;"><?php echo htmlspecialchars($c['descripcion'] ?? ''); ?></td>
                    <td class="text-center">
                      <a class="btn btn-sm btn-outline-primary action-btn"
                         data-bs-toggle="tooltip" data-bs-title="Editar"
                         href="./realizar_consulta.php?accion=UDT&id=<?php echo urlencode($c['id_cita']); ?>">
                        <i class="fas fa-edit"></i>
                      </a>
                    </td>
                    <td class="text-center">
                      <a class="btn btn-sm btn-outline-danger action-btn"
                         data-bs-toggle="tooltip" data-bs-title="Anular"
                         href="../crud/realizar_consultasUPDATE.php?accion=DLT&id=<?php echo urlencode($c['id_cita']); ?>&estado=<?php echo urlencode($c['estado']); ?>"
                         data-confirm="¿Realmente deseas eliminar esta cita?">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div><!-- /card -->
      </div><!-- /container-max -->
    </main>
  </div><!-- /main -->
</div><!-- /app -->

<!-- JS -->
<script src="../src/js/jquery.js"></script>
<script src="../src/js/lib/datatable/js/jquery.dataTables.min.js"></script>
<script src="../src/js/lib/datatable/js/dataTables.responsive.min.js"></script>
<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
  // Tooltips
  (function () {
    var list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    list.map(function (el) { return new bootstrap.Tooltip(el); });
  })();

  // Confirmación
  document.addEventListener('click', function(e){
    const el = e.target.closest('[data-confirm]');
    if (!el) return;
    const msg = el.getAttribute('data-confirm') || '¿Confirmas esta acción?';
    if(!confirm(msg)){
      e.preventDefault();
      e.stopPropagation();
    }
  });

  // DataTable (responsive + español)
  $(function(){
    $('#tabla-citas').DataTable({
      responsive: true,
      pageLength: 10,
      lengthChange: false,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      },
      columnDefs: [
        { orderable: false, targets: [7,8] }
      ]
    });
  });
</script>
</body>
</html>
