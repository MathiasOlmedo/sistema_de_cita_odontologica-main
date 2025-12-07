<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (isset($_SESSION['id_doctor'])) {
  $vUsuario = $_SESSION['id_doctor'];
  $row = consultarDoctor($link, $vUsuario);
  $resultadoCitas = MostrarCitas($link, $vUsuario); // mostrar citas
} else {
  $_SESSION['MensajeTexto'] = "Error: acceso al sistema no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ./index.php");
  exit;
}

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

  <!-- ===== Diseño estandarizado (mismo look & feel) ===== -->
  <style>
    :root{
      --brand:#0d6efd;
      --brand-100:#e7f1ff;
      --surface:#f8f9fa;
      --text:#212529;
      --sidebar-w:260px;
      --maxw:1200px;
      --radius:12px;
    }
    *{ box-sizing:border-box; }
    html, body { height:100%; }
    html{ overflow-y:auto; overflow-x:hidden; }
    body{
      margin:0;
      background:var(--surface);
      color:var(--text);
      font-feature-settings:"liga" 1, "calt" 1;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    /* ===== Sidebar fijo SIEMPRE visible =================== */
    .sidebar{
      background:#fff;
      border-right:0 !important;
      box-shadow:none !important;

      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      padding:1.25rem 1rem;
      overflow-y:hidden !important;
      overflow-x:hidden !important;
      z-index:1030;
      transform:none !important;
    }
    .sidebar::before,.sidebar::after{ content:none !important; display:none !important; }
    .toggle,.js-menu-toggle{ display:none !important; pointer-events:none !important; }

    .brand{
      display:flex; align-items:center; gap:.75rem;
      padding:.5rem .75rem; border-radius:.75rem;
    }
    .brand-title{ margin:0; font-weight:700; letter-spacing:.3px; color:var(--brand); font-size:1.05rem; }

    .profile{
      text-align:center; margin:1rem 0 1.25rem;
    }
    .profile img{ width:96px; height:96px; object-fit:cover; }
    .profile .name{ margin:.75rem 0 .25rem; font-weight:600; }

    .nav-menu .nav-link{
      border-radius:.6rem; color:#495057;
      display:flex; align-items:center; gap:.6rem;
      padding:.6rem .75rem; text-decoration:none;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100);
      color:var(--brand);
      text-decoration:none;
      font-weight:600;
    }

    /* ===== Main empujado por sidebar ====================== */
    .main{
      margin-left:var(--sidebar-w);
      min-height:100vh;
      display:flex; flex-direction:column;
    }
    .container-max{
      width:100%; max-width:var(--maxw);
      margin:0 auto; padding:0 1.25rem;
    }

    .topbar{
      background:#fff;
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:.75rem 0;
      position:sticky; top:0; z-index:10;
    }

    /* ===== Contenido ========== */
    .content{ padding:1.25rem 0 2rem; }

    .card{
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 6px 16px rgba(15,23,42,.06);
    }

    .card-header{
      background:#fff;
      border-bottom:1px solid rgba(0,0,0,.06);
      border-top-left-radius:var(--radius);
      border-top-right-radius:var(--radius);
    }

    .section-title{
      margin:0; font-size:1.1rem; font-weight:700; color:var(--brand); letter-spacing:.2px;
    }

    /* ===== Tabla =========== */
    table.dataTable thead th{
      border-bottom:1px solid rgba(0,0,0,.08);
      white-space:nowrap; font-weight:700;
    }
    #tabla-citas tbody td{ vertical-align:middle; }
    .text-truncate{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .badge-estado{ font-weight:600; }

    .action-btn{
      display:inline-flex; align-items:center; justify-content:center;
      width:2.25rem; height:2.25rem; border-radius:.65rem;
      border:1px solid rgba(13,110,253,.25);
    }

    .alert-dismissible .btn-close{ padding:.9rem 1rem; }

    /* Estilo para el botón cerrar del modal (×) */
    .modal-header .close {
      background: transparent;
      border: 0;
      font-size: 1.5rem;
      font-weight: 700;
      line-height: 1;
      opacity: 0.9;
      padding: 0;
      cursor: pointer;
    }
    .modal-header .close:hover {
      opacity: 1;
    }
  </style>
</head>
<body>
<div class="app">

  <!-- ===== Sidebar ===== -->
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

                    <!-- ===== BOTÓN EDITAR AHORA ABRE EL MODAL ===== -->
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary action-btn"
                              data-bs-toggle="tooltip" data-bs-title="Editar"
                              onclick="abrirModalEditar(<?php echo $c['id_cita']; ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
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



<!-- ======================================================== -->
<!-- ==========   AQUI SE AGREGA EL MODAL NUEVO   ============ -->
<!-- ======================================================== -->
<div class="modal fade" id="modalEditarCita" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="fas fa-edit me-2"></i>Editar cita
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body" id="contenedor-editar-cita">
        Cargando…
      </div>

    </div>
  </div>
</div>

<!-- ======================================================== -->
<!-- ========== /MODAL NUEVO ================================ -->
<!-- ======================================================== -->



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


<!-- ======================================================== -->
<!-- ==========   AQUI SE AGREGA LA FUNCIÓN JS   ============= -->
<!-- ======================================================== -->
<script>
function abrirModalEditar(id_cita) {
    $("#modalEditarCita").modal("show");
    $("#contenedor-editar-cita").html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Cargando...</p></div>');
    $("#contenedor-editar-cita").load("realizar_consulta_modal.php?id=" + id_cita);
}
</script>
<!-- ======================================================== -->

</body>
</html>