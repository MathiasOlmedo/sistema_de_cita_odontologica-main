<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION['id_doctor']) || !isset($_GET['id'])) {
  echo '<div class="alert alert-danger">Acceso no autorizado</div>';
  exit;
}

$id_historial = (int)$_GET['id'];

$sql = "
  SELECT h.*, 
         p.nombre as nombre_paciente, p.apellido as apellido_paciente, p.cedula,
         d.nombreD, d.apellido as apellidoD
  FROM historial_medico h
  INNER JOIN pacientes p ON h.id_paciente = p.id_paciente
  INNER JOIN doctor d ON h.id_doctor = d.id_doctor
  WHERE h.id_historial = $id_historial
";

$result = mysqli_query($link, $sql);
$h = mysqli_fetch_assoc($result);

if (!$h) {
  echo '<div class="alert alert-danger">Registro no encontrado</div>';
  exit;
}
?>

<div class="row">
  <div class="col-md-6 mb-3">
    <strong><i class="fas fa-user"></i> Paciente:</strong><br>
    <?= htmlspecialchars($h['nombre_paciente'].' '.$h['apellido_paciente']) ?><br>
    <small class="text-muted">CI: <?= htmlspecialchars($h['cedula']) ?></small>
  </div>

  <div class="col-md-6 mb-3">
    <strong><i class="fas fa-user-md"></i> Doctor:</strong><br>
    Dr. <?= htmlspecialchars($h['nombreD'].' '.$h['apellidoD']) ?><br>
    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['fecha_registro'])) ?></small>
  </div>
</div>

<hr>

<div class="row">
  <?php if ($h['grupo_sanguineo']): ?>
  <div class="col-md-6 mb-3">
    <div class="p-3 bg-danger bg-opacity-10 rounded">
      <strong><i class="fas fa-tint text-danger"></i> Grupo Sanguíneo:</strong><br>
      <span class="fs-5"><?= htmlspecialchars($h['grupo_sanguineo']) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($h['medicamentos_actuales']): ?>
  <div class="col-md-6 mb-3">
    <div class="p-3 bg-primary bg-opacity-10 rounded">
      <strong><i class="fas fa-pills text-primary"></i> Medicamentos Actuales:</strong><br>
      <?= nl2br(htmlspecialchars($h['medicamentos_actuales'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($h['alergias']): ?>
  <div class="col-12 mb-3">
    <div class="p-3 bg-warning bg-opacity-10 rounded">
      <strong><i class="fas fa-exclamation-triangle text-warning"></i> Alergias:</strong><br>
      <?= nl2br(htmlspecialchars($h['alergias'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($h['enfermedades_cronicas']): ?>
  <div class="col-12 mb-3">
    <div class="p-3 bg-info bg-opacity-10 rounded">
      <strong><i class="fas fa-heartbeat text-info"></i> Enfermedades Crónicas:</strong><br>
      <?= nl2br(htmlspecialchars($h['enfermedades_cronicas'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($h['cirugias_previas']): ?>
  <div class="col-12 mb-3">
    <div class="p-3 bg-secondary bg-opacity-10 rounded">
      <strong><i class="fas fa-procedures text-secondary"></i> Cirugías Previas:</strong><br>
      <?= nl2br(htmlspecialchars($h['cirugias_previas'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($h['observaciones']): ?>
  <div class="col-12 mb-3">
    <div class="p-3 bg-light rounded">
      <strong><i class="fas fa-notes-medical"></i> Observaciones Generales:</strong><br>
      <?= nl2br(htmlspecialchars($h['observaciones'])) ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
mysqli_close($link);
?>