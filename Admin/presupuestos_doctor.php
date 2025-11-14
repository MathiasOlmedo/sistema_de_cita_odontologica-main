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

/* ====== Variables del sidebar unificado ====== */
$SIDEBAR_ACTIVE = 'presupuestos';
$DOCTOR_NAME    = htmlspecialchars(utf8_decode(($row['nombreD'] ?? '').' '.($row['apellido'] ?? '')));
$DOCTOR_SEX     = $row['sexo'] ?? 'Masculino';
$AVATAR_IMG     = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';

/* ====== Consulta de presupuestos ====== */
// Obtener el nombre del paciente desde la tabla pacientes o desde presupuesto si existe
// Primero intentamos verificar si existe la columna paciente_nombre en presupuesto
$checkColumn = mysqli_query($link, "SHOW COLUMNS FROM presupuesto LIKE 'paciente_nombre'");
$hasPacienteNombre = ($checkColumn && mysqli_num_rows($checkColumn) > 0);

if ($hasPacienteNombre) {
  // Si existe paciente_nombre, usar COALESCE para priorizarlo
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
  // Si no existe paciente_nombre, usar solo el JOIN con pacientes
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

// Usar consulta preparada para mayor seguridad
$stmt = mysqli_prepare($link, $sql);
if ($stmt) {
  mysqli_stmt_bind_param($stmt, 'i', $vUsuario);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if (!$res) {
    echo "<div class='alert alert-danger m-3'>
            <strong>Error SQL:</strong> " . htmlspecialchars(mysqli_error($link)) . "<br>
            <code>$sql</code>
          </div>";
    $res = [];
  }
} else {
  echo "<div class='alert alert-danger m-3'>
          <strong>Error preparando consulta:</strong> " . htmlspecialchars(mysqli_error($link)) . "<br>
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
    }
    *{ box-sizing:border-box; margin:0; padding:0; }
    html, body{ height:100%; }
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
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
      border-right:1px solid rgba(0,0,0,.05);
      box-shadow:2px 0 12px rgba(0,0,0,.03);
      z-index:1030;
      padding:1rem 0.75rem;
      overflow-y:auto;
      overflow-x:hidden;
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
    .profile .name{ margin:.5rem 0 .2rem; font-weight:600; font-size:0.95rem; }
    .nav-menu{ display:flex; flex-direction:column; gap:0.25rem; }
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

    /* ===== Main mejorado con menos espacio ===== */
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

    .topbar{ 
      background:#fff; 
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:1rem 0; 
      position:sticky; 
      top:0; 
      z-index:10;
      box-shadow:0 2px 8px rgba(0,0,0,.03);
    }

    .section-title{ 
      margin:0; 
      font-size:1.25rem; 
      font-weight:700; 
      color:var(--brand);
      transition:var(--transition);
    }

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
      padding:1.25rem 1.75rem !important;
    }
    .card-body{
      padding:1.5rem 1.75rem !important;
    }
    
    table.dataTable thead th{
      border-bottom:2px solid var(--brand-100);
      padding:1rem 1.5rem !important;
      background:#fff;
      color:var(--brand);
      font-weight:700;
      font-size:0.85rem;
      text-transform:uppercase;
      letter-spacing:0.5px;
      transition:var(--transition);
    }
    table.dataTable thead th:hover{
      background:var(--brand-50);
      color:var(--brand-hover);
    }
    table.dataTable tbody tr{
      transition:var(--transition);
      border-bottom:1px solid rgba(0,0,0,.03);
    }
    table.dataTable tbody tr:hover{
      background:var(--brand-50) !important;
      transform:scale(1.01);
      box-shadow:0 2px 8px rgba(13,110,253,.1);
    }
    table.dataTable tbody td{
      padding:1.1rem 1.5rem !important;
      font-size:0.9rem;
    }

    @media (max-width:992px){
      :root{ --sidebar-w:220px; }
      .sidebar{ padding:0.75rem 0.5rem; }
      .container-max{ padding:0 1rem; }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:200px; }
      .container-max{ padding:0 0.75rem; }
    }
  </style>
</head>
<body>

  <!-- ===== Sidebar unificado ===== -->
  <aside class="sidebar">
    <div class="brand mb-2">
      <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="side-inner">
      <div class="profile">
        <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle" alt="Perfil">
        <h3 class="name"><?php echo $DOCTOR_NAME; ?></h3>
        <span class="country text-muted">Panel de odontólogo</span>
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
    </div>
  </aside>

  <!-- ===== Main ===== -->
  <main>
    <div class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-file-invoice-dollar text-primary"></i>
          <span class="text-muted">Presupuestos</span>
        </div>
        <div class="small text-muted d-none d-sm-block">
          Sesión: <strong><?php echo htmlspecialchars($row['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </div>

    <div class="container-max mt-4">
      <div class="card">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
          <h2 class="section-title mb-0">Mis Presupuestos</h2>
          <small class="text-muted">Listado de presupuestos generados</small>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tabla-presupuestos" class="table table-hover table-borderless align-middle nowrap" style="width:100%">
              <thead class="bg-light">
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
                  <td><?php echo htmlspecialchars($p['id_presupuesto'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($pacienteNombre); ?></td>
                  <td><?php echo htmlspecialchars($p['fecha'] ?? ''); ?></td>
                  <td><?php echo number_format($p['total'] ?? 0, 0, ',', '.'); ?></td>
                  <td>
                    <?php 
                    $estado = $p['estado'] ?? 'pendiente';
                    if($estado === 'pendiente'): ?>
                      <span class="badge bg-warning text-dark">Pendiente</span>
                    <?php elseif($estado === 'aprobado'): ?>
                      <span class="badge bg-success">Aprobado</span>
                    <?php elseif($estado === 'rechazado'): ?>
                      <span class="badge bg-danger">Rechazado</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($estado)); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if(!empty($p['pdf_path'])): ?>
                      <a href="../<?php echo htmlspecialchars($p['pdf_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-file-pdf"></i> Abrir
                      </a>
                    <?php else: ?>
                      <span class="text-muted small">Sin PDF</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php 
                  endwhile;
                } else {
                  echo '<tr><td colspan="6" class="text-center text-muted">No se encontraron presupuestos.</td></tr>';
                }
                // Cerrar el statement si existe (después de procesar todos los resultados)
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
  </main>

  <script src="https://code.jquery.com/jquery-3.2.1.js"></script>
  <script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../src/js/lib/datatable/js/jquery.dataTables.min.js"></script>
  <script src="../src/js/lib/datatable/js/dataTables.responsive.min.js"></script>
  <script>
  $(function(){
    $('#tabla-presupuestos').DataTable({
      responsive:true,
      pageLength:10,
      order:[[0,'desc']],
      language:{ url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
  });
  </script>
</body>
</html>
