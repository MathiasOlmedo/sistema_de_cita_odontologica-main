<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* ===== Validación Superadmin ===== */
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'SuperAdmin') {
    $_SESSION['MensajeTexto'] = "Acceso no autorizado.";
    $_SESSION['MensajeTipo']  = "p-3 mb-2 bg-danger text-white";
    header("Location: /sistema_de_cita_odontologica-main/index.php");
    exit;
}

$usuario = $_SESSION['usuario'] ?? $_SESSION['nombre'] ?? 'Super Admin';

/* ===== KPIs Principales ===== */
// Total de pacientes
$total_pacientes = $link->query("SELECT COUNT(*) AS c FROM pacientes")->fetch_assoc()['c'] ?? 0;

// Total de doctores
$total_doctores = $link->query("SELECT COUNT(*) AS c FROM doctor")->fetch_assoc()['c'] ?? 0;

// Total de citas
$total_citas = $link->query("SELECT COUNT(*) AS c FROM citas")->fetch_assoc()['c'] ?? 0;

// Citas realizadas
$citas_realizadas = $link->query("SELECT COUNT(*) AS c FROM citas WHERE estado = 'A'")->fetch_assoc()['c'] ?? 0;

// Citas pendientes
$citas_pendientes = $link->query("SELECT COUNT(*) AS c FROM citas WHERE estado = 'I'")->fetch_assoc()['c'] ?? 0;

// Total de presupuestos
$total_presupuestos = $link->query("SELECT COUNT(*) AS c FROM presupuesto")->fetch_assoc()['c'] ?? 0;

// Monto total de presupuestos
$monto_presupuestos = $link->query("SELECT COALESCE(SUM(total), 0) AS suma FROM presupuesto")->fetch_assoc()['suma'] ?? 0;

// Monto total pagado
$monto_pagado = $link->query("SELECT COALESCE(SUM(monto), 0) AS suma FROM pagos")->fetch_assoc()['suma'] ?? 0;

/* ===== Gráfico 1: Citas por mes (año actual) ===== */
$sqlCitasMes = "
  SELECT DATE_FORMAT(fecha_cita, '%Y-%m') AS mes, 
         COUNT(*) AS total
  FROM citas
  WHERE YEAR(fecha_cita) = YEAR(CURDATE())
  GROUP BY mes
  ORDER BY mes";
$resCitasMes = $link->query($sqlCitasMes);
$labels_citas_mes = []; 
$data_citas_mes = [];
while ($r = $resCitasMes->fetch_assoc()) {
  $labels_citas_mes[] = $r['mes'];
  $data_citas_mes[]   = (int)$r['total'];
}

/* ===== Gráfico 2: Distribución de estados de citas ===== */
$sqlEstados = "
  SELECT 
    CASE 
      WHEN estado = 'A' THEN 'Realizadas'
      WHEN estado = 'I' THEN 'Pendientes'
      ELSE 'Otros'
    END AS estado_nombre,
    COUNT(*) AS total
  FROM citas
  GROUP BY estado";
$resEstados = $link->query($sqlEstados);
$labels_estados = []; 
$data_estados = [];
while ($r = $resEstados->fetch_assoc()) {
  $labels_estados[] = $r['estado_nombre'];
  $data_estados[]   = (int)$r['total'];
}

/* ===== Gráfico 3: Top 10 tratamientos más comunes ===== */
$sqlTratamientos = "
  SELECT procedimiento, COUNT(*) AS veces
  FROM presupuesto_detalle
  GROUP BY procedimiento
  ORDER BY veces DESC
  LIMIT 10";
$resTratamientos = $link->query($sqlTratamientos);
$labels_tratamientos = []; 
$data_tratamientos = [];
while ($r = $resTratamientos->fetch_assoc()) {
  $labels_tratamientos[] = $r['procedimiento'] ?: 'Sin nombre';
  $data_tratamientos[]   = (int)$r['veces'];
}

/* ===== Gráfico 4: Doctores con más citas ===== */
$sqlDoctores = "
  SELECT CONCAT(d.nombreD, ' ', d.apellido) AS doctor, 
         COUNT(*) AS citas
  FROM citas c
  JOIN doctor d ON d.id_doctor = c.id_doctor
  GROUP BY c.id_doctor
  ORDER BY citas DESC
  LIMIT 10";
$resDoctores = $link->query($sqlDoctores);
$labels_doctores = []; 
$data_doctores_citas = [];
while ($r = $resDoctores->fetch_assoc()) {
  $labels_doctores[] = $r['doctor'];
  $data_doctores_citas[] = (int)$r['citas'];
}

/* ===== Gráfico 5: Ingresos vs Pagos por mes ===== */
$sqlIngresos = "
  SELECT DATE_FORMAT(p.fecha, '%Y-%m') AS mes,
         COALESCE(SUM(p.total), 0) AS presupuestado,
         COALESCE(SUM(pg.monto), 0) AS pagado
  FROM presupuesto p
  LEFT JOIN pagos pg ON pg.id_presupuesto = p.id_presupuesto
  WHERE YEAR(p.fecha) = YEAR(CURDATE())
  GROUP BY mes
  ORDER BY mes";
$resIngresos = $link->query($sqlIngresos);
$labels_ingresos = []; 
$data_presupuestado = []; 
$data_pagado_mes = [];
while ($r = $resIngresos->fetch_assoc()) {
  $labels_ingresos[] = $r['mes'];
  $data_presupuestado[] = (float)$r['presupuestado'];
  $data_pagado_mes[] = (float)$r['pagado'];
}

/* ===== Gráfico 6: Estados de presupuestos ===== */
$sqlPresupEstados = "
  SELECT estado, COUNT(*) AS total
  FROM presupuesto
  GROUP BY estado";
$resPresupEstados = $link->query($sqlPresupEstados);
$labels_presup = []; 
$data_presup = [];
while ($r = $resPresupEstados->fetch_assoc()) {
  $labels_presup[] = ucfirst($r['estado']);
  $data_presup[] = (int)$r['total'];
}

date_default_timezone_set("America/Asuncion");
$fecha = date("d/m/Y");
$hora  = date("H:i");

$AVATAR_IMG = "/sistema_de_cita_odontologica-main/src/img/admin_user.png";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes Globales - Perfect Teeth</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="./src/img/logo.png" type="image/png" />

  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    :root{
      --brand:#0d6efd;
      --brand-100:#e7f1ff;
      --surface:#f8f9fa;
      --text:#212529;
      --sidebar-w:260px;
      --maxw:1400px;
      --radius:12px;
    }
    *{ box-sizing:border-box; }
    body{
      margin:0;
      background:var(--surface);
      color:var(--text);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Sidebar */
    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff;
      border-right:0;
      box-shadow:0 0 20px rgba(0,0,0,0.08);
      padding:1.25rem 1rem;
      overflow-y:auto;
      z-index:1030;
    }
    .brand{
      display:flex; align-items:center; gap:.75rem;
      padding:.5rem .75rem; border-radius:.75rem;
    }
    .brand-title{ 
      margin:0; font-weight:700; 
      color:var(--brand); font-size:1.05rem; 
    }
    .profile{ text-align:center; margin:1rem 0 1.25rem; }
    .profile img{ width:96px; height:96px; object-fit:cover; }
    .profile .name{ margin:.6rem 0 .1rem; font-weight:600; }
    .nav-menu .nav-link{
      border-radius:.6rem; color:#495057;
      display:flex; align-items:center; gap:.6rem;
      padding:.6rem .75rem; text-decoration:none;
      transition: all 0.2s ease;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100);
      color:var(--brand);
      font-weight:600;
    }

    /* Main */
    .main{
      margin-left:var(--sidebar-w);
      min-height:100vh;
      display:flex; flex-direction:column;
    }
    .container-max{
      width:100%; max-width:var(--maxw);
      margin:0 auto; padding:0 1.5rem;
    }
    .topbar{
      background:#fff;
      border-bottom:1px solid rgba(0,0,0,.06);
      padding:.75rem 0;
      position:sticky; top:0; z-index:10;
      box-shadow:0 2px 4px rgba(0,0,0,0.04);
    }
    .content{ padding:1.5rem 0 2rem; }

    /* KPI Cards */
    .kpi-card{
      background:#fff;
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 4px 12px rgba(15,23,42,.08);
      padding:1.5rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover{
      transform: translateY(-4px);
      box-shadow:0 8px 24px rgba(15,23,42,.12);
    }
    .kpi-icon{
      width:48px; height:48px;
      border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.5rem;
    }
    .kpi-value{
      font-size:2rem;
      font-weight:800;
      line-height:1;
      margin:.5rem 0;
    }
    .kpi-label{
      color:#6c757d;
      font-size:.9rem;
      margin:0;
    }

    /* Chart Cards */
    .chart-card{
      background:#fff;
      border-radius:var(--radius);
      border:1px solid rgba(0,0,0,.06);
      box-shadow:0 4px 12px rgba(15,23,42,.08);
      padding:1.5rem;
      height:100%;
    }
    .chart-title{
      font-size:1.1rem;
      font-weight:700;
      color:var(--brand);
      margin-bottom:1rem;
      display:flex;
      align-items:center;
      gap:.5rem;
    }
    .chart-container{
      position:relative;
      height:320px;
    }

    /* Colors */
    .bg-primary-soft{ background:#e7f1ff; color:#0d6efd; }
    .bg-success-soft{ background:#d1f4e0; color:#198754; }
    .bg-warning-soft{ background:#fff3cd; color:#ffc107; }
    .bg-danger-soft{ background:#f8d7da; color:#dc3545; }
    .bg-info-soft{ background:#cfe2ff; color:#0dcaf0; }
    .bg-purple-soft{ background:#e2d9f3; color:#6f42c1; }

    @media (max-width:992px){
      :root{ --sidebar-w:240px; }
      .sidebar{ width:var(--sidebar-w); }
      .main{ margin-left:var(--sidebar-w); }
      .chart-container{ height:280px; }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:220px; }
      .sidebar{ width:var(--sidebar-w); }
      .main{ margin-left:var(--sidebar-w); }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="brand mb-2">
    <img src="/sistema_de_cita_odontologica-main/src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
    <h1 class="brand-title">Perfect Teeth</h1>
  </div>

  <div class="profile">
    <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle border" alt="Perfil">
    <div class="name"><?php echo htmlspecialchars($usuario); ?></div>
    <div class="text-muted small">Panel Super Admin</div>
  </div>

  <nav class="nav-menu">
    <a class="nav-link" href="superadmin_dashboard.php">
      <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>
    <a class="nav-link" href="gestionar_pacientes.php">
      <i class="bi bi-people"></i><span>Gestionar Pacientes</span>
    </a>
    <a class="nav-link" href="gestionar_dentistas.php">
      <i class="bi bi-person-badge"></i><span>Gestionar Dentistas</span>
    </a>
    <a class="nav-link" href="gestionar_citas.php">
      <i class="bi bi-calendar-check"></i><span>Gestionar Citas</span>
    </a>
    <a class="nav-link" href="gestionar_usuarios.php">
      <i class="bi bi-person-gear"></i><span>Gestionar Usuarios</span>
    </a>
    <a class="nav-link active" href="reportes.php">
      <i class="bi bi-graph-up"></i><span>Reportes</span>
    </a>
    <a class="nav-link text-danger" href="php/cerrar.php">
      <i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>
    </a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <header class="topbar">
    <div class="container-max d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-graph-up text-primary fs-5"></i>
        <span class="fw-semibold">Reportes y Estadísticas</span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted small d-none d-sm-inline"><?php echo $fecha . " • " . $hora; ?></span>
        <span class="small"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($usuario); ?></span>
      </div>
    </div>
  </header>

  <main class="content">
    <div class="container-max">

      <!-- KPIs -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-primary-soft">
                <i class="bi bi-people"></i>
              </div>
              <div>
                <div class="kpi-value text-primary"><?php echo number_format($total_pacientes); ?></div>
                <p class="kpi-label">Total Pacientes</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-success-soft">
                <i class="bi bi-person-badge"></i>
              </div>
              <div>
                <div class="kpi-value text-success"><?php echo number_format($total_doctores); ?></div>
                <p class="kpi-label">Total Doctores</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-warning-soft">
                <i class="bi bi-calendar-check"></i>
              </div>
              <div>
                <div class="kpi-value text-warning"><?php echo number_format($total_citas); ?></div>
                <p class="kpi-label">Total Citas</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-info-soft">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <div>
                <div class="kpi-value text-info"><?php echo number_format($total_presupuestos); ?></div>
                <p class="kpi-label">Presupuestos</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Segunda fila de KPIs -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-success-soft">
                <i class="bi bi-check-circle"></i>
              </div>
              <div>
                <div class="kpi-value text-success"><?php echo number_format($citas_realizadas); ?></div>
                <p class="kpi-label">Citas Realizadas</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-danger-soft">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <div>
                <div class="kpi-value text-danger"><?php echo number_format($citas_pendientes); ?></div>
                <p class="kpi-label">Citas Pendientes</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="kpi-card">
            <div class="d-flex align-items-center gap-3">
              <div class="kpi-icon bg-purple-soft">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div>
                <div class="kpi-value" style="color:#6f42c1; font-size:1.5rem;">
                  $<?php echo number_format($monto_pagado, 0, ',', '.'); ?>
                </div>
                <p class="kpi-label">Total Pagado</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos -->
      <div class="row g-4 mb-4">
        <!-- Citas por mes -->
        <div class="col-12 col-lg-6">
          <div class="chart-card">
            <h3 class="chart-title">
              <i class="bi bi-calendar-event"></i> Citas por Mes (<?php echo date('Y'); ?>)
            </h3>
            <div class="chart-container">
              <canvas id="chartCitasMes"></canvas>
            </div>
          </div>
        </div>

        <!-- Estados de citas -->
        <div class="col-12 col-lg-6">
          <div class="chart-card">
            <h3 class="chart-title">
              <i class="bi bi-pie-chart"></i> Distribución de Estados de Citas
            </h3>
            <div class="chart-container">
              <canvas id="chartEstados"></canvas>
            </div>
          </div>
        </div>

        <!-- Tratamientos más comunes -->
        <div class="col-12 col-lg-6">
          <div class="chart-card">
            <h3 class="chart-title">
              <i class="bi bi-award"></i> Top 10 Tratamientos Más Comunes
            </h3>
            <div class="chart-container">
              <canvas id="chartTratamientos"></canvas>
            </div>
          </div>
        </div>

        <!-- Doctores con más citas -->
        <div class="col-12 col-lg-6">
          <div class="chart-card">
            <h3 class="chart-title">
              <i class="bi bi-star"></i> Doctores con Más Citas
            </h3>
            <div class="chart-container">
              <canvas id="chartDoctores"></canvas>
            </div>
          </div>
        </div>

        <!-- Ingresos vs Pagos -->
        <div class="col-12">
          <div class="chart-card">
            <h3 class="chart-title">
              <i class="bi bi-graph-up-arrow"></i> Ingresos Presupuestados vs Pagos Recibidos (<?php echo date('Y'); ?>)
            </h3>
            <div class="chart-container">
              <canvas id="chartIngresos"></canvas>
            </div>
          </div>
        </div>

        <!-- Estados de presupuestos -->
        <div class="col-12 col-lg-6">
          <div class="chart-card">
            <h3 class="chart-title">
              <i class="bi bi-file-earmark-check"></i> Estados de Presupuestos
            </h3>
            <div class="chart-container">
              <canvas id="chartPresupEstados"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-end text-muted small py-3">
        Sistema de Citas Odontológicas © <?php echo date("Y"); ?>
      </div>
    </div>
  </main>
</div>

<script>
// Configuración global de Chart.js
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.plugins.legend.display = true;

// 1️⃣ Citas por mes
const ctxCitasMes = document.getElementById('chartCitasMes').getContext('2d');
new Chart(ctxCitasMes, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($labels_citas_mes); ?>,
    datasets: [{
      label: 'Citas',
      data: <?php echo json_encode($data_citas_mes); ?>,
      borderColor: '#0d6efd',
      backgroundColor: 'rgba(13, 110, 253, 0.1)',
      borderWidth: 3,
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 } }
    },
    plugins: {
      legend: { display: false }
    }
  }
});

// 2️⃣ Estados de citas
const ctxEstados = document.getElementById('chartEstados').getContext('2d');
new Chart(ctxEstados, {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($labels_estados); ?>,
    datasets: [{
      data: <?php echo json_encode($data_estados); ?>,
      backgroundColor: ['#198754', '#ffc107', '#6c757d'],
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});

// 3️⃣ Tratamientos más comunes
const ctxTratamientos = document.getElementById('chartTratamientos').getContext('2d');
new Chart(ctxTratamientos, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels_tratamientos); ?>,
    datasets: [{
      label: 'Cantidad',
      data: <?php echo json_encode($data_tratamientos); ?>,
      backgroundColor: '#0dcaf0',
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    scales: {
      x: { beginAtZero: true, ticks: { precision: 0 } }
    },
    plugins: {
      legend: { display: false }
    }
  }
});

// 4️⃣ Doctores con más citas
const ctxDoctores = document.getElementById('chartDoctores').getContext('2d');
new Chart(ctxDoctores, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels_doctores); ?>,
    datasets: [{
      label: 'Citas',
      data: <?php echo json_encode($data_doctores_citas); ?>,
      backgroundColor: '#198754',
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 } }
    },
    plugins: {
      legend: { display: false }
    }
  }
});

// 5️⃣ Ingresos vs Pagos
const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
new Chart(ctxIngresos, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels_ingresos); ?>,
    datasets: [
      {
        label: 'Presupuestado',
        data: <?php echo json_encode($data_presupuestado); ?>,
        backgroundColor: '#0d6efd',
        borderRadius: 6
      },
      {
        label: 'Pagado',
        data: <?php echo json_encode($data_pagado_mes); ?>,
        backgroundColor: '#198754',
        borderRadius: 6
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true }
    },
    plugins: {
      legend: { position: 'top' }
    }
  }
});

// 6️⃣ Estados de presupuestos
const ctxPresupEstados = document.getElementById('chartPresupEstados').getContext('2d');
new Chart(ctxPresupEstados, {
  type: 'pie',
  data: {
    labels: <?php echo json_encode($labels_presup); ?>,
    datasets: [{
      data: <?php echo json_encode($data_presup); ?>,
      backgroundColor: ['#6c757d', '#0dcaf0', '#0d6efd', '#dc3545'],
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>