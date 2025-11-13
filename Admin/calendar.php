<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$vUsuario = $_SESSION['id_doctor'] ?? null;
$resultadoDentistas = $vUsuario ? MostrarCitas($link, $vUsuario) : null; // eventos

if (isset($_SESSION['id_doctor'])) {
  $row = consultarDoctor($link, $_SESSION['id_doctor']);
} else {
  $_SESSION['MensajeTexto'] = "Error acceso al sistema  no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php"); exit;
}

/* ====== Variables del sidebar unificado ====== */
$SIDEBAR_ACTIVE = 'calendario'; // 'citas' | 'calendario' | 'odontograma'
$DOCTOR_NAME    = htmlspecialchars(utf8_decode(($row['nombreD'] ?? '').' '.($row['apellido'] ?? '')));
$DOCTOR_SEX     = $row['sexo'] ?? 'Masculino';
$AVATAR_IMG     = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth — Calendario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ICONO -->
  <link rel="icon" href="../src/img/logo.png" type="image/png" />

  <!-- Bootstrap 4 (puedes cambiar a tu local si prefieres) -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.css">

  <!-- FullCalendar v3 CSS -->
  <link rel="stylesheet" type="text/css" href="../src/css/fullcalendar.css" />

  <!-- Tu CSS existente -->
  <link rel="stylesheet" href="../src/css/admin.css">

  <!-- ======== Modelo estandarizado (sidebar unificado + calendario full height) ======== -->
  <style>
    :root{
      --brand:#0d6efd; --brand-100:#e7f1ff;
      --surface:#f8f9fa; --text:#212529;
      --sidebar-w:260px; --maxw:1200px; --radius:12px;
    }
    *{ box-sizing: border-box; }
    html, body { height:100%; }
    html{ overflow-y:auto; overflow-x:hidden; }
    body{
      margin:0; background:var(--surface); color:var(--text);
      -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    }

    /* ===== Sidebar fijo, SIEMPRE visible, sin borde ni scroll propio ===== */
    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff;
      border-right:0 !important;      /* sin línea */
      box-shadow:none !important;
      transform:none !important;
      z-index:1030;
      padding:1.25rem 1rem;
      overflow-y:hidden !important; overflow-x:hidden !important;
    }
    .sidebar::before, .sidebar::after{ content:none !important; display:none !important; }
    .toggle, .js-menu-toggle{ display:none !important; pointer-events:none !important; }

    /* Contenido del sidebar (idéntico al resto) */
    .brand{ display:flex; align-items:center; gap:.75rem; padding:.5rem .75rem; border-radius:.75rem; }
    .brand-title{ margin:0; font-weight:700; letter-spacing:.3px; color:var(--brand); font-size:1.05rem; }

    .side-inner{ padding-bottom:1rem; }
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

    /* ===== Main empujado por sidebar ===== */
    main{
      margin-left:var(--sidebar-w);
      min-height:100vh;
      display:flex; flex-direction:column;
    }
    .container-max{ width:100%; max-width:var(--maxw); margin:0 auto; padding:0 1rem; }

    /* Topbar */
    .topbar{
      background:#fff; border-bottom:1px solid rgba(0,0,0,.06);
      padding:.75rem 0; position:sticky; top:0; z-index:10;
    }

    /* Tarjeta del calendario */
    .site-section{ padding:0; }
    .calendar-card{
      background:#fff; border:1px solid rgba(0,0,0,.06);
      border-radius:var(--radius); box-shadow:0 6px 16px rgba(15,23,42,.06);
      overflow:hidden; display:flex; flex-direction:column;
      min-height: calc(100vh - 56px - 32px); /* altura útil */
      margin: 1rem 0 1.5rem;
    }
    .calendar-header{
      padding:.85rem 1rem; background:#fff; border-bottom:1px solid rgba(0,0,0,.06);
      display:flex; align-items:center; justify-content:space-between;
    }
    .calendar-title{ margin:0; font-weight:700; font-size:1.05rem; color:var(--brand) }

    .calendar-wrapper{ flex:1 1 auto; min-height:0; padding:.75rem; background:#fff; }

    /* FullCalendar limpio y adaptable */
    #calendar{ width:100%; height:100%; background:#fff; border:0; border-radius:10px; box-shadow:none; }
    .fc{ font-size:.95rem; }
    .fc-toolbar{ margin-bottom:.75rem; }
    .fc-toolbar h2{ font-size:1.1rem; font-weight:700; color:#0d1b2a; }
    .fc-button{
      border-radius:.5rem !important; border:1px solid rgba(13,110,253,.25) !important;
      background:#fff !important; color:#0d6efd !important;
      text-shadow:none !important; box-shadow:none !important;
      padding:.35rem .6rem !important;
    }
    .fc-state-active, .fc-button:focus{
      outline:none !important; box-shadow:0 0 0 .15rem rgba(13,110,253,.15) !important;
    }
    .fc-day-grid-event .fc-content{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    /* Responsive */
    @media (max-width:992px){
      :root{ --sidebar-w:240px }
      .sidebar{ width:var(--sidebar-w) }
      main{ margin-left:var(--sidebar-w) }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:220px }
      .sidebar{ width:var(--sidebar-w) }
      main{ margin-left:var(--sidebar-w) }
      .fc-toolbar .fc-left, .fc-toolbar .fc-right, .fc-toolbar .fc-center{
        float:none !important; display:block; text-align:center; margin-bottom:.5rem;
      }
    }
  </style>

  <!-- jQuery + Popper + Bootstrap 4 JS -->
  <script src="https://code.jquery.com/jquery-3.2.1.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

  <!-- FullCalendar v3 JS -->
  <script type="text/javascript" src="../src/js/lib/FullCalendar/moment.min.js"></script>
  <script type="text/javascript" src="../src/js/lib/FullCalendar/fullcalendar.min.js"></script>
  <script type="text/javascript" src="../src/js/lib/FullCalendar/locale/es.js"></script>

  <script>
    // Fecha por defecto
    function addZero(i){ return (i < 10) ? ('0' + i) : i; }
    var hoy = new Date();
    var dd = addZero(hoy.getDate());
    var mm = addZero(hoy.getMonth() + 1);
    var yyyy = hoy.getFullYear();

    $(function(){
      $('#calendar').fullCalendar({
        header: {
          left: 'prev,next today',
          center: 'title',
          right: 'month,agendaWeek,agendaDay,listWeek'
        },
        defaultDate: yyyy + '-' + mm + '-' + dd,
        buttonIcons: true,
        weekNumbers: false,
        editable: false,
        eventLimit: true,

        /* Altura real al contenedor, sin “zoom” */
        height: 'parent',          // usa la altura de .calendar-wrapper
        contentHeight: 'auto',
        handleWindowResize: true,
        aspectRatio: 1.45,

        /* En móviles, si estás en mes, cae a listWeek */
        viewRender: function(view){
          var isMobile = window.matchMedia('(max-width: 576px)').matches;
          if (isMobile && view.name === 'month') {
            $('#calendar').fullCalendar('changeView', 'listWeek');
          }
        },

        events: [
          <?php if ($resultadoDentistas): ?>
            <?php while ($row1 = mysqli_fetch_array($resultadoDentistas, MYSQLI_ASSOC)) { ?>
              {
                id: '<?php echo $row1['id_cita']; ?>',
                title: '<?php echo addslashes($row1['tipo']); ?>',
                description: '<?php
                  echo addslashes(
                    'Paciente: ' . (($row1['nombre'] ?? '') . ' ' . ($row1['apellido'] ?? '')) . '<br>' .
                    'Doctor: ' . ($row1['nombreD'] ?? '') . '<br>' .
                    'Fecha: ' . ($row1['fecha_cita'] ?? '') . '<br>' .
                    'Hora: ' . ($row1['hora_cita'] ?? '')
                  );
                ?>',
                start: '<?php echo $row1['fecha_cita']; ?>',
                textColor: 'white'
              },
            <?php } ?>
          <?php endif; ?>
        ],
        eventClick: function(calEvent){
          $('#event-title').text(calEvent.title);
          $('#event-description').html(calEvent.description);
          $('#modal-event').modal('show');
        }
      });
    });
  </script>
</head>

<body>
  <!-- ===== Sidebar unificado (igual al resto) ===== -->
  <aside class="sidebar">
    <div class="brand mb-2">
      <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="side-inner">
      <div class="profile">
        <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle" alt="Perfil">
        <h3 class="name"><?php echo $DOCTOR_NAME; ?></h3>
        <span class="country text-muted">Perfect Teeth</span>
      </div>

      <nav class="nav-menu">
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='citas'?'active':''); ?>" href="/sistema_de_cita_odontologica-main/admin/inicioAdmin.php">
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

  <!-- Main -->
  <main>
    <div class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="far fa-calendar-alt text-primary"></i>
          <span class="text-muted">Calendario</span>
        </div>
        <div class="small text-muted d-none d-sm-block">
          Sesión: <strong><?php echo htmlspecialchars($row['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </div>

    <div class="site-section">
      <div class="container-max">
        <div class="calendar-card">
          <div class="calendar-header">
            <h1 class="calendar-title">Calendario de Citas</h1>
            <div class="text-muted small d-none d-sm-block"><?php echo $DOCTOR_NAME; ?></div>
          </div>
          <div class="calendar-wrapper">
            <div id="calendar"></div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal evento -->
  <div class="modal fade" id="modal-event" tabindex="-1" role="dialog" aria-labelledby="modal-eventLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-primary" id="event-title"></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body text-dark">
          <div id="event-description"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- JS propio -->
  <script src="../src/js/admin.js"></script>
</body>
</html>
