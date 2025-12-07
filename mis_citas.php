<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (isset($_SESSION['id_paciente'])) {
     $vUsuario = $_SESSION['id_paciente'];
     $row = consultarPaciente($link, $vUsuario);
} else {
     $_SESSION['MensajeTexto'] = "Error: acceso al sistema no registrado.";
     $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
     header("Location: ./index.php");
     exit();
}

/* ============================================================
   Consulta de Mis Citas
   ============================================================ */
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

/* ====== Variables del sidebar ====== */
$SIDEBAR_ACTIVE = 'citas';
$PATIENT_NAME = htmlspecialchars($row['nombre'].' '.$row['apellido']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth – Mis Citas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="./src/img/logo.png" type="image/png" />
  
  <!-- Bootstrap -->
  <link rel="stylesheet" href="src/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="src/css/font-awesome.min.css">
  
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

    /* ===== Sidebar fijo SIEMPRE visible ===== */
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

    /* ===== Main empujado por sidebar ===== */
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
      transition:all 0.3s ease;
    }
    .card:hover{
      transform:translateY(-3px);
      box-shadow:0 10px 25px rgba(15,23,42,.12);
    }

    .card-header-custom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem;
      border-bottom: 2px solid #f0f0f0;
    }
    
    .card-header-custom h3 {
      margin: 0;
      font-size: 1.5rem;
      color: #333;
      font-weight: 700;
    }

    /* ===== Timeline de citas ===== */
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

    /* ===== Badges ===== */
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

    /* ===== Botones ===== */
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
      .d-md-none{ display:none !important; }
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
          <i class="fa fa-calendar-check-o" style="color:#0d6efd;"></i>
          <span style="font-weight:600;">Mis Citas</span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <a href="agendar_cita.php" class="btn btn-sm btn-gradient" style="padding:8px 20px; font-size:0.9rem;">
            <i class="fa fa-plus"></i> Nueva Cita
          </a>
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

        <!-- Tarjeta de Citas -->
        <div class="card">
          <div class="card-header-custom">
            <h3><i class="fa fa-calendar"></i> Historial de Citas</h3>
          </div>

          <div style="padding: 1.5rem;">
            <?php if (empty($misCitas)): ?>
              <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fa fa-calendar-times-o" style="font-size: 4rem; margin-bottom: 20px;"></i>
                <h4>No tienes citas registradas</h4>
                <p>¡Agenda tu primera cita para comenzar tu tratamiento!</p>
                <a href="agendar_cita.php" class="btn btn-gradient">
                  <i class="fa fa-calendar-plus-o"></i> Agendar Cita
                </a>
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
        </div>

      </div>
    </div>
  </main>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="src/js/bootstrap.min.js"></script>
</body>
</html>