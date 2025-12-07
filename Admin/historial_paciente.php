<?php
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION['id_doctor'])) {
  $_SESSION['MensajeTexto'] = "Error: acceso no registrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: ../index.php");
  exit;
}

$id_doctor = (int)$_SESSION['id_doctor'];
$id_paciente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_paciente === 0) {
  header("Location: historial_medico.php");
  exit;
}

$row = consultarDoctor($link, $id_doctor);

/* ====== Información del paciente ====== */
$sql_paciente = "SELECT * FROM pacientes WHERE id_paciente = $id_paciente";
$result_paciente = mysqli_query($link, $sql_paciente);
$paciente = mysqli_fetch_assoc($result_paciente);

if (!$paciente) {
  $_SESSION['MensajeTexto'] = "Paciente no encontrado.";
  $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
  header("Location: historial_medico.php");
  exit;
}

/* ====== Variables del sidebar ====== */
$SIDEBAR_ACTIVE = 'historial';
$DOCTOR_NAME    = htmlspecialchars(utf8_decode(($row['nombreD'] ?? '').' '.($row['apellido'] ?? '')));
$DOCTOR_SEX     = $row['sexo'] ?? 'Masculino';
$AVATAR_IMG     = ($DOCTOR_SEX === 'Femenino') ? '../src/img/odontologa.png' : '../src/img/odontologo.png';

/* ====== Obtener historiales médicos del paciente ====== */
$sql_historial = "
  SELECT h.*, d.nombreD, d.apellido as apellidoD
  FROM historial_medico h
  INNER JOIN doctor d ON h.id_doctor = d.id_doctor
  WHERE h.id_paciente = $id_paciente
  ORDER BY h.fecha_registro DESC
";
$historiales = mysqli_query($link, $sql_historial);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Perfect Teeth – Historial de <?= htmlspecialchars($paciente['nombre'].' '.$paciente['apellido']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="../src/img/logo.png" type="image/png" />
  <link rel="stylesheet" href="../src/css/lib/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.min.css">

  <style>
    :root{
      --brand:#0d6efd; --brand-100:#e7f1ff;
      --surface:#f8f9fa; --text:#212529;
      --sidebar-w:260px; --maxw:1200px; --radius:12px;
    }
    *{ box-sizing:border-box; }
    html, body { height:100%; }
    body{ margin:0; background:var(--surface); color:var(--text); }

    .sidebar{
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:#fff; border-right:0 !important;
      padding:1.25rem 1rem; overflow-y:auto; z-index:1030;
    }
    .brand{ display:flex; align-items:center; gap:.75rem; }
    .brand-title{ margin:0; font-weight:700; color:var(--brand); font-size:1.05rem; }
    .profile{ text-align:center; margin:1rem 0 1.25rem; }
    .profile img{ width:96px; height:96px; object-fit:cover; }
    .profile .name{ margin:.6rem 0 .1rem; font-weight:600; }
    .nav-menu .nav-link{
      display:flex; align-items:center; gap:.6rem;
      border-radius:.6rem; padding:.6rem .75rem;
      color:#495057; text-decoration:none;
    }
    .nav-menu .nav-link:hover,
    .nav-menu .nav-link.active{
      background:var(--brand-100); color:var(--brand); font-weight:600;
    }

    .main{ margin-left:var(--sidebar-w); min-height:100vh; padding:1.5rem; }
    .topbar{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); padding:.75rem 1rem; position:sticky; top:0; z-index:10; }
    .card{ border-radius:var(--radius); border:1px solid rgba(0,0,0,.06); box-shadow:0 6px 16px rgba(15,23,42,.06); }
    
    .patient-info-card {
      background: var(--brand) !important;
      color: white;
      border: none;
    }

    .historial-timeline{
      position: relative;
      padding-left: 30px;
    }
    .historial-timeline::before{
      content: '';
      position: absolute;
      left: 10px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: var(--brand);
    }
    .historial-item{
      position: relative;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: white;
      border-radius: 8px;
      border-left: 4px solid var(--brand);
    }
    .historial-item::before{
      content: '';
      position: absolute;
      left: -33px;
      top: 1rem;
      width: 12px;
      height: 12px;
      background: var(--brand);
      border-radius: 50%;
      border: 3px solid white;
    }
    .info-badge{
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      margin-right: 8px;
      margin-bottom: 8px;
    }
    .bg-blood{ background: #dc3545; color: white; }
    .bg-allergy{ background: #ffc107; color: #000; }
    .bg-disease{ background: #17a2b8; color: white; }

    /* Estilo para el botón cerrar del modal (×) */
    .modal-header .close {
      background: transparent;
      border: 0;
      font-size: 1.5rem;
      font-weight: 700;
      line-height: 1;
      opacity: 0.5;
      padding: 0;
      cursor: pointer;
    }
    .modal-header .close:hover {
      opacity: 0.75;
    }
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
    <img src="<?= $AVATAR_IMG ?>" class="rounded-circle" alt="Perfil">
    <h3 class="name"><?= $DOCTOR_NAME ?></h3>
    <span class="country text-muted">Perfect Teeth</span>
  </div>

  <nav class="nav-menu">
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='citas'?'active':'') ?>" href="inicioAdmin.php">
      <i class="far fa-calendar-check"></i><span>Citas pendientes</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='calendario'?'active':'') ?>" href="calendar.php">
      <i class="far fa-calendar-alt"></i><span>Calendario</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='historial'?'active':'') ?>" href="historial_medico.php">
      <i class="fas fa-notes-medical"></i><span>Historial Médico</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='odontograma'?'active':'') ?>" href="../odontograma.php">
      <i class="fas fa-tooth"></i><span>Odontograma</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='presupuestos'?'active':'') ?>" href="presupuestos_doctor.php">
      <i class="fas fa-file-invoice-dollar"></i><span>Presupuestos</span>
    </a>
    <a class="nav-link <?= ($SIDEBAR_ACTIVE==='reportes'?'active':'') ?>" href="reportes_doctor.php">
      <i class="fas fa-chart-line"></i><span>Reportes</span>
    </a>
    <a class="nav-link" href="../php/cerrar.php">
      <i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span>
    </a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="historial_medico.php" class="btn btn-sm btn-outline-secondary me-2">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
      <h5 class="d-inline mb-0 text-primary">
        <i class="fas fa-user-injured"></i> Historial de <?= htmlspecialchars($paciente['nombre'].' '.$paciente['apellido']) ?>
      </h5>
    </div>
    <button class="btn btn-primary" onclick="abrirModalNuevo()">
      <i class="fas fa-plus"></i> Nuevo Registro
    </button>
  </div>

  <!-- Información del Paciente -->
  <div class="card patient-info-card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <h6 class="mb-1">Cédula</h6>
          <p class="mb-0"><i class="fas fa-id-card"></i> <?= htmlspecialchars($paciente['cedula']) ?></p>
        </div>
        <div class="col-md-3">
          <h6 class="mb-1">Teléfono</h6>
          <p class="mb-0"><i class="fas fa-phone"></i> <?= htmlspecialchars($paciente['telefono']) ?></p>
        </div>
        <div class="col-md-3">
          <h6 class="mb-1">Sexo</h6>
          <p class="mb-0"><i class="fas fa-venus-mars"></i> <?= htmlspecialchars($paciente['sexo']) ?></p>
        </div>
        <div class="col-md-3">
          <h6 class="mb-1">Fecha de Nacimiento</h6>
          <p class="mb-0"><i class="fas fa-birthday-cake"></i> <?= htmlspecialchars($paciente['fecha_nacimiento']) ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Historiales -->
  <div class="card">
    <div class="card-header bg-white">
      <h6 class="mb-0 text-primary">
        <i class="fas fa-history"></i> Registros Médicos
      </h6>
    </div>
    <div class="card-body">
      <?php if (mysqli_num_rows($historiales) === 0): ?>
        <div class="text-center py-5">
          <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
          <h5 class="text-muted">No hay registros médicos</h5>
          <p class="text-muted">Este paciente aún no tiene historial médico registrado.</p>
          <button class="btn btn-primary" onclick="abrirModalNuevo()">
            <i class="fas fa-plus"></i> Crear Primer Registro
          </button>
        </div>
      <?php else: ?>
        <div class="historial-timeline">
          <?php while ($h = mysqli_fetch_assoc($historiales)): ?>
            <div class="historial-item">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h6 class="mb-1">
                    <i class="fas fa-calendar-alt text-primary"></i>
                    <?= date('d/m/Y H:i', strtotime($h['fecha_registro'])) ?>
                  </h6>
                  <small class="text-muted">
                    <i class="fas fa-user-md"></i>
                    Dr. <?= htmlspecialchars($h['nombreD'].' '.$h['apellidoD']) ?>
                  </small>
                </div>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(<?= $h['id_historial'] ?>)">
                    <i class="fas fa-eye"></i> Ver
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="eliminarHistorial(<?= $h['id_historial'] ?>)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>

              <div class="mt-3">
                <?php if ($h['grupo_sanguineo']): ?>
                  <span class="info-badge bg-blood">
                    <i class="fas fa-tint"></i> <?= htmlspecialchars($h['grupo_sanguineo']) ?>
                  </span>
                <?php endif; ?>

                <?php if ($h['alergias']): ?>
                  <span class="info-badge bg-allergy">
                    <i class="fas fa-exclamation-triangle"></i> Alergias
                  </span>
                <?php endif; ?>

                <?php if ($h['enfermedades_cronicas']): ?>
                  <span class="info-badge bg-disease">
                    <i class="fas fa-heartbeat"></i> Enfermedades Crónicas
                  </span>
                <?php endif; ?>
              </div>

              <?php if ($h['observaciones']): ?>
                <div class="mt-3 p-2 bg-light rounded">
                  <strong>Observaciones:</strong>
                  <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($h['observaciones'])) ?></p>
                </div>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Nuevo Historial -->
<div class="modal fade" id="modalNuevoHistorial" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nuevo Registro Médico</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form id="formNuevoHistorial">
        <div class="modal-body">
          <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">
          <input type="hidden" name="id_doctor" value="<?= $id_doctor ?>">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><i class="fas fa-tint text-danger"></i> Grupo Sanguíneo</label>
              <select name="grupo_sanguineo" class="form-select">
                <option value="">Seleccionar...</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label"><i class="fas fa-pills"></i> Medicamentos Actuales</label>
              <input type="text" name="medicamentos_actuales" class="form-control" 
                     placeholder="Ej: Ibuprofeno, Amoxicilina...">
            </div>

            <div class="col-12 mb-3">
              <label class="form-label"><i class="fas fa-exclamation-triangle text-warning"></i> Alergias</label>
              <textarea name="alergias" class="form-control" rows="2" 
                        placeholder="Especifique alergias conocidas (medicamentos, alimentos, etc.)"></textarea>
            </div>

            <div class="col-12 mb-3">
              <label class="form-label"><i class="fas fa-heartbeat text-info"></i> Enfermedades Crónicas</label>
              <textarea name="enfermedades_cronicas" class="form-control" rows="2" 
                        placeholder="Diabetes, hipertensión, asma, etc."></textarea>
            </div>

            <div class="col-12 mb-3">
              <label class="form-label"><i class="fas fa-procedures"></i> Cirugías Previas</label>
              <textarea name="cirugias_previas" class="form-control" rows="2" 
                        placeholder="Describa cirugías previas y fechas aproximadas"></textarea>
            </div>

            <div class="col-12 mb-3">
              <label class="form-label"><i class="fas fa-notes-medical"></i> Observaciones Generales</label>
              <textarea name="observaciones" class="form-control" rows="3" 
                        placeholder="Información adicional relevante"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Registro
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Ver Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-medical"></i> Detalle del Registro</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="detalleContenido">
        <!-- Se carga dinámicamente -->
      </div>
    </div>
  </div>
</div>

<!-- jQuery DEBE IR PRIMERO -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// Función para abrir el modal
function abrirModalNuevo() {
  console.log('✅ Abriendo modal de nuevo historial');
  $('#modalNuevoHistorial').modal('show');
}

function verDetalle(id) {
  $.ajax({
    url: 'ver_historial.php',
    type: 'GET',
    data: { id: id },
    success: function(response) {
      $('#detalleContenido').html(response);
      $('#modalDetalle').modal('show');
    },
    error: function() {
      alert('Error al cargar el detalle');
    }
  });
}

function eliminarHistorial(id) {
  if (confirm('¿Está seguro de eliminar este registro médico?')) {
    $.ajax({
      url: 'eliminar_historial.php',
      type: 'POST',
      data: { id: id },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          alert('✅ ' + response.message);
          location.reload();
        } else {
          alert('❌ Error: ' + response.message);
        }
      },
      error: function(xhr) {
        console.error('Error:', xhr.responseText);
        alert('❌ Error al eliminar el registro');
      }
    });
  }
}

$('#formNuevoHistorial').on('submit', function(e) {
  e.preventDefault();
  
  var formData = $(this).serialize();
  console.log('📤 Enviando datos:', formData);
  
  $.ajax({
    url: 'guardar_historial.php',
    type: 'POST',
    data: formData,
    dataType: 'json',
    success: function(response) {
      console.log('📥 Respuesta recibida:', response);
      if (response.success) {
        alert('✅ ' + response.message);
        $('#modalNuevoHistorial').modal('hide');
        location.reload();
      } else {
        alert('❌ Error: ' + (response.message || 'Error desconocido'));
        console.error('Detalles del error:', response);
      }
    },
    error: function(xhr, status, error) {
      console.error('❌ Error AJAX Status:', status);
      console.error('❌ Error:', error);
      console.error('❌ Respuesta del servidor:', xhr.responseText);
      alert('❌ Error al guardar el registro.\n\nDetalles: ' + error + '\n\nRevisa la consola (F12) para más información.');
    }
  });
});

// Verificar carga del sistema
$(document).ready(function() {
  console.log('✅ Sistema de historial médico cargado correctamente');
  console.log('📋 ID Paciente:', <?= $id_paciente ?>);
  console.log('👨‍⚕️ ID Doctor:', <?= $id_doctor ?>);
  console.log('✅ jQuery version:', $.fn.jquery);
  console.log('✅ Bootstrap modal disponible:', typeof $.fn.modal !== 'undefined');
});
</script>
</body>
</html>