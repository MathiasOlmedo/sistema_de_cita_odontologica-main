<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$resultado = MostrarConsultas($link);
$resultadoDentistas = MostrarDentistas($link);

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
$SIDEBAR_ACTIVE = 'agendar';
$PATIENT_NAME = htmlspecialchars($row['nombre'].' '.$row['apellido']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth – Agendar Cita</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="./src/img/logo.png" type="image/png" />
  
  <link rel="stylesheet" href="src/css/bootstrap.min.css">
  <link rel="stylesheet" href="src/css/font-awesome.min.css">
  
  <!-- jQuery UI para calendario -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  
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

    /* ===== Sidebar (mismo que mis_citas.php) ===== */
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

    /* ===== Main ===== */
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
    }

    .card-header-custom {
      padding:1.25rem 1.5rem;
      background:white;
      border-bottom:2px solid #f0f0f0;
    }
    
    .card-header-custom h3 {
      margin:0;
      font-size:1.5rem;
      color:#333;
      font-weight:700;
    }

    /* ===== Formulario ===== */
    .form-control{
      border-radius:8px;
      border:1px solid #ddd;
      padding:10px 15px;
    }
    .form-control:focus{
      border-color:var(--brand);
      box-shadow:0 0 0 0.2rem rgba(13,110,253,0.15);
    }

    /* ===== Sistema de Horarios ===== */
    #horarios-container {
      min-height:100px;
      background:#f8f9fa;
      padding:20px;
      border-radius:10px;
      margin:15px 0;
      border:2px dashed #ddd;
      text-align:center;
    }
    
    .time-slot {
      display:inline-block;
      margin:6px;
      padding:15px 25px;
      border-radius:10px;
      font-weight:600;
      font-size:1.1rem;
      border:2px solid #ddd;
      cursor:pointer;
      transition:all 0.3s ease;
      min-width:90px;
      text-align:center;
      position:relative;
      overflow:hidden;
    }
    
    .time-slot.available {
      background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color:white;
      border-color:#667eea;
      box-shadow:0 4px 15px rgba(102,126,234,0.3);
    }
    
    .time-slot.available:hover {
      transform:scale(1.1);
      box-shadow:0 6px 25px rgba(102,126,234,0.5);
    }
    
    .time-slot.occupied {
      background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color:white;
      border-color:#f5576c;
      cursor:not-allowed;
      opacity:0.7;
    }
    
    .time-slot.selected {
      background:linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
      color:white !important;
      border-color:#11998e !important;
      box-shadow:0 8px 30px rgba(17,153,142,0.4) !important;
      transform:scale(1.15);
    }
    
    .time-slot.selected::after {
      content:'✓';
      position:absolute;
      top:-5px;
      right:-5px;
      background:white;
      color:#11998e;
      width:25px;
      height:25px;
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:bold;
      box-shadow:0 2px 8px rgba(0,0,0,0.2);
    }

    .loading-indicator {
      text-align:center;
      padding:30px;
      color:#667eea;
      font-size:1.1rem;
    }
    
    .loading-indicator i {
      font-size:2rem;
      animation:spin 1s linear infinite;
    }
    
    @keyframes spin {
      100% { transform:rotate(360deg); }
    }

    /* ===== Botones ===== */
    .btn-gradient {
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
      width:100%;
    }
    
    .btn-gradient:hover {
      transform:translateY(-3px);
      box-shadow:0 6px 25px rgba(102,126,234,0.5);
      color:white;
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
      .time-slot {
        min-width:70px;
        padding:12px 18px;
        font-size:1rem;
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
          <i class="fa fa-calendar-plus-o" style="color:#0d6efd;"></i>
          <span style="font-weight:600;">Agendar Nueva Cita</span>
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

        <!-- Formulario -->
        <div class="card">
          <div class="card-header-custom">
            <h3><i class="fa fa-calendar-plus-o"></i> Nueva Cita</h3>
          </div>

          <div style="padding:1.5rem;">
            <form action="./crud/cita_INSERT.php?opciones=INS" method="POST" enctype="multipart/form-data" autocomplete="off" id="appointment-form">
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="name">Nombre</label>
                  <input type="text" class="form-control" id="name" name="name" readonly value="<?php echo htmlspecialchars($row['nombre']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                  <label for="lastname">Apellido</label>
                  <input type="text" class="form-control" id="lastname" name="lastname" readonly value="<?php echo htmlspecialchars($row['apellido']); ?>">
                </div>
              </div>

              <div class="mb-3">
                <label for="email">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" readonly value="<?php echo htmlspecialchars($row['correo_electronico']); ?>">
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="consultas">Tipo de Consulta</label>
                  <select name="consultas" id="consultas" class="form-control" required>
                    <?php 
                    mysqli_data_seek($resultado, 0);
                    while ($row1 = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
                      echo "<option value='{$row1['id_consultas']}'>" . htmlspecialchars($row1['tipo']) . "</option>";
                    } 
                    ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="dentistas">Dentista</label>
                  <select name="dentistas" id="dentistas" class="form-control" required>
                    <option value="">Seleccione un doctor</option>
                    <?php 
                    mysqli_data_seek($resultadoDentistas, 0);
                    while ($row2 = mysqli_fetch_array($resultadoDentistas, MYSQLI_ASSOC)) {
                      echo "<option value='{$row2['id_doctor']}'>" . htmlspecialchars($row2['nombreD'] . ' ' . $row2['apellido']) . "</option>";
                    } 
                    ?>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label for="fecha_cita">Fecha de la cita</label>
                <input type="date" class="form-control" name="fecha_cita" id="fecha_cita" required min="<?php echo date('Y-m-d'); ?>">
              </div>

              <div class="mb-3">
                <label>Selecciona un horario disponible</label>
                <div id="horarios-container">
                  <div class="loading-indicator">
                    <i class="fa fa-clock-o"></i>
                    <p>Selecciona un doctor y una fecha para ver los horarios disponibles</p>
                  </div>
                </div>
                <input type="hidden" name="hora" id="hora" required>
              </div>

              <div class="mb-3">
                <label for="phone">Teléfono</label>
                <input type="tel" class="form-control" id="phone" name="phone" readonly value="<?php echo htmlspecialchars($row['telefono']); ?>">
              </div>

              <button type="submit" name="enviar" value="enviar" class="btn-gradient" id="cf-submit">
                <i class="fa fa-calendar-check-o"></i> Confirmar Cita
              </button>

            </form>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- Scripts -->
  <script src="src/js/bootstrap.min.js"></script>

  <script>
  $(document).ready(function() {
    // Validación de fecha: Bloquear fines de semana
    $('#fecha_cita').on('change', function() {
      const fecha = new Date(this.value + 'T00:00:00');
      const day = fecha.getUTCDay();
      
      if (day === 0 || day === 6) {
        alert('⚠️ No se permiten citas los fines de semana. Por favor selecciona un día de Lunes a Viernes.');
        $(this).val('');
        $('#horarios-container').html('<div class="loading-indicator"><i class="fa fa-exclamation-triangle"></i><p>Selecciona una fecha válida</p></div>');
      }
    });

    // Sistema de horarios en tiempo real
    function cargarHorarios() {
      const doctor = $("#dentistas").val();
      const fecha = $("#fecha_cita").val();
      const contenedor = $("#horarios-container");
      
      $("#hora").val('');

      if (!doctor || !fecha) {
        contenedor.html('<div class="loading-indicator"><i class="fa fa-info-circle"></i><p>Selecciona un doctor y una fecha para ver los horarios disponibles</p></div>');
        return;
      }

      contenedor.html('<div class="loading-indicator"><i class="fa fa-spinner fa-spin"></i><p>Cargando horarios disponibles...</p></div>');

      $.ajax({
        url: "ajax/horarios_disponibles.php",
        method: "GET",
        data: { doctor: doctor, fecha: fecha, _t: new Date().getTime() },
        cache: false,
        dataType: "json",
        success: function(data) {
          contenedor.empty();

          if (data.error) {
            contenedor.html('<div class="loading-indicator"><i class="fa fa-exclamation-triangle"></i><p>' + data.error + '</p></div>');
            return;
          }

          if (data.length === 0) {
            contenedor.html('<div class="loading-indicator"><i class="fa fa-calendar-times-o"></i><p>No hay horarios disponibles para esta fecha</p></div>');
            return;
          }

          data.forEach(function(horario) {
            const div = $("<div>")
              .addClass("time-slot")
              .addClass(horario.disponible ? "available" : "occupied")
              .html('<i class="fa fa-clock-o"></i> ' + horario.hora)
              .data("hora", horario.hora);

            if (horario.disponible) {
              div.on("click", function() {
                $(".time-slot.available").removeClass("selected");
                $(this).addClass("selected");
                $("#hora").val($(this).data("hora"));
              });
            } else {
              div.attr('title', 'Horario ocupado');
            }

            contenedor.append(div);
          });
        },
        error: function() {
          contenedor.html('<div class="loading-indicator"><i class="fa fa-exclamation-triangle"></i><p>Error al cargar horarios. Por favor intenta nuevamente.</p></div>');
        }
      });
    }

    $("#fecha_cita, #dentistas").on("change", cargarHorarios);

    $("#appointment-form").on("submit", function(e) {
      if (!$("#hora").val()) {
        e.preventDefault();
        alert('⚠️ Por favor selecciona un horario disponible antes de confirmar la cita.');
        $("#horarios-container").css('border-color', '#f5576c');
        return false;
      }
    });
  });
  </script>
</body>
</html>