<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$vUsuario = $_SESSION['id_doctor'] ?? null;
if (!$vUsuario) {
  $_SESSION['MensajeTexto'] = "Error: acceso no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

$row = consultarDoctor($link, $vUsuario);

$SIDEBAR_ACTIVE = 'presupuestos';
$DOCTOR_NAME    = htmlspecialchars(utf8_decode(($row['nombreD'] ?? '').' '.($row['apellido'] ?? '')));
$DOCTOR_SEX     = $row['sexo'] ?? 'Masculino';
$AVATAR_IMG     = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';

$checkColumn = mysqli_query($link, "SHOW COLUMNS FROM presupuesto LIKE 'paciente_nombre'");
$hasPacienteNombre = ($checkColumn && mysqli_num_rows($checkColumn) > 0);

if ($hasPacienteNombre) {
  $sql = "SELECT 
            p.id_presupuesto, 
            COALESCE(p.paciente_nombre, CONCAT(pac.nombre, ' ', pac.apellido)) AS paciente_nombre,
            p.fecha, 
            p.total, 
            p.pdf_path, 
            p.estado,
            p.folio
          FROM presupuesto p
          LEFT JOIN pacientes pac ON pac.id_paciente = p.id_paciente
          WHERE p.id_doctor = ? 
          ORDER BY p.fecha DESC";
} else {
  $sql = "SELECT 
            p.id_presupuesto, 
            CONCAT(pac.nombre, ' ', pac.apellido) AS paciente_nombre,
            p.fecha, 
            p.total, 
            p.pdf_path, 
            p.estado,
            p.folio
          FROM presupuesto p
          LEFT JOIN pacientes pac ON pac.id_paciente = p.id_paciente
          WHERE p.id_doctor = ? 
          ORDER BY p.fecha DESC";
}

$stmt = mysqli_prepare($link, $sql);
if ($stmt) {
  mysqli_stmt_bind_param($stmt, 'i', $vUsuario);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if (!$res) {
    echo "<div class='alert alert-danger m-3'>
            <strong>Error SQL:</strong> " . htmlspecialchars(mysqli_error($link)) . "
          </div>";
    $res = [];
  }
} else {
  echo "<div class='alert alert-danger m-3'>
          <strong>Error preparando consulta:</strong> " . htmlspecialchars(mysqli_error($link)) . "
        </div>";
  $res = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth — Presupuestos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="../src/img/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.css">
  <link rel="stylesheet" href="../src/js/lib/datatable/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="../src/js/lib/datatable/css/responsive.dataTables.min.css">

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
      --success:#198754;
      --warning:#ffc107;
      --danger:#dc3545;
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

    /* ===== SIDEBAR ===== */
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

    .nav-menu{ display:flex; flex-direction:column; gap:0.25rem; list-style:none; padding:0; margin:0; }
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

    /* ===== MAIN ===== */
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
      padding:0 2rem;
    }

    /* ===== TOPBAR ===== */
    .topbar{
      background:#fff;
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:1.25rem 0;
      position:sticky; top:0; z-index:10;
      box-shadow:0 2px 8px rgba(0,0,0,.03);
    }
    .topbar i{ transition:var(--transition); font-size:1.25rem; color:var(--brand); }
    .topbar:hover i{ transform:scale(1.1); }
    .topbar .text-muted{ font-size:0.95rem; }

    /* ===== CONTENT ===== */
    .content{ padding:2rem 0; }

    /* ===== CARD ===== */
    .card{
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 4px 16px rgba(0,0,0,.08);
      transition:var(--transition);
      overflow:hidden;
    }
    .card:hover{
      box-shadow:0 8px 24px rgba(0,0,0,.12);
      transform:translateY(-2px);
    }

    .card-header{
      background:linear-gradient(135deg, #fff 0%, #fafbfc 100%);
      border-bottom:1px solid rgba(0,0,0,.06);
      border-top-left-radius:var(--radius);
      border-top-right-radius:var(--radius);
      padding:1.5rem 1.75rem !important;
    }
    .card-header-content{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:1rem;
    }
    .card-header-content i{
      font-size:1.5rem;
      color:var(--brand);
      transition:var(--transition);
    }
    .card:hover .card-header-content i{
      transform:scale(1.15);
      color:var(--brand-hover);
    }

    .section-title{
      margin:0;
      font-size:1.35rem;
      font-weight:700;
      color:var(--brand);
      letter-spacing:.2px;
      transition:var(--transition);
    }
    .card:hover .section-title{ color:var(--brand-hover); }

    .section-subtitle{
      margin:0;
      font-size:0.9rem;
      color:var(--text-muted);
      font-weight:500;
    }

    .card-body{ padding:1.75rem !important; }

    /* ===== TABLA MEJORADA ===== */
    .table-responsive{ padding:0; margin:0; }

    table.dataTable{
      border-collapse:collapse;
    }
    table.dataTable thead th{
      background:linear-gradient(135deg, var(--brand-50) 0%, #fff 100%);
      border-bottom:2px solid var(--brand-100) !important;
      white-space:nowrap;
      font-weight:700;
      padding:1.1rem 1.5rem !important;
      color:var(--brand);
      font-size:0.85rem;
      text-transform:uppercase;
      letter-spacing:0.6px;
      transition:var(--transition);
    }
    table.dataTable thead th:hover{
      background:linear-gradient(135deg, var(--brand-100) 0%, var(--brand-50) 100%);
      color:var(--brand-hover);
    }

    #tabla-presupuestos tbody tr{
      transition:var(--transition);
      border-bottom:1px solid rgba(0,0,0,.05);
    }
    #tabla-presupuestos tbody tr:hover{
      background:linear-gradient(90deg, var(--brand-50) 0%, transparent 100%) !important;
      transform:scale(1.005);
      box-shadow:inset 0 2px 8px rgba(13,110,253,.1);
    }
    #tabla-presupuestos tbody td{
      vertical-align:middle;
      padding:1.15rem 1.5rem !important;
      font-size:0.9rem;
      color:var(--text);
    }
    #tabla-presupuestos tbody td strong{ color:var(--brand); font-weight:600; }

    .badge{
      font-weight:600;
      padding:0.5rem 0.9rem;
      font-size:0.8rem;
      border-radius:8px;
      transition:var(--transition);
      display:inline-block;
    }
    .badge:hover{ transform:scale(1.08); box-shadow:0 2px 8px rgba(0,0,0,.15); }

    .badge-success{
      background:linear-gradient(135deg, var(--success) 0%, #20c997 100%);
      color:#fff;
    }
    .badge-warning{
      background:linear-gradient(135deg, var(--warning) 0%, #fd7e14 100%);
      color:#000;
    }
    .badge-danger{
      background:linear-gradient(135deg, var(--danger) 0%, #dc2c2c 100%);
      color:#fff;
    }

    .action-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:0.5rem;
      padding:0.6rem 1.15rem;
      border-radius:var(--radius);
      border:1px solid rgba(13,110,253,.25);
      transition:var(--transition);
      text-decoration:none;
      background:#fff;
      color:var(--brand);
      font-size:0.9rem;
      font-weight:500;
    }
    .action-btn:hover{
      transform:translateY(-2px);
      box-shadow:0 6px 16px rgba(13,110,253,.3);
      border-color:var(--brand);
      background:var(--brand-50);
      color:var(--brand);
      text-decoration:none;
    }
    .action-btn i{ transition:var(--transition); }
    .action-btn:hover i{ transform:scale(1.15); }

    .text-without-pdf{
      color:var(--text-muted);
      font-size:0.9rem;
      font-weight:500;
    }

    /* ===== PAGINACIÓN ===== */
    .dataTables_wrapper {
      position:relative;
    }
    .dataTables_paginate {
      margin-top:2rem !important;
      display:flex !important;
      justify-content:center !important;
      align-items:center !important;
      gap:0.5rem;
      flex-wrap:wrap;
    }
    .paginate_button {
      background:#fff !important;
      border:1px solid rgba(0,0,0,.12) !important;
      border-radius:var(--radius) !important;
      padding:0.6rem 1.1rem !important;
      margin:0.25rem !important;
      color:var(--text) !important;
      font-weight:500;
      font-size:0.9rem;
      transition:var(--transition) !important;
      text-decoration:none !important;
      min-width:45px;
      text-align:center;
      box-shadow:0 2px 6px rgba(0,0,0,.06);
    }
    .paginate_button:hover {
      background:var(--brand-50) !important;
      color:var(--brand) !important;
      border-color:var(--brand) !important;
      transform:translateY(-2px) !important;
      box-shadow:0 4px 12px rgba(13,110,253,.2) !important;
      text-decoration:none !important;
    }
    .paginate_button.current {
      background:linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%) !important;
      color:#fff !important;
      border-color:var(--brand) !important;
      font-weight:600;
      box-shadow:0 4px 14px rgba(13,110,253,.35) !important;
    }
    .paginate_button.current:hover {
      background:linear-gradient(135deg, var(--brand-hover) 0%, var(--brand) 100%) !important;
      color:#fff !important;
    }
    .paginate_button.disabled,
    .paginate_button.disabled:hover {
      background:var(--surface) !important;
      color:var(--text-muted) !important;
      border-color:rgba(0,0,0,.05) !important;
      cursor:not-allowed !important;
      transform:none !important;
      box-shadow:none !important;
      opacity:0.5;
    }

    .dataTables_info {
      color:var(--text-muted);
      font-size:0.9rem;
      margin-top:1.5rem;
      text-align:center;
      font-weight:500;
    }

    .dataTables_wrapper .bottom {
      display:flex;
      flex-direction:column;
      align-items:center;
      margin-top:2rem;
      padding-top:1.5rem;
      border-top:1px solid rgba(0,0,0,.06);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width:992px){
      :root{ --sidebar-w:220px; }
      .sidebar{ padding:0.75rem 0.5rem; }
      .container-max{ padding:0 1.25rem; }
      .card-header{ padding:1.25rem 1.5rem !important; }
      .card-body{ padding:1.5rem !important; }
      .section-title{ font-size:1.2rem; }
      #tabla-presupuestos tbody td{ padding:0.95rem 1.25rem !important; font-size:0.85rem; }
      .paginate_button { padding:0.5rem 0.95rem !important; min-width:40px; font-size:0.8rem; }
    }
    @media (max-width:768px){
      .topbar .text-muted{ display:none; }
      .card-header-content{ flex-direction:column; align-items:flex-start; }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:200px; }
      .container-max{ padding:0 1rem; }
      .content{ padding:1.5rem 0; }
      .card-header{ padding:1.1rem 1.25rem !important; }
      .card-body{ padding:1.25rem !important; }
      .section-title{ font-size:1.1rem; }
      #tabla-presupuestos tbody th, #tabla-presupuestos tbody td{ padding:0.85rem 1rem !important; font-size:0.8rem; }
      .action-btn{ padding:0.5rem 0.95rem; font-size:0.8rem; }
      .badge{ padding:0.4rem 0.7rem; font-size:0.75rem; }
      .dataTables_paginate { gap:0.25rem; }
      .paginate_button { padding:0.4rem 0.8rem !important; min-width:36px; font-size:0.75rem; margin:0.15rem !important; }
    }
  </style>
</head>
<body>
<div class="app">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="brand mb-2">
      <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="side-inner">
      <div class="profile">
        <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle border" alt="Perfil">
        <div class="name"><?php echo $DOCTOR_NAME; ?></div>
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
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='reportes'?'active':''); ?>" href="reportes_doctor.php">
          <i class="fas fa-chart-line"></i><span>Reportes</span>
        </a>
        <a class="nav-link" href="../php/cerrar.php">
          <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
        </a>
      </nav>
    </div>
  </aside>

  <!-- MAIN -->
  <main>
    <header class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-file-invoice-dollar"></i>
          <span class="text-muted">Presupuestos</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Doctor:  </span>
          <strong><?php echo htmlspecialchars($row['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="container-max">
        <div class="card">
          <div class="card-header">
            <div class="card-header-content">
              <div>
                <h2 class="section-title mb-0">Mis Presupuestos</h2>
                <p class="section-subtitle mt-1">
                  Listado de presupuestos generados
                </p>
              </div>
              <i class="fas fa-file-pdf"></i>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tabla-presupuestos" class="table table-hover table-borderless align-middle nowrap">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Fecha</th>
                    <th>Total (Gs)</th>
                    <th>Estado</th>
                    <th class="text-center">PDF</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  if ($res && is_object($res)) {
                    while($p = mysqli_fetch_assoc($res)): 
                      $pacienteNombre = $p['paciente_nombre'] ?? 'Sin nombre';
                      if (empty($pacienteNombre) || $pacienteNombre === ' ' || $pacienteNombre === null) {
                        $pacienteNombre = 'Paciente #' . ($p['id_paciente'] ?? $p['id_presupuesto']);
                      }
                  ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($p['id_presupuesto'] ?? ''); ?></strong></td>
                    <td><?php echo htmlspecialchars($pacienteNombre); ?></td>
                    <td><?php echo htmlspecialchars($p['fecha'] ?? '-'); ?></td>
                    <td><strong><?php echo number_format($p['total'] ?? 0, 0, ',', '.'); ?></strong></td>
                    <td>
                      <?php 
                      $estado = $p['estado'] ?? 'pendiente';
                      if($estado === 'pendiente'): ?>
                        <span class="badge badge-warning">
                          <i class="fas fa-hourglass-half me-1"></i>Pendiente
                        </span>
                      <?php elseif($estado === 'aprobado'): ?>
                        <span class="badge badge-success">
                          <i class="fas fa-check-circle me-1"></i>Aprobado
                        </span>
                      <?php elseif($estado === 'rechazado'): ?>
                        <span class="badge badge-danger">
                          <i class="fas fa-times-circle me-1"></i>Rechazado
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary">
                          <i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars(ucfirst($estado)); ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if(!empty($p['pdf_path'])): ?>
                        <a href="../<?php echo htmlspecialchars($p['pdf_path']); ?>" target="_blank" class="action-btn">
                          <i class="fas fa-file-pdf"></i> Abrir
                        </a>
                      <?php else: ?>
                        <span class="text-without-pdf">Sin PDF</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php 
                    endwhile;
                  } else {
                    echo '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No se encontraron presupuestos.</td></tr>';
                  }
                  if (isset($stmt) && $stmt) {
                    mysqli_stmt_close($stmt);
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://code.jquery.com/jquery-3.2.1.js"></script>
<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../src/js/lib/datatable/js/jquery.dataTables.min.js"></script>
<script src="../src/js/lib/datatable/js/dataTables.responsive.min.js"></script>
<script>
  $(function(){
    $('#tabla-presupuestos').DataTable({
      responsive: true,
      pageLength: 10,
      lengthChange: false,
      order: [[0,'desc']],
      language: {
        "decimal": "",
        "emptyTable": "No hay datos disponibles",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "infoEmpty": "Mostrando 0 a 0 de 0 registros",
        "infoFiltered": "(filtrado de _MAX_ registros totales)",
        "thousands": ",",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "No se encontraron registros coincidentes",
        "paginate": {
          "first": "Primero",
          "last": "Último",
          "next": "Siguiente",
          "previous": "Anterior"
        }
      },
      columnDefs: [
        { orderable: false, targets: [5] }
      ],
      dom: '<"row"<"col-sm-12"tr>>' +
           '<"bottom"<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>>'
    });
  });
</script>
</body>
</html>