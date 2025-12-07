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
$sql = "SELECT id_presupuesto, paciente_nombre, fecha, total, pdf_path, estado 
        FROM presupuesto 
        WHERE id_doctor = $vUsuario 
        ORDER BY fecha DESC";
$res = mysqli_query($link, $sql);
if (!$res) {
  echo "<div class='alert alert-danger m-3'>
          <strong>Error SQL:</strong> " . htmlspecialchars(mysqli_error($link)) . "<br>
          <code>$sql</code>
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
      --brand:#0d6efd; --brand-100:#e7f1ff;
      --surface:#f8f9fa; --text:#212529;
      --sidebar-w:260px; --maxw:1200px; --radius:12px;
    }
    *{ box-sizing:border-box; }
    html, body{ height:100%; }
    body{ margin:0; background:var(--surface); color:var(--text); }

    /* ===== Sidebar ===== */
    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff;
      border-right:0 !important; box-shadow:none !important;
      transform:none !important; z-index:1030;
      padding:1.25rem 1rem;
      overflow-y:hidden !important; overflow-x:hidden !important;
    }
    .brand{ display:flex; align-items:center; gap:.75rem; padding:.5rem .75rem; border-radius:.75rem; }
    .brand-title{ margin:0; font-weight:700; letter-spacing:.3px; color:var(--brand); font-size:1.05rem; }
    .profile{text-align:center; margin:1rem 0 1.25rem}
    .profile img{ width:96px; height:96px; object-fit:cover }
    .profile .name{ margin:.65rem 0 .2rem; font-weight:600 }
    .nav-menu{ display:flex; flex-direction:column; gap:.25rem; }
    .nav-menu .nav-link{
      display:flex; align-items:center; gap:.6rem;
      border-radius:.6rem; padding:.6rem .75rem;
      color:#495057; text-decoration:none;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100); color:var(--brand);
      text-decoration:none; font-weight:600;
    }

    /* ===== Main ===== */
    main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
    .container-max{ width:100%; max-width:var(--maxw); margin:0 auto; padding:0 1rem; }

    .topbar{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06);
      padding:.75rem 0; position:sticky; top:0; z-index:10; }

    .section-title{ margin:0; font-size:1.1rem; font-weight:700; color:var(--brand); }

    .card{ border-radius:var(--radius); border:1px solid rgba(0,0,0,.06);
      box-shadow:0 6px 16px rgba(15,23,42,.06); }

    @media (max-width:992px){
      :root{ --sidebar-w:240px; }
      .sidebar{ width:var(--sidebar-w); }
      main{ margin-left:var(--sidebar-w); }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:220px; }
      .sidebar{ width:var(--sidebar-w); }
      main{ margin-left:var(--sidebar-w); }
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
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='citas'?'active':''); ?>" href="/sistema_de_cita_odontologica-main/admin/inicioAdmin.php">
          <i class="far fa-calendar-check"></i><span>Citas pendientes</span>
        </a>
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='calendario'?'active':''); ?>" href="calendar.php">
          <i class="far fa-calendar-alt"></i><span>Calendario</span>
        </a>
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='historial'?'active':''); ?>" href="historial_medico.php">
    <i class="fas fa-notes-medical"></i><span>Historial médico</span>
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
                <?php while($p = mysqli_fetch_assoc($res)): ?>
                <tr>
                  <td><?php echo htmlspecialchars($p['id_presupuesto']); ?></td>
                  <td><?php echo htmlspecialchars($p['paciente_nombre']); ?></td>
                  <td><?php echo htmlspecialchars($p['fecha']); ?></td>
                  <td><?php echo number_format($p['total'], 0, ',', '.'); ?></td>
                  <td>
                    <?php if($p['estado']==='pendiente'): ?>
                      <span class="badge bg-warning text-dark">Pendiente</span>
                    <?php elseif($p['estado']==='aprobado'): ?>
                      <span class="badge bg-success">Aprobado</span>
                    <?php elseif($p['estado']==='rechazado'): ?>
                      <span class="badge bg-danger">Rechazado</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Enviado</span>
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
                <?php endwhile; ?>
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
