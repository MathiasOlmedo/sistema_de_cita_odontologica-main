<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== Validación Superadmin Unificada ===== */
if (
    !isset($_SESSION['id_usuario']) ||
    $_SESSION['tipo'] !== 'SuperAdmin'
) {
    $_SESSION['MensajeTexto'] = "Acceso no autorizado.";
    $_SESSION['MensajeTipo']  = "alert alert-danger";
    header("Location: ./index.php");
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
  WHERE estado = 'A' AND YEAR(fecha_cita) = YEAR(CURDATE())
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
<!-- Este archivo está diseñado para ser cargado por AJAX en superadmin_dashboard.php -->
<div class="container-fluid">
    <h3 class="text-primary mb-3"><i class="bi bi-graph-up"></i> Reportes Globales</h3>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Pacientes Registrados</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_pacientes; ?></h3>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Doctores Activos</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_doctores; ?></h3>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Citas Totales</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_citas; ?></h3>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="card text-center p-3">
          <h6 class="text-muted">Presupuestos Generados</h6>
          <h3 class="fw-bold text-primary"><?php echo $total_presupuestos; ?></h3>
        </div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card p-3">
          <h6 class="text-primary mb-2">Pacientes atendidos por mes (Año actual)</h6>
          <canvas id="chartPacMes"></canvas>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card p-3">
          <h6 class="text-primary mb-2">Top 10 Tratamientos más comunes</h6>
          <canvas id="chartTrata"></canvas>
        </div>
      </div>
      <div class="col-lg-12">
        <div class="card p-3">
          <h6 class="text-primary mb-2">Top 10 Doctores con más citas</h6>
          <canvas id="chartDoc"></canvas>
        </div>
      </div>
    </div>
</div>

<!-- JS para inicializar los gráficos -->
<script>
(function() {
    const labelsMes  = <?php echo json_encode($labels_mes); ?>;
    const dataPac    = <?php echo json_encode($data_pac); ?>;
    const labelsTrat = <?php echo json_encode($labels_trat); ?>;
    const dataTrat   = <?php echo json_encode($data_trat); ?>;
    const labelsDoc  = <?php echo json_encode($labels_doc); ?>;
    const dataDoc    = <?php echo json_encode($data_doc); ?>;

    // 1️⃣ Pacientes por mes
    if(labelsMes.length && document.getElementById('chartPacMes')){
      new Chart(document.getElementById('chartPacMes'), {
        type: 'line',
        data: { labels: labelsMes, datasets: [{ label:'Pacientes atendidos', data:dataPac, borderColor:'#0d6efd', fill:false, tension:.3 }] },
        options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
      });
    }

    // 2️⃣ Tratamientos más comunes
    if(labelsTrat.length && document.getElementById('chartTrata')){
      new Chart(document.getElementById('chartTrata'), {
        type: 'bar',
        data: { labels: labelsTrat, datasets:[{ label:'Cantidad', data:dataTrat, backgroundColor:'#0d6efd' }] },
        options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
      });
    }

    // 3️⃣ Doctores con más citas
    if(labelsDoc.length && document.getElementById('chartDoc')){
      new Chart(document.getElementById('chartDoc'), {
        type: 'bar',
        data: { labels: labelsDoc, datasets:[{ label:'Citas realizadas', data:dataDoc, backgroundColor:'#198754' }] },
        options: { responsive:true, indexAxis: 'y', scales:{ y:{ beginAtZero:true } } }
      });
    }
})();
</script>
