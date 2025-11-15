<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$vUsuario = $_SESSION['id_doctor'] ?? null;
$resultadoDentistas = $vUsuario ? MostrarCitas($link, $vUsuario) : null;

if (isset($_SESSION['id_doctor'])) {
  $row = consultarDoctor($link, $_SESSION['id_doctor']);
} else {
  $_SESSION['MensajeTexto'] = "Error: acceso al sistema no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

$SIDEBAR_ACTIVE = 'calendario';
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
  <link rel="icon" href="../src/img/logo.png" type="image/png" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.css">
  <link rel="stylesheet" type="text/css" href="../src/css/fullcalendar.css" />

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
      padding:0;
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

    /* ===== CARD DEL CALENDARIO ===== */
    .calendar-card{
      background:#fff;
      border:1px solid rgba(0,0,0,.06);
      border-radius:var(--radius);
      box-shadow:0 4px 16px rgba(0,0,0,.08);
      overflow:hidden;
      display:flex;
      flex-direction:column;
      min-height:calc(100vh - 180px);
      transition:var(--transition);
    }
    .calendar-card:hover{
      box-shadow:0 8px 24px rgba(0,0,0,.12);
      transform:translateY(-2px);
    }

    .calendar-header{
      padding:1.5rem 1.75rem;
      background:linear-gradient(135deg, #fff 0%, #fafbfc 100%);
      border-bottom:1px solid rgba(0,0,0,.06);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:1rem;
    }
    .calendar-header-content{
      display:flex;
      align-items:center;
      gap:1rem;
    }
    .calendar-header-content i{
      font-size:1.5rem;
      color:var(--brand);
      transition:var(--transition);
    }
    .calendar-card:hover .calendar-header-content i{
      transform:scale(1.15);
      color:var(--brand-hover);
    }

    .calendar-title{
      margin:0;
      font-weight:700;
      font-size:1.35rem;
      color:var(--brand);
      transition:var(--transition);
      letter-spacing:.2px;
    }
    .calendar-card:hover .calendar-title{
      color:var(--brand-hover);
    }

    .calendar-subtitle{
      margin:0;
      font-size:0.9rem;
      color:var(--text-muted);
      font-weight:500;
    }

    .calendar-wrapper{
      flex:1 1 auto;
      min-height:0;
      padding:1.75rem;
      background:#fff;
    }

    /* ===== FULLCALENDAR MEJORADO ===== */
    #calendar{
      width:100%;
      height:100%;
      background:#fff;
      border:0;
      border-radius:var(--radius);
      box-shadow:none;
    }
    .fc{ font-size:.95rem; }
    .fc-toolbar{
      margin-bottom:1.5rem;
      display:flex;
      justify-content:center;
      align-items:center;
      gap:1.5rem;
      flex-wrap:wrap;
    }
    .fc-toolbar h2{
      font-size:1.3rem;
      font-weight:700;
      color:var(--brand);
      transition:var(--transition);
      margin:0;
      order:3;
    }
    .fc-toolbar .fc-left{
      float:none !important;
      display:flex;
      justify-content:center;
      align-items:center;
      gap:0.5rem;
      order:1;
    }
    .fc-toolbar .fc-center{
      float:none !important;
      display:none;
    }
    .fc-toolbar .fc-right{
      float:none !important;
      display:flex;
      justify-content:center;
      align-items:center;
      gap:0.5rem;
      order:2;
    }
    .fc-button{
      border-radius:var(--radius) !important;
      border:1px solid rgba(13,110,253,.25) !important;
      background:#fff !important;
      color:var(--brand) !important;
      text-shadow:none !important;
      box-shadow:none !important;
      padding:0.65rem 1.15rem !important;
      margin:0 0.25rem !important;
      transition:var(--transition) !important;
      cursor:pointer !important;
      font-weight:500;
      font-size:0.9rem;
      display:flex !important;
      align-items:center !important;
      justify-content:center !important;
      text-align:center !important;
      white-space:nowrap;
    }
    .fc-button:hover{
      background:var(--brand-50) !important;
      color:var(--brand) !important;
      transform:translateY(-2px) !important;
      box-shadow:0 4px 12px rgba(13,110,253,.2) !important;
      border-color:var(--brand) !important;
    }
    .fc-state-active, .fc-button.fc-state-active{
      background:linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%) !important;
      color:#fff !important;
      border-color:var(--brand) !important;
      font-weight:600;
      box-shadow:0 4px 14px rgba(13,110,253,.35) !important;
    }
    .fc-state-active:hover, .fc-button.fc-state-active:hover{
      background:linear-gradient(135deg, var(--brand-hover) 0%, var(--brand) 100%) !important;
      color:#fff !important;
    }
    .fc-button:focus{
      outline:none !important;
      box-shadow:0 0 0 .2rem rgba(13,110,253,.25) !important;
    }

    .fc-day-grid-event .fc-content{
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      padding:2px 4px;
    }

    /* ===== MODAL MEJORADO ===== */
    .modal-content{
      border:1px solid rgba(0,0,0,.06);
      border-radius:var(--radius);
      box-shadow:0 8px 24px rgba(0,0,0,.12);
    }
    .modal-header{
      background:linear-gradient(135deg, var(--brand-50) 0%, #fff 100%);
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:1.25rem 1.75rem;
    }
    .modal-header .modal-title{
      color:var(--brand);
      font-weight:700;
    }
    .modal-body{
      padding:1.75rem;
      color:var(--text);
    }
    .modal-footer{
      border-top:1px solid rgba(0,0,0,.06);
      padding:1.25rem 1.75rem;
      background:#f5f7fa;
    }
    .btn-secondary{
      background:var(--text-muted);
      border-color:var(--text-muted);
      transition:var(--transition);
    }
    .btn-secondary:hover{
      background:var(--text);
      border-color:var(--text);
      transform:translateY(-2px);
      box-shadow:0 4px 12px rgba(0,0,0,.15);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width:992px){
      :root{ --sidebar-w:220px; }
      .sidebar{ padding:0.75rem 0.5rem; }
      .container-max{ padding:0 1.25rem; }
      .calendar-header{ padding:1.25rem 1.5rem; }
      .calendar-wrapper{ padding:1.5rem; }
      .calendar-title{ font-size:1.2rem; }
    }
    @media (max-width:768px){
      .topbar .d-none.d-sm-block{ display:none !important; }
      .calendar-header{ flex-direction:column; align-items:flex-start; }
      .calendar-header-content i{ display:none; }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:200px; }
      .container-max{ padding:0 1rem; }
      .content{ padding:1.5rem 0; }
      .calendar-header{ padding:1.1rem 1.25rem; }
      .calendar-wrapper{ padding:1.25rem; }
      .calendar-title{ font-size:1.1rem; }
      .fc-toolbar{ gap:0.5rem; margin-bottom:1rem; }
      .fc-button{ padding:0.55rem 0.95rem !important; margin:0.15rem !important; font-size:0.8rem; }
      .fc-toolbar h2{ font-size:1.1rem; }
    }
  </style>

  <script src="https://code.jquery.com/jquery-3.2.1.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="../src/js/lib/FullCalendar/moment.min.js"></script>
  <script type="text/javascript" src="../src/js/lib/FullCalendar/fullcalendar.min.js"></script>
  <script type="text/javascript" src="../src/js/lib/FullCalendar/locale/es.js"></script>

  <script>
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
        height: 'parent',
        contentHeight: 'auto',
        handleWindowResize: true,
        aspectRatio: 1.45,
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
          <i class="far fa-calendar-alt"></i>
          <span class="text-muted">Calendario</span>
        </div>
        <div class="small text-muted d-none d-sm-block">
          Usuario:  <strong><?php echo htmlspecialchars($row['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </header>

    <div class="content">
      <div class="container-max">
        <div class="calendar-card">
          <div class="calendar-header">
            <div class="calendar-header-content">
              <i class="fas fa-calendar-check"></i>
              <div>
                <h1 class="calendar-title mb-0">Calendario de Citas</h1>
                <p class="calendar-subtitle mt-1">
                   Vista completa de tus citas
                </p>
              </div>
            </div>
            <div class="text-muted small d-none d-sm-block"><?php echo $DOCTOR_NAME; ?></div>
          </div>
          <div class="calendar-wrapper">
            <div id="calendar"></div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- MODAL EVENTO -->
  <div class="modal fade" id="modal-event" tabindex="-1" role="dialog" aria-labelledby="modal-eventLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="event-title"></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="event-description"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../src/js/admin.js"></script>
</body>
</html>