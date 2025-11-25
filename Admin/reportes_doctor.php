<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* ====== Autenticación Doctor ====== */
if (!isset($_SESSION['id_doctor'])) {
  $_SESSION['MensajeTexto'] = "Error: acceso no autorizado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

$id_doctor = (int)$_SESSION['id_doctor'];
$doctor = consultarDoctor($link, $id_doctor);

$DOCTOR_NAME = trim(($doctor['nombreD'] ?? '').' '.($doctor['apellido'] ?? ''));
$DOCTOR_SEX  = $doctor['sexo'] ?? 'Masculino';
$AVATAR_IMG  = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';

/* ====== Totales de tarjetas ====== */
// Total de citas
$sqlTotal = $link->prepare("SELECT COUNT(*) AS c FROM citas WHERE id_doctor = ?");
$sqlTotal->bind_param('i', $id_doctor);
$sqlTotal->execute();
$total_citas = ($sqlTotal->get_result()->fetch_assoc()['c'] ?? 0);
$sqlTotal->close();

// Citas realizadas (en tu sistema, estado 'A' = Realizada)
$sqlRealizadas = $link->prepare("SELECT COUNT(*) AS c FROM citas WHERE id_doctor = ? AND estado = 'A'");
$sqlRealizadas->bind_param('i', $id_doctor);
$sqlRealizadas->execute();
$citas_realizadas = ($sqlRealizadas->get_result()->fetch_assoc()['c'] ?? 0);
$sqlRealizadas->close();

// Citas pendientes (asumimos != 'A' => pendiente, o explícitamente 'I')
$sqlPend = $link->prepare("SELECT COUNT(*) AS c FROM citas WHERE id_doctor = ? AND estado <> 'A'");
$sqlPend->bind_param('i', $id_doctor);
$sqlPend->execute();
$citas_pendientes = ($sqlPend->get_result()->fetch_assoc()['c'] ?? 0);
$sqlPend->close();

// Tratamientos realizados/presupuestados (conteo de filas de detalle del presupuesto del doctor)
$sqlTrx = $link->prepare("
  SELECT COUNT(*) AS c
  FROM presupuesto_detalle d
  JOIN presupuesto p ON p.id_presupuesto = d.id_presupuesto
  WHERE p.id_doctor = ?
");
$sqlTrx->bind_param('i', $id_doctor);
$sqlTrx->execute();
$tratamientos_count = ($sqlTrx->get_result()->fetch_assoc()['c'] ?? 0);
$sqlTrx->close();

// ✅ NUEVO: Total de ingresos generados
$sqlIngresos = $link->prepare("
  SELECT COALESCE(SUM(d.precio), 0) AS total_ingresos
  FROM presupuesto_detalle d
  JOIN presupuesto p ON p.id_presupuesto = d.id_presupuesto
  WHERE p.id_doctor = ?
");
$sqlIngresos->bind_param('i', $id_doctor);
$sqlIngresos->execute();
$total_ingresos = ($sqlIngresos->get_result()->fetch_assoc()['total_ingresos'] ?? 0);
$sqlIngresos->close();

// ✅ NUEVO: Pacientes únicos atendidos
$sqlPacientes = $link->prepare("
  SELECT COUNT(DISTINCT id_paciente) AS total_pacientes
  FROM citas
  WHERE id_doctor = ? AND estado = 'A'
");
$sqlPacientes->bind_param('i', $id_doctor);
$sqlPacientes->execute();
$total_pacientes = ($sqlPacientes->get_result()->fetch_assoc()['total_pacientes'] ?? 0);
$sqlPacientes->close();

/* ====== Gráfico 1: Top tratamientos (cantidad + monto) ====== */
$sqlTop = $link->prepare("
  SELECT d.procedimiento,
         COUNT(*) AS veces,
         SUM(d.precio) AS total_monto
  FROM presupuesto_detalle d
  JOIN presupuesto p ON p.id_presupuesto = d.id_presupuesto
  WHERE p.id_doctor = ?
  GROUP BY d.procedimiento
  ORDER BY veces DESC
  LIMIT 10
");
$sqlTop->bind_param('i', $id_doctor);
$sqlTop->execute();
$resTop = $sqlTop->get_result();
$labels_top = []; $data_veces = []; $data_monto = [];
while ($r = $resTop->fetch_assoc()) {
  $labels_top[] = $r['procedimiento'] ?: 'Sin nombre';
  $data_veces[] = (int)$r['veces'];
  $data_monto[] = (float)$r['total_monto'];
}
$sqlTop->close();

/* ====== Gráfico 2: Citas por mes (año actual) ====== */
$sqlMes = $link->prepare("
  SELECT DATE_FORMAT(fecha_cita, '%Y-%m') AS ym, COUNT(*) AS c
  FROM citas
  WHERE id_doctor = ? AND YEAR(fecha_cita) = YEAR(CURDATE())
  GROUP BY ym
  ORDER BY ym
");
$sqlMes->bind_param('i', $id_doctor);
$sqlMes->execute();
$resMes = $sqlMes->get_result();
$labels_mes = []; $data_citas_mes = [];
while ($r = $resMes->fetch_assoc()) {
  $labels_mes[] = $r['ym'];
  $data_citas_mes[] = (int)$r['c'];
}
$sqlMes->close();

/* ====== ✅ NUEVO: Gráfico 3 - Ingresos por mes (año actual) ====== */
$sqlIngresosMes = $link->prepare("
  SELECT DATE_FORMAT(p.fecha, '%Y-%m') AS mes,
         SUM(d.precio) AS ingresos
  FROM presupuesto_detalle d
  JOIN presupuesto p ON p.id_presupuesto = d.id_presupuesto
  WHERE p.id_doctor = ? 
    AND YEAR(p.fecha) = YEAR(CURDATE())
  GROUP BY mes
  ORDER BY mes
");

$sqlIngresosMes->bind_param('i', $id_doctor);
$sqlIngresosMes->execute();
$resIngresosMes = $sqlIngresosMes->get_result();
$labels_ingresos_mes = []; $data_ingresos_mes = [];
while ($r = $resIngresosMes->fetch_assoc()) {
  $labels_ingresos_mes[] = $r['mes'];
  $data_ingresos_mes[] = (float)$r['ingresos'];
}
$sqlIngresosMes->close();

/* ====== ✅ NUEVO: Gráfico 4 - Estado de citas (Pie Chart) ====== */
$sqlEstados = $link->prepare("
  SELECT 
    SUM(CASE WHEN estado = 'A' THEN 1 ELSE 0 END) AS atendidas,
    SUM(CASE WHEN estado = 'I' THEN 1 ELSE 0 END) AS pendientes,
    SUM(CASE WHEN estado NOT IN ('A', 'I') THEN 1 ELSE 0 END) AS otros
  FROM citas
  WHERE id_doctor = ?
");
$sqlEstados->bind_param('i', $id_doctor);
$sqlEstados->execute();
$estados = $sqlEstados->get_result()->fetch_assoc();
$sqlEstados->close();

/* ====== Sidebar active ====== */
$SIDEBAR_ACTIVE = 'reportes'; // citas | calendario | odontograma | presupuestos | reportes
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth — Reportes del Odontólogo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="icon" href="../src/img/logo.png" type="image/png" />
  <!-- Bootstrap / FA -->
  <link rel="stylesheet" href="../src/css/lib/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.min.css">
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
      --success:#28a745;
      --warning:#ffc107;
      --danger:#dc3545;
      --info:#17a2b8;
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
    }
    .brand{ display:flex; align-items:center; gap:.75rem; padding:.5rem .75rem; border-radius:.75rem; }
    .brand-title{ margin:0; font-weight:700; letter-spacing:.3px; color:var(--brand); font-size:1.05rem; }
    .profile{ text-align:center; margin:1rem 0 1.25rem; }
    .profile img{ width:96px; height:96px; object-fit:cover; }
    .profile .name{ margin:.75rem 0 .25rem; font-weight:600; }
    .nav-menu .nav-link{
      border-radius:.6rem; color:#495057;
      display:flex; align-items:center; gap:.6rem;
      padding:.6rem .75rem; text-decoration:none;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100); color:var(--brand);
      text-decoration:none; font-weight:600;
    }
    .main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
    .container-max{ width:100%; max-width:var(--maxw); margin:0 auto; padding:0 1.25rem; }
    .topbar{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); padding:.75rem 0; position:sticky; top:0; z-index:10; }
    .content{ padding:1.25rem 0 2rem; }
    .card{ border-radius:var(--radius); border:1px solid rgba(0,0,0,.06); box-shadow:0 6px 16px rgba(15,23,42,.06); }
    .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:var(--radius); border-top-right-radius:var(--radius); }
    .section-title{ margin:0; font-size:1.1rem; font-weight:700; color:var(--brand); letter-spacing:.2px; }
    .kpi{ display:flex; align-items:center; gap:.75rem; }
    .kpi .icon{ width:48px; height:48px; display:inline-flex; align-items:center; justify-content:center; border-radius:.75rem; font-size:1.25rem; }
    .kpi .value{ font-size:1.75rem; font-weight:800; line-height:1; margin-bottom:.25rem; }
    .kpi .label{ margin:0; color:#6c757d; font-size:.9rem; }
    .kpi.success .icon{ background:rgba(40,167,69,.1); color:var(--success); }
    .kpi.info .icon{ background:rgba(13,110,253,.1); color:var(--brand); }
    .kpi.warning .icon{ background:rgba(255,193,7,.1); color:var(--warning); }
    .kpi.danger .icon{ background:rgba(220,53,69,.1); color:var(--danger); }
    .chart-container{ position:relative; height:320px; }
    @media (max-width:992px){
      :root{ --sidebar-w:240px; }
      .sidebar{ width:var(--sidebar-w); }
      .main{ margin-left:var(--sidebar-w); }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:220px; }
      .sidebar{ width:var(--sidebar-w); }
      .main{ margin-left:var(--sidebar-w); }
    }
  </style>
</head>
<body>
<div class="app">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand mb-2">
      <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
      <h1 class="brand-title">Perfect Teeth</h1>
    </div>
    <div class="profile">
      <img src="<?php echo $AVATAR_IMG; ?>" class="rounded-circle border" alt="Perfil">
      <div class="name"><?php echo htmlspecialchars($DOCTOR_NAME ?: 'Odontólogo'); ?></div>
      <div class="text-muted small">Panel de odontólogo</div>
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
      <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='reportes'?'active':''); ?>" href="reportes_doctor.php">
        <i class="fas fa-chart-line"></i><span>Reportes</span>
      </a>
      <a class="nav-link" href="../php/cerrar.php">
        <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
      </a>
    </nav>
  </aside>
  <!-- /Sidebar -->

  <!-- Main -->
  <div class="main">
    <header class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-chart-line text-primary"></i>
          <span class="text-muted">Reportes y Estadísticas</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Sesión:</span>
          <strong><?php echo htmlspecialchars($doctor['correo_eletronico'] ?? ''); ?></strong>
        </div>
      </div>
    </header>

    <main class="content">
      <div class="container-max">
        <?php if (!empty($_SESSION['MensajeTexto'])): ?>
          <div class="alert <?php echo $_SESSION['MensajeTipo'] ?? 'alert-info'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['MensajeTexto']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
          </div>
          <?php $_SESSION['MensajeTexto'] = null; $_SESSION['MensajeTipo'] = null; ?>
        <?php endif; ?>

        <!-- ✅ KPIs MEJORADOS (6 tarjetas) -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100">
              <div class="card-body">
                <div class="kpi info">
                  <div class="icon"><i class="far fa-calendar-check"></i></div>
                  <div>
                    <div class="value"><?php echo (int)$total_citas; ?></div>
                    <p class="label">Total de citas</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100">
              <div class="card-body">
                <div class="kpi success">
                  <div class="icon"><i class="fas fa-check-circle"></i></div>
                  <div>
                    <div class="value"><?php echo (int)$citas_realizadas; ?></div>
                    <p class="label">Citas realizadas</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100">
              <div class="card-body">
                <div class="kpi warning">
                  <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                  <div>
                    <div class="value"><?php echo (int)$citas_pendientes; ?></div>
                    <p class="label">Citas pendientes</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100">
              <div class="card-body">
                <div class="kpi info">
                  <div class="icon"><i class="fas fa-tooth"></i></div>
                  <div>
                    <div class="value"><?php echo (int)$tratamientos_count; ?></div>
                    <p class="label">Tratamientos</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100">
              <div class="card-body">
                <div class="kpi success">
                  <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                  <div>
                    <div class="value">$<?php echo number_format($total_ingresos, 0, ',', '.'); ?></div>
                    <p class="label">Ingresos totales</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100">
              <div class="card-body">
                <div class="kpi info">
                  <div class="icon"><i class="fas fa-users"></i></div>
                  <div>
                    <div class="value"><?php echo (int)$total_pacientes; ?></div>
                    <p class="label">Pacientes únicos</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ✅ GRÁFICOS MEJORADOS -->
        <div class="row g-3 mb-3">
          <!-- Top tratamientos -->
          <div class="col-12 col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="section-title mb-0"><i class="fas fa-medal text-warning me-2"></i>Top 10 Tratamientos</h2>
                <small class="text-muted">Más realizados</small>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="chartTop"></canvas>
                </div>
                <?php if (empty($labels_top)): ?>
                  <p class="text-center text-muted mt-3 mb-0">Sin datos suficientes.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Estado de citas (Pie) -->
          <div class="col-12 col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="section-title mb-0"><i class="fas fa-chart-pie text-info me-2"></i>Estado de Citas</h2>
                <small class="text-muted">Distribución</small>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="chartEstados"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <!-- Citas por mes -->
          <div class="col-12 col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="section-title mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Citas por Mes</h2>
                <small class="text-muted">Año actual</small>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="chartMes"></canvas>
                </div>
                <?php if (empty($labels_mes)): ?>
                  <p class="text-center text-muted mt-3 mb-0">Sin datos para el año actual.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Ingresos por mes -->
          <div class="col-12 col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="section-title mb-0"><i class="fas fa-chart-area text-success me-2"></i>Ingresos por Mes</h2>
                <small class="text-muted">Año actual</small>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="chartIngresos"></canvas>
                </div>
                <?php if (empty($labels_ingresos_mes)): ?>
                  <p class="text-center text-muted mt-3 mb-0">Sin datos de ingresos para el año actual.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /container-max -->
    </main>
  </div>
</div>

<!-- JS -->
<script src="../src/js/jquery.js"></script>
<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  // Data desde PHP
  const labelsTop   = <?php echo json_encode($labels_top, JSON_UNESCAPED_UNICODE); ?>;
  const dataVeces   = <?php echo json_encode($data_veces, JSON_NUMERIC_CHECK); ?>;
  const dataMonto   = <?php echo json_encode($data_monto, JSON_NUMERIC_CHECK); ?>;

  const labelsMes   = <?php echo json_encode($labels_mes, JSON_UNESCAPED_UNICODE); ?>;
  const dataMes     = <?php echo json_encode($data_citas_mes, JSON_NUMERIC_CHECK); ?>;

  const labelsIngresosMes = <?php echo json_encode($labels_ingresos_mes, JSON_UNESCAPED_UNICODE); ?>;
  const dataIngresosMes   = <?php echo json_encode($data_ingresos_mes, JSON_NUMERIC_CHECK); ?>;

  const estadosData = {
    atendidas: <?php echo (int)($estados['atendidas'] ?? 0); ?>,
    pendientes: <?php echo (int)($estados['pendientes'] ?? 0); ?>,
    otros: <?php echo (int)($estados['otros'] ?? 0); ?>
  };

  // ✅ Chart Top tratamientos (barras horizontales mejoradas)
  if (labelsTop.length){
    const ctxTop = document.getElementById('chartTop').getContext('2d');
    new Chart(ctxTop, {
      type: 'bar',
      data: {
        labels: labelsTop,
        datasets: [{
          label: 'Cantidad de veces realizado',
          data: dataVeces,
          backgroundColor: 'rgba(13, 110, 253, 0.7)',
          borderColor: 'rgba(13, 110, 253, 1)',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.parsed.x + ' veces';
              }
            }
          }
        },
        scales: {
          x: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
      }
    });
  }

  // ✅ Chart Estado de citas (Pie mejorado)
  const ctxEstados = document.getElementById('chartEstados').getContext('2d');
  new Chart(ctxEstados, {
    type: 'doughnut',
    data: {
      labels: ['Atendidas', 'Pendientes', 'Otros'],
      datasets: [{
        data: [estadosData.atendidas, estadosData.pendientes, estadosData.otros],
        backgroundColor: [
          'rgba(40, 167, 69, 0.7)',
          'rgba(255, 193, 7, 0.7)',
          'rgba(108, 117, 125, 0.7)'
        ],
        borderColor: [
          'rgba(40, 167, 69, 1)',
          'rgba(255, 193, 7, 1)',
          'rgba(108, 117, 125, 1)'
        ],
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

  // ✅ Chart Citas por mes (línea mejorada)
  if (labelsMes.length){
    const ctxMes = document.getElementById('chartMes').getContext('2d');
    new Chart(ctxMes, {
      type: 'line',
      data: {
        labels: labelsMes,
        datasets: [{
          label: 'Número de citas',
          data: dataMes,
          borderColor: 'rgba(13, 110, 253, 1)',
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
      }
    });
  }

  // ✅ Chart Ingresos por mes (área mejorada)
  if (labelsIngresosMes.length){
    const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
    new Chart(ctxIngresos, {
      type: 'line',
      data: {
        labels: labelsIngresosMes,
        datasets: [{
          label: 'Ingresos ($)',
          data: dataIngresosMes,
          borderColor: 'rgba(40, 167, 69, 1)',
          backgroundColor: 'rgba(40, 167, 69, 0.2)',
          tension: 0.4,
          fill: true,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { 
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '$' + value.toLocaleString();
              }
            }
          }
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                return '$' + context.parsed.y.toLocaleString();
              }
            }
          }
        }
      }
    });
  }
</script>
</body>
</html>