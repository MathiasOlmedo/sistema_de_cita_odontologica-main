<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

$resultado = MostrarConsultas($link);
$resultadoDentistas = MostrarDentistas($link);

if (isset($_SESSION['id_paciente'])) {
     $vUsuario = $_SESSION['id_paciente'];
     $row = consultarPaciente($link, $vUsuario);
} else {
     $_SESSION['MensajeTexto'] = "Error acceso al sistema no registrado.";
     $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
     header("Location: ./index.php");
     exit();
}

/* ============================================================
   Consultas para "Mi Panel del Paciente"
   ============================================================ */

// 1) Mis Presupuestos + Pagos
$presupuestos = [];
$sqlPres = "
  SELECT 
    p.id_presupuesto, p.folio, p.fecha, p.total, p.estado, p.pdf_path,
    COALESCE(SUM(pg.monto),0) AS pagado
  FROM presupuesto p
  LEFT JOIN pagos pg ON pg.id_presupuesto = p.id_presupuesto
  WHERE p.id_paciente = ?
  GROUP BY p.id_presupuesto
  ORDER BY p.fecha DESC
  LIMIT 5
";
if ($stmt = $link->prepare($sqlPres)) {
  $stmt->bind_param('i', $vUsuario);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $r['saldo_calc'] = (float)$r['total'] - (float)$r['pagado'];
    if ($r['saldo_calc'] < 0) $r['saldo_calc'] = 0.0;
    $presupuestos[] = $r;
  }
  $stmt->close();
}

// 2) Tratamientos realizados (últimos 10)
$tratamientos = [];
$sqlTrat = "
  SELECT 
    pd.diente, pd.lado, pd.procedimiento, pd.precio, 
    p.fecha AS fecha_presupuesto,
    CONCAT(d.nombreD,' ',d.apellido) AS doctor
  FROM presupuesto_detalle pd
  INNER JOIN presupuesto p ON p.id_presupuesto = pd.id_presupuesto
  LEFT JOIN doctor d ON d.id_doctor = p.id_doctor
  WHERE p.id_paciente = ? AND p.estado = 'aprobado'
  ORDER BY p.fecha DESC, pd.id DESC
  LIMIT 10
";
if ($stmt = $link->prepare($sqlTrat)) {
  $stmt->bind_param('i', $vUsuario);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $tratamientos[] = $r;
  $stmt->close();
}

// 3) Mis Citas (próximas primero)
$misCitas = [];
$sqlCitas = "
  SELECT 
    c.id_cita, c.fecha_cita, c.hora_cita, c.estado,
    CONCAT(d.nombreD,' ',d.apellido) AS doctor,
    con.tipo AS tipo_consulta,
    CASE 
      WHEN c.fecha_cita > CURDATE() THEN 'proxima'
      WHEN c.fecha_cita = CURDATE() THEN 'hoy'
      ELSE 'pasada'
    END as temporalidad
  FROM citas c
  INNER JOIN doctor d ON d.id_doctor = c.id_doctor
  LEFT JOIN consultas con ON con.id_consultas = c.id_consultas
  WHERE c.id_paciente = ?
  ORDER BY 
    CASE WHEN c.fecha_cita >= CURDATE() THEN 0 ELSE 1 END,
    c.fecha_cita DESC, 
    c.hora_cita DESC
  LIMIT 15
";
if ($stmt = $link->prepare($sqlCitas)) {
  $stmt->bind_param('i', $vUsuario);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $misCitas[] = $r;
  $stmt->close();
}

// 4) Estadísticas del paciente
$totalCitas = count($misCitas);
$citasPendientes = 0;
$citasRealizadas = 0;
$proximasCitas = 0;

foreach ($misCitas as $cita) {
    if ($cita['estado'] === 'I' && $cita['temporalidad'] !== 'pasada') $citasPendientes++;
    if ($cita['estado'] === 'A') $citasRealizadas++;
    if ($cita['temporalidad'] === 'proxima' || $cita['temporalidad'] === 'hoy') $proximasCitas++;
}

$totalPresupuestos = count($presupuestos);
$presupuestosPendientes = 0;
foreach ($presupuestos as $p) {
    if ($p['estado'] === 'pendiente' || $p['estado'] === 'enviado') $presupuestosPendientes++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
     <title>Perfect Teeth - Mi Panel</title>
     <link rel="icon" href="./src/img/logo.png" type="image/png" />
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

     <link rel="stylesheet" href="src/css/bootstrap.min.css">
     <link rel="stylesheet" href="src/css/font-awesome.min.css">
     <link rel="stylesheet" href="src/css/animate.css">
     <link rel="stylesheet" href="src/css/owl.carousel.css">
     <link rel="stylesheet" href="src/css/owl.theme.default.min.css">
     <link rel="stylesheet" href="src/css/tooplate-style.css">
     
     <!-- jQuery UI para calendario -->
     <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
     <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
     <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

     <style>
       /* ===== DASHBOARD DEL PACIENTE ===== */
       .patient-dashboard {
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         padding: 60px 0;
         color: white;
       }
       
       .dashboard-header {
         text-align: center;
         margin-bottom: 40px;
       }
       
       .dashboard-header h1 {
         font-size: 2.5rem;
         font-weight: bold;
         margin-bottom: 10px;
         text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
       }
       
       .dashboard-stats {
         background: white;
         border-radius: 15px;
         padding: 30px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.15);
         margin-bottom: 30px;
       }
       
       .stat-box {
         text-align: center;
         padding: 25px 15px;
         border-radius: 10px;
         transition: all 0.3s ease;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         color: white;
         margin-bottom: 15px;
         position: relative;
         overflow: hidden;
       }
       
       .stat-box::before {
         content: '';
         position: absolute;
         top: -50%;
         right: -50%;
         width: 200%;
         height: 200%;
         background: rgba(255,255,255,0.1);
         transform: rotate(45deg);
         transition: all 0.5s;
       }
       
       .stat-box:hover::before {
         right: -100%;
       }
       
       .stat-box:hover {
         transform: translateY(-5px);
         box-shadow: 0 15px 35px rgba(102,126,234,0.4);
       }
       
       .stat-box h3 {
         font-size: 3rem;
         margin: 10px 0;
         font-weight: bold;
         position: relative;
         z-index: 1;
       }
       
       .stat-box p {
         margin: 0;
         font-size: 1rem;
         opacity: 0.95;
         position: relative;
         z-index: 1;
         text-transform: uppercase;
         letter-spacing: 1px;
       }
       
       .stat-box i {
         font-size: 2rem;
         opacity: 0.8;
         margin-bottom: 10px;
       }
       
       /* ===== CARDS MEJORADAS ===== */
       .dashboard-card {
         background: white;
         border-radius: 15px;
         padding: 30px;
         margin-bottom: 25px;
         box-shadow: 0 5px 20px rgba(0,0,0,0.08);
         transition: all 0.3s ease;
         border: 1px solid #f0f0f0;
       }
       
       .dashboard-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 35px rgba(0,0,0,0.15);
       }
       
       .card-header-custom {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 20px;
         padding-bottom: 15px;
         border-bottom: 2px solid #f0f0f0;
       }
       
       .card-header-custom h3 {
         margin: 0;
         font-size: 1.5rem;
         color: #333;
         font-weight: 700;
       }
       
       .card-header-custom .icon {
         font-size: 1.8rem;
         color: #667eea;
       }
       
       /* ===== SISTEMA DE HORARIOS EN TIEMPO REAL ===== */
       #horarios-container {
         min-height: 100px;
         background: #f8f9fa;
         padding: 20px;
         border-radius: 10px;
         margin: 15px 0;
         border: 2px dashed #ddd;
         text-align: center;
       }
       
       .time-slot {
         display: inline-block;
         margin: 6px;
         padding: 15px 25px;
         border-radius: 10px;
         font-weight: 600;
         font-size: 1.1rem;
         border: 2px solid #ddd;
         cursor: pointer;
         transition: all 0.3s ease;
         min-width: 90px;
         text-align: center;
         position: relative;
         overflow: hidden;
       }
       
       .time-slot::before {
         content: '';
         position: absolute;
         top: 50%;
         left: 50%;
         width: 0;
         height: 0;
         border-radius: 50%;
         background: rgba(255,255,255,0.4);
         transform: translate(-50%, -50%);
         transition: width 0.3s, height 0.3s;
       }
       
       .time-slot:hover::before {
         width: 300px;
         height: 300px;
       }
       
       .time-slot.available {
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         color: white;
         border-color: #667eea;
         box-shadow: 0 4px 15px rgba(102,126,234,0.3);
       }
       
       .time-slot.available:hover {
         transform: scale(1.1);
         box-shadow: 0 6px 25px rgba(102,126,234,0.5);
       }
       
       .time-slot.occupied {
         background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
         color: white;
         border-color: #f5576c;
         cursor: not-allowed;
         opacity: 0.7;
       }
       
       .time-slot.selected {
         background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
         color: white !important;
         border-color: #11998e !important;
         box-shadow: 0 8px 30px rgba(17,153,142,0.4) !important;
         transform: scale(1.15);
       }
       
       .time-slot.selected::after {
         content: '✓';
         position: absolute;
         top: -5px;
         right: -5px;
         background: white;
         color: #11998e;
         width: 25px;
         height: 25px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         font-weight: bold;
         box-shadow: 0 2px 8px rgba(0,0,0,0.2);
       }
       
       /* ===== TIMELINE DE CITAS ===== */
       .timeline {
         position: relative;
         padding-left: 40px;
         margin-top: 20px;
       }
       
       .timeline::before {
         content: '';
         position: absolute;
         left: 15px;
         top: 0;
         bottom: 0;
         width: 3px;
         background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
         border-radius: 3px;
       }
       
       .timeline-item {
         position: relative;
         padding: 20px;
         margin-bottom: 20px;
         background: white;
         border-radius: 10px;
         box-shadow: 0 3px 10px rgba(0,0,0,0.08);
         transition: all 0.3s ease;
       }
       
       .timeline-item:hover {
         transform: translateX(10px);
         box-shadow: 0 5px 20px rgba(0,0,0,0.15);
       }
       
       .timeline-item::before {
         content: '';
         position: absolute;
         left: -34px;
         top: 25px;
         width: 18px;
         height: 18px;
         border-radius: 50%;
         background: #667eea;
         border: 4px solid white;
         box-shadow: 0 0 0 3px #667eea;
         z-index: 2;
       }
       
       .timeline-item.completed::before {
         background: #11998e;
         box-shadow: 0 0 0 3px #11998e;
       }
       
       .timeline-item.proxima::before {
         background: #f5576c;
         box-shadow: 0 0 0 3px #f5576c;
         animation: pulse 2s infinite;
       }
       
       @keyframes pulse {
         0%, 100% { transform: scale(1); }
         50% { transform: scale(1.2); }
       }
       
       .timeline-item .timeline-date {
         font-weight: bold;
         color: #667eea;
         margin-bottom: 5px;
       }
       
       .timeline-item .timeline-content {
         color: #666;
       }
       
       /* ===== TABLA MODERNA ===== */
       .table-modern {
         border-collapse: separate;
         border-spacing: 0 12px;
       }
       
       .table-modern thead th {
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         color: white;
         padding: 15px;
         border: none;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 1px;
         font-size: 0.85rem;
       }
       
       .table-modern thead th:first-child {
         border-top-left-radius: 10px;
         border-bottom-left-radius: 10px;
       }
       
       .table-modern thead th:last-child {
         border-top-right-radius: 10px;
         border-bottom-right-radius: 10px;
       }
       
       .table-modern tbody tr {
         background: white;
         box-shadow: 0 3px 10px rgba(0,0,0,0.06);
         transition: all 0.3s ease;
       }
       
       .table-modern tbody tr:hover {
         box-shadow: 0 5px 20px rgba(0,0,0,0.12);
         transform: translateY(-3px);
       }
       
       .table-modern td {
         border: none;
         padding: 18px;
         vertical-align: middle;
         color: #333;
       }
       
       .table-modern td:first-child {
         border-top-left-radius: 10px;
         border-bottom-left-radius: 10px;
       }
       
       .table-modern td:last-child {
         border-top-right-radius: 10px;
         border-bottom-right-radius: 10px;
       }
       
       /* ===== BADGES PERSONALIZADOS ===== */
       .badge-custom {
         padding: 8px 18px;
         border-radius: 20px;
         font-weight: 600;
         font-size: 0.85rem;
         text-transform: uppercase;
         letter-spacing: 0.5px;
       }
       
       .badge-pendiente {
         background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
         color: white;
       }
       
       .badge-aprobado {
         background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
         color: white;
       }
       
       .badge-completada {
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         color: white;
       }
       
       /* ===== ALERTAS Y MENSAJES ===== */
       .alert-custom {
         border-radius: 10px;
         border: none;
         padding: 15px 20px;
         box-shadow: 0 4px 15px rgba(0,0,0,0.1);
       }
       
       .loading-indicator {
         text-align: center;
         padding: 30px;
         color: #667eea;
         font-size: 1.1rem;
       }
       
       .loading-indicator i {
         font-size: 2rem;
         animation: spin 1s linear infinite;
       }
       
       @keyframes spin {
         100% { transform: rotate(360deg); }
       }
       
       /* ===== BOTONES MEJORADOS ===== */
       .btn-gradient {
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         color: white;
         border: none;
         padding: 12px 30px;
         border-radius: 25px;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 1px;
         transition: all 0.3s ease;
         box-shadow: 0 4px 15px rgba(102,126,234,0.3);
       }
       
       .btn-gradient:hover {
         transform: translateY(-3px);
         box-shadow: 0 6px 25px rgba(102,126,234,0.5);
         color: white;
       }
       
       /* ===== RESPONSIVE ===== */
       @media (max-width: 768px) {
         .dashboard-header h1 {
           font-size: 1.8rem;
         }
         
         .stat-box h3 {
           font-size: 2rem;
         }
         
         .time-slot {
           min-width: 70px;
           padding: 12px 18px;
           font-size: 1rem;
         }
         
         .timeline {
           padding-left: 30px;
         }
       }
     </style>
</head>

<body id="top" data-spy="scroll" data-target=".navbar-collapse" data-offset="50">

     <!-- PRE LOADER -->
     <section class="preloader">
          <div class="spinner">
               <span class="spinner-rotate"></span>
          </div>
     </section>

     <!-- HEADER -->
     <header>
          <div class="container">
               <div class="row">
                    <div class="col-md-4 col-sm-10">
                         <p>
                          <?php 
                            if ($row['sexo'] == 'Masculino') {
                              echo "Bienvenido " . htmlspecialchars($row['nombre'] . ' ' . $row['apellido']);
                            } else {
                              echo "Bienvenida " . htmlspecialchars($row['nombre'] . ' ' . $row['apellido']);
                            } 
                          ?>
                         </p>
                    </div>
                    <div class="col-md-8 col-sm-10">
                         <span class="phone-icon"><i class="fa fa-phone"></i> +1 (849) 856 4014</span>
                         <span class="date-icon"><i class="fa fa-calendar-plus-o"></i> 8:00 AM - 7:00 PM (Lun-Vie)</span>
                         <span class="email-icon"><i class="fa fa-envelope-o"></i> <a href="#">perfect-teeth00@hotmail.com</a></span>
                         <span><i class="fa fa-sign-out"></i><a href="./php/cerrar.php">Cerrar Sesión</a></span>
                    </div>
               </div>
          </div>
     </header>

     <!-- MENU -->
     <section class="navbar navbar-default navbar-static-top" role="navigation">
          <div class="container">
               <div class="navbar-header">
                    <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                    </button>
                    <a href="principal.php" class="navbar-brand"><img src="src/img/logo.png" width="20px" height="20px" alt="Logo"></a>
                    <a href="principal.php" class="navbar-brand">Perfect Teeth</a>
               </div>

               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                         <li><a href="#top" class="smoothScroll">Mi Panel</a></li>
                         <li><a href="#about" class="smoothScroll">Nosotros</a></li>
                         <li><a href="#team" class="smoothScroll">Dentistas</a></li>
                         <li><a href="#mis-citas" class="smoothScroll">Mis Citas</a></li>
                         <li><a href="#appointment" class="smoothScroll">Nueva Cita</a></li>
                         <li><a href="#perfil" class="smoothScroll">Perfil</a></li>
                    </ul>
               </div>
          </div>
     </section>

     <!-- Mensajes de alerta -->
     <?php if (isset($_SESSION['MensajeTexto'])): ?>
     <div class="container" style="margin-top: 20px;">
          <div class="alert alert-custom <?php echo strpos($_SESSION['MensajeTipo'], 'success') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade in" role="alert">
               <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
               <strong><i class="fa fa-info-circle"></i></strong> <?php echo htmlspecialchars($_SESSION['MensajeTexto']); ?>
          </div>
     </div>
     <?php 
          $_SESSION['MensajeTexto'] = null;
          $_SESSION['MensajeTipo'] = null;
     endif; 
     ?>

     <!-- ===== DASHBOARD DEL PACIENTE ===== -->
     <section class="patient-dashboard">
          <div class="container">
               <div class="dashboard-header wow fadeInDown" data-wow-delay="0.2s">
                    <h1><i class="fa fa-user-circle"></i> Mi Panel Personal</h1>
                    <p style="font-size: 1.2rem; opacity: 0.9;">Gestiona tus citas, presupuestos y historial médico</p>
               </div>

               <!-- Estadísticas -->
               <div class="dashboard-stats wow fadeInUp" data-wow-delay="0.4s">
                    <div class="row">
                         <div class="col-md-3 col-sm-6">
                              <div class="stat-box">
                                   <i class="fa fa-calendar-check-o"></i>
                                   <h3><?php echo $proximasCitas; ?></h3>
                                   <p>Citas Próximas</p>
                              </div>
                         </div>
                         <div class="col-md-3 col-sm-6">
                              <div class="stat-box" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                   <i class="fa fa-check-circle"></i>
                                   <h3><?php echo $citasRealizadas; ?></h3>
                                   <p>Citas Realizadas</p>
                              </div>
                         </div>
                         <div class="col-md-3 col-sm-6">
                              <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                   <i class="fa fa-file-text-o"></i>
                                   <h3><?php echo $totalPresupuestos; ?></h3>
                                   <p>Presupuestos</p>
                              </div>
                         </div>
                         <div class="col-md-3 col-sm-6">
                              <div class="stat-box" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                   <i class="fa fa-heartbeat"></i>
                                   <h3><?php echo count($tratamientos); ?></h3>
                                   <p>Tratamientos</p>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- ===== MIS CITAS (TIMELINE) ===== -->
     <section id="mis-citas" style="padding: 60px 0; background: #f5f5f5;">
          <div class="container">
               <div class="dashboard-card wow fadeInUp" data-wow-delay="0.2s">
                    <div class="card-header-custom">
                         <h3><i class="fa fa-calendar"></i> Mis Citas</h3>
                         <span class="icon"><i class="fa fa-stethoscope"></i></span>
                    </div>

                    <?php if (empty($misCitas)): ?>
                         <div style="text-align: center; padding: 40px; color: #999;">
                              <i class="fa fa-calendar-times-o" style="font-size: 4rem; margin-bottom: 20px;"></i>
                              <h4>No tienes citas registradas</h4>
                              <p>¡Agenda tu primera cita para comenzar tu tratamiento!</p>
                              <a href="#appointment" class="btn btn-gradient smoothScroll">Agendar Cita</a>
                         </div>
                    <?php else: ?>
                         <div class="timeline">
                              <?php foreach ($misCitas as $cita): 
                                   $esProxima = $cita['temporalidad'] === 'proxima' || $cita['temporalidad'] === 'hoy';
                                   $claseEstado = $cita['estado'] === 'A' ? 'completed' : ($esProxima ? 'proxima' : '');
                              ?>
                              <div class="timeline-item <?php echo $claseEstado; ?>">
                                   <div class="timeline-date">
                                        <i class="fa fa-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($cita['fecha_cita'])); ?> 
                                        - <?php echo substr($cita['hora_cita'], 0, 5); ?>
                                   </div>
                                   <div class="timeline-content">
                                        <strong><?php echo htmlspecialchars($cita['tipo_consulta']); ?></strong><br>
                                        <small><i class="fa fa-user-md"></i> Dr. <?php echo htmlspecialchars($cita['doctor']); ?></small>
                                        <span class="badge-custom <?php 
                                             if ($cita['estado'] === 'A') echo 'badge-completada';
                                             elseif ($esProxima) echo 'badge-pendiente';
                                             else echo 'badge-aprobado';
                                        ?>" style="float: right;">
                                             <?php 
                                                  if ($cita['estado'] === 'A') echo 'Realizada';
                                                  elseif ($cita['temporalidad'] === 'hoy') echo 'Hoy';
                                                  elseif ($esProxima) echo 'Próxima';
                                                  else echo 'Pasada';
                                             ?>
                                        </span>
                                   </div>
                              </div>
                              <?php endforeach; ?>
                         </div>
                    <?php endif; ?>
               </div>

               <!-- Mis Presupuestos -->
               <?php if (!empty($presupuestos)): ?>
               <div class="dashboard-card wow fadeInUp" data-wow-delay="0.4s">
                    <div class="card-header-custom">
                         <h3><i class="fa fa-file-text"></i> Mis Presupuestos</h3>
                         <span class="icon"><i class="fa fa-money"></i></span>
                    </div>

                    <div class="table-responsive">
                         <table class="table table-modern">
                              <thead>
                                   <tr>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Pagado</th>
                                        <th>Saldo</th>
                                        <th>Estado</th>
                                        <th>PDF</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <?php foreach ($presupuestos as $p): ?>
                                   <tr>
                                        <td><strong><?php echo htmlspecialchars($p['folio']); ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($p['fecha'])); ?></td>
                                        <td>Gs <?php echo number_format($p['total'], 0, ',', '.'); ?></td>
                                        <td class="text-success">Gs <?php echo number_format($p['pagado'], 0, ',', '.'); ?></td>
                                        <td class="text-danger"><strong>Gs <?php echo number_format($p['saldo_calc'], 0, ',', '.'); ?></strong></td>
                                        <td>
                                             <span class="badge-custom <?php 
                                                  if ($p['estado'] === 'aprobado') echo 'badge-aprobado';
                                                  else echo 'badge-pendiente';
                                             ?>">
                                                  <?php echo ucfirst($p['estado']); ?>
                                             </span>
                                        </td>
                                        <td>
                                             <?php if (!empty($p['pdf_path'])): ?>
                                                  <a href="<?php echo htmlspecialchars($p['pdf_path']); ?>" target="_blank" class="btn btn-sm btn-gradient">
                                                       <i class="fa fa-file-pdf-o"></i> Ver PDF
                                                  </a>
                                             <?php else: ?>
                                                  <span class="text-muted">Sin PDF</span>
                                             <?php endif; ?>
                                        </td>
                                   </tr>
                                   <?php endforeach; ?>
                              </tbody>
                         </table>
                    </div>
               </div>
               <?php endif; ?>

               <!-- Mis Tratamientos -->
               <?php if (!empty($tratamientos)): ?>
               <div class="dashboard-card wow fadeInUp" data-wow-delay="0.6s">
                    <div class="card-header-custom">
                         <h3><i class="fa fa-medkit"></i> Mis Tratamientos Realizados</h3>
                         <span class="icon"><i class="fa fa-tooth"></i></span>
                    </div>

                    <div class="table-responsive">
                         <table class="table table-modern">
                              <thead>
                                   <tr>
                                        <th>Diente</th>
                                        <th>Lado</th>
                                        <th>Procedimiento</th>
                                        <th>Precio</th>
                                        <th>Fecha</th>
                                        <th>Doctor</th>
                                   </tr>
                              </thead>
                              <tbody>
                                   <?php foreach ($tratamientos as $t): ?>
                                   <tr>
                                        <td><strong>#<?php echo htmlspecialchars($t['diente']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($t['lado']); ?></td>
                                        <td><?php echo htmlspecialchars($t['procedimiento']); ?></td>
                                        <td><strong>Gs <?php echo number_format($t['precio'], 0, ',', '.'); ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($t['fecha_presupuesto'])); ?></td>
                                        <td><small>Dr. <?php echo htmlspecialchars($t['doctor']); ?></small></td>
                                   </tr>
                                   <?php endforeach; ?>
                              </tbody>
                         </table>
                    </div>
               </div>
               <?php endif; ?>
          </div>
     </section>

     <!-- ABOUT -->
     <section id="about">
          <div class="container">
               <div class="row">
                    <div class="col-md-6 col-sm-6">
                         <div class="about-info">
                              <h2 class="wow fadeInUp" data-wow-delay="0.6s">Bienvenido a Perfect Teeth</h2>
                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <p>En esta Clínica se ofrecen Servicios Odontológicos a niños y adultos en las diferentes ramas de la Odontología. Entre ellas podemos incluir: Diagnóstico, Emergencias, Radiología, Periodoncia, Operatoria Dental, Odontopediatría, Endodoncia, Prótesis (Fija, Parcial Removible y Total), Cirugía y Ortodoncia.</p>
                                   <h5>Visión</h5>
                                   <p>Ser la institución líder en servicios odontológicos a nivel nacional y América Latina; logrando la expansión de las coberturas, la mejora continua de los procesos y garantizando calidad y profesionalidad.</p>
                                   <h5>Misión</h5>
                                   <p>Brindar un servicio de excelencia en el área de salud oral, basado en conocimientos, alta tecnología y calidez humana que cubran las necesidades y expectativas de nuestros pacientes e interesados.</p>
                              </div>
                              <figure class="profile wow fadeInUp" data-wow-delay="1s">
                                   <img src="src/img/author-image.jpg" class="img-responsive" alt="">
                                   <figcaption>
                                        <h3>Dr. Jiminian Cruz</h3>
                                        <p>Odontólogo general</p>
                                   </figcaption>
                              </figure>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- TEAM -->
     <section id="team" data-stellar-background-ratio="1">
          <div class="container">
               <div class="row">
                    <div class="col-md-6 col-sm-6">
                         <div class="about-info">
                              <h2 class="wow fadeInUp" data-wow-delay="0.1s">Nuestros dentistas</h2>
                         </div>
                    </div>
                    <div class="clearfix"></div>

                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.2s">
                              <img src="src/img/team-image1.jpg" class="img-responsive" alt="">
                              <div class="team-info">
                                   <h3>Francisco Rosario</h3>
                                   <p>Odontopediatra</p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i> +1 (829) 856 4014</p>
                                        <p><i class="fa fa-envelope-o"></i> <a href="#">francisco@hotmail.com</a></p>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.4s">
                              <img src="src/img/team-image2.jpg" class="img-responsive" alt="">
                              <div class="team-info">
                                   <h3>Stewart Diaz</h3>
                                   <p>Ortodoncista/Endodoncista</p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i> 010-070-0170</p>
                                        <p><i class="fa fa-envelope-o"></i> <a href="#">Stewart@company.com</a></p>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.6s">
                              <img src="src/img/team-image3.jpg" class="img-responsive" alt="">
                              <div class="team-info">
                                   <h3>Arlenis Hernández</h3>
                                   <p>Patólogo oral</p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i> +1 (829) 462-9992</p>
                                        <p><i class="fa fa-envelope-o"></i> <a href="#">arlenis@company.com</a></p>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- ===== AGENDAR NUEVA CITA (SISTEMA DE HORARIOS EN TIEMPO REAL) ===== -->
     <section id="appointment" data-stellar-background-ratio="3">
          <div class="container">
               <div class="row">
                    <div class="col-md-6 col-sm-6">
                         <img src="src/img/appointment-image.jpg" class="img-responsive" alt="">
                    </div>

                    <div class="col-md-6 col-sm-6">
                         <form action="./crud/cita_INSERT.php?opciones=INS" method="POST" enctype="multipart/form-data" autocomplete="off" id="appointment-form">
                              <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                   <h2>Agendar Nueva Cita</h2>
                              </div>

                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <div class="col-md-6 col-sm-6">
                                        <label for="name">Nombre</label>
                                        <input type="text" class="form-control" id="name" name="name" readonly value="<?php echo htmlspecialchars($row['nombre']); ?>">
                                   </div>

                                   <div class="col-md-6 col-sm-6">
                                        <label for="lastname">Apellido</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" readonly value="<?php echo htmlspecialchars($row['apellido']); ?>">
                                   </div>

                                   <div class="col-md-12 col-sm-12">
                                        <label for="email">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="email" name="email" readonly value="<?php echo htmlspecialchars($row['correo_electronico']); ?>">
                                   </div>

                                   <div class="col-md-6 col-sm-6">
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

                                   <div class="col-md-6 col-sm-6">
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

                                   <div class="col-md-12 col-sm-12">
                                        <label for="fecha_cita">Fecha de la cita</label>
                                        <input type="date" class="form-control" name="fecha_cita" id="fecha_cita" required min="<?php echo date('Y-m-d'); ?>">
                                   </div>

                                   <div class="col-md-12 col-sm-12">
                                        <label>Selecciona un horario disponible</label>
                                        <div id="horarios-container">
                                             <div class="loading-indicator">
                                                  <i class="fa fa-clock-o"></i>
                                                  <p>Selecciona un doctor y una fecha para ver los horarios disponibles</p>
                                             </div>
                                        </div>
                                        <input type="hidden" name="hora" id="hora" required>
                                   </div>

                                   <div class="col-md-12 col-sm-12">
                                        <label for="phone">Teléfono</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" readonly value="<?php echo htmlspecialchars($row['telefono']); ?>">
                                   </div>

                                   <div class="col-md-12 col-sm-12">
                                        <br><button type="submit" name="enviar" value="enviar" class="form-control btn btn-gradient" id="cf-submit">
                                             <i class="fa fa-calendar-check-o"></i> Confirmar Cita
                                        </button>
                                   </div>
                              </div>
                         </form>
                    </div>
               </div>
          </div>
     </section>

     <!-- PERFIL -->
     <section id="perfil" style="margin-top: 5%; background: #f5f5f5; padding: 60px 0;">
          <div class="container">
               <div class="main-body">
                    <div class="row gutters-sm">
                         <div class="col-md-4 mb-3">
                              <div class="dashboard-card">
                                   <div class="card-body">
                                        <div class="d-flex flex-column align-items-center text-center">
                                             <?php if ($row['sexo'] == 'Masculino'): ?>
                                                  <img src="./src/img/iconoH.jpg" class="rounded-circle" width="150" height="150" style="object-fit: cover;">
                                             <?php else: ?>
                                                  <img src="./src/img/iconoM.jpg" class="rounded-circle" width="150" height="150" style="object-fit: cover;">
                                             <?php endif; ?>
                                             <div class="mt-3">
                                                  <h3><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></h3>
                                                  <p class="text-secondary mb-1">Perfect Teeth</p>
                                                  <p class="text-muted font-size-sm"><?php echo htmlspecialchars($row['correo_electronico']); ?></p>
                                                  
                                                  <div style="margin-top: 30px;">
                                                       <a class="btn btn-gradient" href="./editar_paciente.php">
                                                            <i class="fa fa-edit"></i> Editar Perfil
                                                       </a>
                                                       <br><br>
                                                       <a class="btn btn-gradient" target="_blank" href="/sistema_de_cita_odontologica-main/Reportes/reporte.php">
                                                            <i class="fa fa-file-pdf-o"></i> Reporte de Citas
                                                       </a>
                                                       <br><br>
                                                       <a class="btn btn-gradient" target="_blank" href="./Reportes/reporteH.php">
                                                            <i class="fa fa-history"></i> Historial Completo
                                                       </a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                         </div>

                         <div class="col-md-8">
                              <div class="dashboard-card mb-3">
                                   <div class="card-body">
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Nombre</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['nombre']); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Apellido</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['apellido']); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Cédula</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['cedula']); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Sexo</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['sexo']); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Correo</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['correo_electronico']); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Teléfono</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['telefono']); ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Fecha de Nacimiento</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo htmlspecialchars($row['fecha_nacimiento']); ?></div>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- GOOGLE MAP -->
     <section id="google-map">
          <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d901.4595953705438!2d-57.51119473043212!3d-25.34320378656389!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjXCsDIwJzM1LjUiUyA1N8KwMzAnMzguMCJX!5e0!3m2!1ses!2spy!4v1743518779941!5m2!1ses!2spy" width="100%" height="350" frameborder="0" style="border:0" allowfullscreen></iframe>
     </section>

     <!-- FOOTER -->
     <footer data-stellar-background-ratio="5">
          <div class="container">
               <div class="row">
                    <div class="col-md-4 col-sm-4">
                         <div class="footer-thumb">
                              <h4 class="wow fadeInUp" data-wow-delay="0.4s">Datos de contacto</h4>
                              <p>Todos los derechos reservados 2021-<?php echo date('Y'); ?></p>
                              <div class="contact-info">
                                   <p><i class="fa fa-phone"></i> +1 (849) 856 4014</p>
                                   <p><i class="fa fa-envelope-o"></i> <a href="#">perfect-teeth00@hotmail.com</a></p>
                              </div>
                         </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                         <div class="footer-thumb">
                              <h4 class="wow fadeInUp" data-wow-delay="0.4s">Últimas noticias</h4>
                              <div class="latest-stories">
                                   <div class="stories-image">
                                        <a href="https://www.odontologos.mx/odontologos/noticias/3508/manejo-del-blanqueamiento-dental-no-vital" target="_blank">
                                             <img src="src/img/blanqueamiento.jpg" class="img-responsive" alt="">
                                        </a>
                                   </div>
                                   <div class="stories-info">
                                        <a href="https://www.odontologos.mx/odontologos/noticias/3508/manejo-del-blanqueamiento-dental-no-vital" target="_blank">
                                             <h5>Blanqueamiento dental no vital</h5>
                                        </a>
                                        <span>Mayo 24, 2021</span>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                         <div class="footer-thumb">
                              <div class="opening-hours">
                                   <h4 class="wow fadeInUp" data-wow-delay="0.4s">Horario de apertura</h4>
                                   <p>Lunes - Viernes <span>08:00 AM - 7:00 PM</span></p>
                                   <p>Sábado <span>Cerrado</span></p>
                                   <p>Domingo <span>Cerrado</span></p>
                              </div>
                              <ul class="social-icon">
                                   <li><a href="https://www.facebook.com/Perfect-Teeth-111123294536780" target="_blank" class="fa fa-facebook-square"></a></li>
                              </ul>
                         </div>
                    </div>
               </div>
          </div>
     </footer>

     <!-- SCRIPTS -->
     <script src="src/js/bootstrap.min.js"></script>
     <script src="src/js/jquery.sticky.js"></script>
     <script src="src/js/jquery.stellar.min.js"></script>
     <script src="src/js/wow.min.js"></script>
     <script src="src/js/smoothscroll.js"></script>
     <script src="src/js/owl.carousel.min.js"></script>
     <script src="src/js/custom.js"></script>

     <script>
     $(document).ready(function() {
          // ===== VALIDACIÓN DE FECHA: BLOQUEAR FINES DE SEMANA =====
          $('#fecha_cita').on('change', function() {
               const fecha = new Date(this.value + 'T00:00:00');
               const day = fecha.getUTCDay();
               
               if (day === 0 || day === 6) {
                    alert('⚠️ No se permiten citas los fines de semana. Por favor selecciona un día de Lunes a Viernes.');
                    $(this).val('');
                    $('#horarios-container').html('<div class="loading-indicator"><i class="fa fa-exclamation-triangle"></i><p>Selecciona una fecha válida</p></div>');
               }
          });

          // ===== SISTEMA DE HORARIOS EN TIEMPO REAL =====
          function cargarHorarios() {
               const doctor = $("#dentistas").val();
               const fecha = $("#fecha_cita").val();
               const contenedor = $("#horarios-container");
               
               // Limpiar horario seleccionado
               $("#hora").val('');

               if (!doctor || !fecha) {
                    contenedor.html('<div class="loading-indicator"><i class="fa fa-info-circle"></i><p>Selecciona un doctor y una fecha para ver los horarios disponibles</p></div>');
                    return;
               }

               // Mostrar indicador de carga
               contenedor.html('<div class="loading-indicator"><i class="fa fa-spinner fa-spin"></i><p>Cargando horarios disponibles...</p></div>');

               // Petición AJAX para obtener horarios
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

                         // Crear botones de horarios
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

          // Cargar horarios al cambiar doctor o fecha
          $("#fecha_cita, #dentistas").on("change", cargarHorarios);

          // Validación antes de enviar el formulario
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