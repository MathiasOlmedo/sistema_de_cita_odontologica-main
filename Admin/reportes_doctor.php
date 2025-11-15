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
      background:var(--surface);
      color:var(--text);
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
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
      border-radius:var(--radius);
      color:var(--text);
      display:flex; align-items:center; gap:.65rem;
      padding:.7rem .75rem;
      text-decoration:none;
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
      background:var(--brand-100);
      color:var(--brand);
      text-decoration:none;
      font-weight:600;
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
    .main{ 
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
    .content{ padding:1.5rem 0 2rem; }
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
      border-bottom:1px solid rgba(0,0,0,.06); 
      border-top-left-radius:var(--radius); 
      border-top-right-radius:var(--radius);
      padding:1.25rem 1.75rem !important;
    }
    .section-title{ 
      margin:0; 
      font-size:1.25rem; 
      font-weight:700; 
      color:var(--brand); 
      letter-spacing:.2px;
      transition:var(--transition);
    }
    .card:hover .section-title{ color:var(--brand-hover); }
    .kpi{ 
      display:flex; 
      align-items:center; 
      gap:.75rem;
      padding:1rem;
      background:#fff;
      border-radius:var(--radius);
      transition:var(--transition);
      border:1px solid rgba(0,0,0,.05);
    }
    .kpi:hover{
      transform:translateY(-2px);
      box-shadow:0 4px 12px rgba(13,110,253,.15);
    }
    .kpi .icon{ 
      width:48px; 
      height:48px; 
      display:inline-flex; 
      align-items:center; 
      justify-content:center; 
      border-radius:var(--radius); 
      background:var(--brand-100); 
      color:var(--brand);
      transition:var(--transition);
    }
    .kpi:hover .icon{
      background:var(--brand);
      color:#fff;
      transform:rotate(5deg) scale(1.1);
    }
    .kpi .value{ font-size:1.5rem; font-weight:800; line-height:1; color:var(--text); }
    .kpi .label{ margin:0; color:var(--text-muted); font-size:0.85rem; }
    @media (max-width:992px){
      :root{ --sidebar-w:220px; }
      .sidebar{ padding:0.75rem 0.5rem; }
      .container-max{ padding:0 1rem; }
    }
    @media (max-width:575.98px){
      :root{ --sidebar-w:200px; }
      .container-max{ padding:0 0.75rem; }
      .content{ padding:1rem 0 1.5rem; }
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
  </aside>
  <!-- /Sidebar -->

  <!-- Main -->
  <div class="main">
    <header class="topbar">
      <div class="container-max d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-chart-line text-primary"></i>
          <span class="text-muted">Reportes</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted">Usuario: </span>
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

        <!-- KPIs -->
        <div class="row g-3 mb-3">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi">
                      <div class="icon"><i class="far fa-calendar-check"></i></div>
                      <div>
                        <div class="value"><?php echo (int)$total_citas; ?></div>
                        <p class="label">Total de citas</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi">
                      <div class="icon"><i class="fas fa-check-circle"></i></div>
                      <div>
                        <div class="value"><?php echo (int)$citas_realizadas; ?></div>
                        <p class="label">Citas realizadas</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi">
                      <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                      <div>
                        <div class="value"><?php echo (int)$citas_pendientes; ?></div>
                        <p class="label">Citas pendientes</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi">
                      <div class="icon"><i class="fas fa-tooth"></i></div>
                      <div>
                        <div class="value"><?php echo (int)$tratamientos_count; ?></div>
                        <p class="label">Tratamientos registrados</p>
                      </div>
                    </div>
                  </div>
                </div> <!-- /row -->
              </div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="row g-3">
          <!-- Top tratamientos -->
          <div class="col-12 col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="section-title mb-0">Top tratamientos</h2>
                <small class="text-muted">Cantidad y monto (Top 10)</small>
              </div>
              <div class="card-body">
                <canvas id="chartTop"></canvas>
                <?php if (empty($labels_top)): ?>
                  <p class="text-center text-muted mt-3 mb-0">Sin datos suficientes.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Citas por mes -->
          <div class="col-12 col-xl-6">
            <div class="card h-100">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="section-title mb-0">Citas por mes (año actual)</h2>
                <small class="text-muted">Solo tus citas</small>
              </div>
              <div class="card-body">
                <canvas id="chartMes"></canvas>
                <?php if (empty($labels_mes)): ?>
                  <p class="text-center text-muted mt-3 mb-0">Sin datos para el año actual.</p>
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

  // Chart Top tratamientos (barras dobles)
  if (labelsTop.length){
    const ctxTop = document.getElementById('chartTop').getContext('2d');
    new Chart(ctxTop, {
      type: 'bar',
      data: {
        labels: labelsTop,
        datasets: [
          { label: 'Cantidad', data: dataVeces },
          { label: 'Monto (USD)', data: dataMonto, yAxisID: 'y1' }
        ]
      },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        stacked: false,
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Cantidad' } },
          y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'USD' } }
        }
      }
    });
  }

  // Chart Citas por mes (línea)
  if (labelsMes.length){
    const ctxMes = document.getElementById('chartMes').getContext('2d');
    new Chart(ctxMes, {
      type: 'line',
      data: {
        labels: labelsMes,
        datasets: [{ label: 'Citas', data: dataMes, tension: .3, fill: false }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });
  }
</script>
</body>
</html>
