<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== Validación Superadmin ===== */
if (!isset($_SESSION['id_superadmin'])) {
  $_SESSION['MensajeTexto'] = "Acceso no autorizado.";
  $_SESSION['MensajeTipo']  = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

/* ===== Estadísticas generales ===== */
$total_pacientes = $link->query("SELECT COUNT(*) AS c FROM pacientes")->fetch_assoc()['c'] ?? 0;
$total_doctores  = $link->query("SELECT COUNT(*) AS c FROM doctor")->fetch_assoc()['c'] ?? 0;
$total_citas     = $link->query("SELECT COUNT(*) AS c FROM citas")->fetch_assoc()['c'] ?? 0;
$total_presupuestos = $link->query("SELECT COUNT(*) AS c FROM presupuesto")->fetch_assoc()['c'] ?? 0;

/* ===== Gráfico 1: Pacientes atendidos por mes ===== */
$sqlPacMes = "
  SELECT DATE_FORMAT(fecha_cita, '%Y-%m') AS mes, COUNT(DISTINCT id_paciente) AS pacientes
  FROM citas
  WHERE estado = 'atendido' AND YEAR(fecha_cita) = YEAR(CURDATE())
  GROUP BY mes ORDER BY mes";
$resPacMes = $link->query($sqlPacMes);
$labels_mes = []; $data_pac = [];
while ($r = $resPacMes->fetch_assoc()) {
  $labels_mes[] = $r['mes'];
  $data_pac[]   = (int)$r['pacientes'];
}

/* ===== Gráfico 2: Tratamientos más comunes ===== */
$sqlTrata = "
  SELECT d.procedimiento, COUNT(*) AS veces
  FROM presupuesto_detalle d
  JOIN presupuesto p ON p.id_presupuesto = d.id_presupuesto
  GROUP BY d.procedimiento
  ORDER BY veces DESC LIMIT 10";
$resTrata = $link->query($sqlTrata);
$labels_trat = []; $data_trat = [];
while ($r = $resTrata->fetch_assoc()) {
  $labels_trat[] = $r['procedimiento'] ?: 'Sin nombre';
  $data_trat[]   = (int)$r['veces'];
}

/* ===== Gráfico 3: Doctores con más citas ===== */
$sqlDoc = "
  SELECT CONCAT(nombreD,' ',apellido) AS doctor, COUNT(*) AS citas
  FROM citas c
  JOIN doctor d ON d.id_doctor = c.id_doctor
  GROUP BY c.id_doctor
  ORDER BY citas DESC LIMIT 10";
$resDoc = $link->query($sqlDoc);
$labels_doc = []; $data_doc = [];
while ($r = $resDoc->fetch_assoc()) {
  $labels_doc[] = $r['doctor'];
  $data_doc[]   = (int)$r['citas'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reportes Globales — Perfect Teeth</title>
<link rel="icon" href="../src/img/logo.png" type="image/png" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root{
  --brand:#0d6efd; --brand-100:#e7f1ff;
  --surface:#f8f9fa; --text:#212529;
  --sidebar-w:260px; --maxw:1200px; --radius:12px;
}
body{background:var(--surface); color:var(--text); margin:0;}
.sidebar{
  position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh;
  background:#fff; padding:1.25rem 1rem; box-shadow:none;
}
.brand{display:flex; align-items:center; gap:.75rem;}
.brand-title{margin:0; font-weight:700; color:var(--brand);}
.profile{text-align:center; margin:1rem 0;}
.profile img{width:96px; height:96px; object-fit:cover;}
.nav-menu{display:flex; flex-direction:column; gap:.25rem;}
.nav-menu .nav-link{
  display:flex; align-items:center; gap:.6rem; border-radius:.6rem;
  padding:.6rem .75rem; color:#495057; text-decoration:none;
}
.nav-menu .nav-link:hover,
.nav-menu .nav-link.active{background:var(--brand-100); color:var(--brand);}
.main{margin-left:var(--sidebar-w); min-height:100vh; padding:1.5rem;}
.card{border-radius:var(--radius); border:1px solid rgba(0,0,0,.06); box-shadow:0 6px 16px rgba(15,23,42,.06);}
.section-title{font-weight:700; color:var(--brand);}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="brand mb-2">
    <img src="../src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
    <h1 class="brand-title">Perfect Teeth</h1>
  </div>
  <div class="profile">
    <img src="../src/img/admin_user.png" class="rounded-circle border" alt="Perfil">
    <div class="name">Superadmin</div>
    <div class="small text-muted">Panel Global</div>
  </div>
  <nav class="nav-menu">
    <a href="../gestionar_pacientes.php" class="nav-link"><i class="bi bi-people"></i> Pacientes</a>
    <a href="../gestionar_dentistas.php" class="nav-link"><i class="bi bi-person-badge"></i> Dentistas</a>
    <a href="../gestionar_citas.php" class="nav-link"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="../gestionar_usuarios.php" class="nav-link"><i class="bi bi-person-gear"></i> Usuarios</a>
    <a href="reportes_globales.php" class="nav-link active"><i class="bi bi-graph-up"></i> Reportes Globales</a>
    <a href="../php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="container-fluid">
    <h3 class="text-primary mb-3"><i class="bi bi-graph-up"></i> Reportes Globales</h3>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Pacientes</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_pacientes; ?></h3>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Doctores</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_doctores; ?></h3>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Citas</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_citas; ?></h3>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Presupuestos</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_presupuestos; ?></h3>
        </div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card p-3">
          <h6 class="section-title mb-2">Pacientes atendidos por mes</h6>
          <canvas id="chartPacMes"></canvas>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card p-3">
          <h6 class="section-title mb-2">Tratamientos más comunes</h6>
          <canvas id="chartTrata"></canvas>
        </div>
      </div>
      <div class="col-lg-12">
        <div class="card p-3">
          <h6 class="section-title mb-2">Doctores con más citas</h6>
          <canvas id="chartDoc"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script>
const labelsMes  = <?php echo json_encode($labels_mes); ?>;
const dataPac    = <?php echo json_encode($data_pac); ?>;
const labelsTrat = <?php echo json_encode($labels_trat); ?>;
const dataTrat   = <?php echo json_encode($data_trat); ?>;
const labelsDoc  = <?php echo json_encode($labels_doc); ?>;
const dataDoc    = <?php echo json_encode($data_doc); ?>;

// 1️⃣ Pacientes por mes
if(labelsMes.length){
  new Chart(document.getElementById('chartPacMes'), {
    type: 'line',
    data: { labels: labelsMes, datasets: [{ label:'Pacientes atendidos', data:dataPac, borderColor:'#0d6efd', fill:false, tension:.3 }] },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
  });
}

// 2️⃣ Tratamientos más comunes
if(labelsTrat.length){
  new Chart(document.getElementById('chartTrata'), {
    type: 'bar',
    data: { labels: labelsTrat, datasets:[{ label:'Cantidad', data:dataTrat, backgroundColor:'#0d6efd' }] },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
  });
}

// 3️⃣ Doctores con más citas
if(labelsDoc.length){
  new Chart(document.getElementById('chartDoc'), {
    type: 'bar',
    data: { labels: labelsDoc, datasets:[{ label:'Citas realizadas', data:dataDoc, backgroundColor:'#198754' }] },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
  });
}
</script>
</body>
</html>
