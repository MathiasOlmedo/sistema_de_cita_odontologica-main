<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../php/conexionDB.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ======= Control de acceso ======= */
if (
  empty($_SESSION['id_usuario']) ||
  ($_SESSION['tipo'] ?? '') !== 'Secretaria'
) {
  header('Location: ../index.php');
  exit;
}

$usuario = $_SESSION['nombre'] ?? 'Secretaría';

/* ======= Cargar doctores, pacientes y consultas ======= */
$doctores = mysqli_query($link, "SELECT id_doctor, CONCAT(nombreD, ' ', apellido) AS nombre FROM doctor ORDER BY nombreD ASC");
$pacientes = mysqli_query($link, "SELECT id_paciente, CONCAT(nombre, ' ', apellido) AS nombre_completo, cedula FROM pacientes ORDER BY nombre ASC");
$consultas = mysqli_query($link, "SELECT id_consultas, tipo FROM consultas ORDER BY tipo ASC");

/* ======= Filtros ======= */
$where = "1=1";

if (!empty($_GET['nombre'])) {
  $nombre = mysqli_real_escape_string($link, $_GET['nombre']);
  $where .= " AND p.nombre LIKE '%$nombre%'";
}
if (!empty($_GET['fecha'])) {
  $fecha = mysqli_real_escape_string($link, $_GET['fecha']);
  $where .= " AND c.fecha_cita = '$fecha'";
}
if (!empty($_GET['doctor'])) {
  $doctor = (int)$_GET['doctor'];
  $where .= " AND c.id_doctor = $doctor";
}
if (!empty($_GET['estado'])) {
  $estadoFiltro = mysqli_real_escape_string($link, $_GET['estado']);
  $where .= " AND c.estado = '$estadoFiltro'";
}

/* ======= Consultar citas ======= */
$sql = "
  SELECT 
    c.id_cita, c.fecha_cita, c.hora_cita, c.estado,
    p.nombre AS nombre_paciente, p.apellido AS apellido_paciente, p.cedula,
    d.nombreD AS nombre_doctor, d.apellido AS apellido_doctor,
    con.tipo AS tipo_consulta
  FROM citas c
  INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
  INNER JOIN doctor d ON c.id_doctor = d.id_doctor
  LEFT JOIN consultas con ON con.id_consultas = c.id_consultas
  WHERE $where
  ORDER BY c.fecha_cita DESC, c.hora_cita ASC
";
$citas = mysqli_query($link, $sql);
if (!$citas) die("<b>Error SQL:</b> " . mysqli_error($link));

date_default_timezone_set("America/Asuncion");
$fecha = date("d/m/Y");
$hora = date("H:i");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Citas – Secretaría</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
:root{
  --brand:#0d6efd; --brand-100:#e7f1ff;
  --surface:#f8f9fa; --text:#212529;
  --sidebar-w:260px; --radius:12px;
}
body{margin:0;background:var(--surface);color:var(--text);}
.sidebar{
  position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;
  background:#fff;padding:1.25rem 1rem;border-right:0;box-shadow:none;z-index:1030;
}
.brand{display:flex;align-items:center;gap:.75rem;}
.brand-title{margin:0;font-weight:700;color:var(--brand);}
.profile{text-align:center;margin:1rem 0;}
.profile img{width:96px;height:96px;object-fit:cover;}
.nav-menu{display:flex;flex-direction:column;gap:.25rem;}
.nav-menu .nav-link{
  display:flex;align-items:center;gap:.6rem;
  border-radius:.6rem;padding:.6rem .75rem;color:#495057;text-decoration:none;
}
.nav-menu .nav-link:hover,.nav-menu .nav-link.active{
  background:var(--brand-100);color:var(--brand);font-weight:600;
}
.main{margin-left:var(--sidebar-w);min-height:100vh;padding:1.5rem;}
.topbar{
  background:#fff;border-bottom:1px solid rgba(0,0,0,.06);
  padding:.75rem 1rem;position:sticky;top:0;z-index:10;
}
.card{border-radius:var(--radius);border:1px solid rgba(0,0,0,.06);box-shadow:0 6px 16px rgba(15,23,42,.06);}

/* ✅ ESTILOS PARA SELECCIÓN DE HORARIOS (del principal.php) */
.time-slots-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 10px;
  margin-top: 15px;
  max-height: 400px;
  overflow-y: auto;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 10px;
}

.time-slot {
  padding: 12px 8px;
  text-align: center;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  font-weight: 600;
  border: 2px solid transparent;
}

.time-slot.available {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.time-slot.available:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
}

.time-slot.available.selected {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  border-color: #155724;
  transform: scale(1.05);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.5);
}

.time-slot.occupied {
  background: #e9ecef;
  color: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

.loading-indicator {
  text-align: center;
  padding: 30px;
  color: #6c757d;
}

.loading-indicator i {
  font-size: 2rem;
  margin-bottom: 10px;
  display: block;
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
    <img src="../src/img/secretaria.png" class="rounded-circle border" alt="Perfil">
    <div class="name"><?= htmlspecialchars($usuario) ?></div>
    <div class="small text-muted">Panel Secretaría</div>
  </div>

  <nav class="nav-menu">
    <a href="gestionar_pacientes.php" class="nav-link"><i class="bi bi-people"></i> Pacientes</a>
    <a href="pagos.php" class="nav-link"><i class="bi bi-cash-stack"></i> Pagos</a>
    <a href="gestionar_citas.php" class="nav-link active"><i class="bi bi-calendar-check"></i> Citas</a>
    <a href="presupuestos_pendientes.php" class="nav-link"><i class="bi bi-clock-history"></i> Presupuestos</a>
    <a href="perfil_secretaria.php" class="nav-link"><i class="bi bi-person-gear"></i> Perfil</a>
    <a href="../php/cerrar.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
  </nav>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-primary"><i class="bi bi-calendar-check"></i> Gestión de Citas</h5>
    <small class="text-muted"><?= $fecha ?> • <?= $hora ?></small>
  </div>

  <!-- Alertas -->
  <div id="alert-container"></div>

  <!-- Filtros -->
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-3"><input type="text" name="nombre" class="form-control" placeholder="Buscar por paciente" value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>"></div>
        <div class="col-md-2"><input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($_GET['fecha'] ?? '') ?>"></div>
        <div class="col-md-3">
          <select name="doctor" class="form-select">
            <option value="">-- Todos los doctores --</option>
            <?php 
            mysqli_data_seek($doctores, 0);
            while ($d = mysqli_fetch_assoc($doctores)) { ?>
              <option value="<?= $d['id_doctor'] ?>" <?= (isset($_GET['doctor']) && $_GET['doctor'] == $d['id_doctor']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['nombre']) ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="estado" class="form-select">
            <option value="">-- Todos --</option>
            <option value="I" <?= (isset($_GET['estado']) && $_GET['estado'] === 'I') ? 'selected' : '' ?>>Pendiente</option>
            <option value="A" <?= (isset($_GET['estado']) && $_GET['estado'] === 'A') ? 'selected' : '' ?>>Atendida</option>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button></div>
      </form>
    </div>
  </div>

  <!-- Lista -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="text-primary mb-0"><i class="bi bi-list-ul"></i> Citas Registradas</h6>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarCita">
          <i class="bi bi-plus-circle"></i> Nueva Cita
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th><th>Paciente</th><th>Cédula</th><th>Doctor</th><th>Consulta</th><th>Fecha</th><th>Hora</th><th>Estado</th><th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            mysqli_data_seek($citas, 0);
            while ($c = mysqli_fetch_assoc($citas)) { ?>
            <tr>
              <td><?= $c['id_cita'] ?></td>
              <td><?= htmlspecialchars($c['nombre_paciente'] . ' ' . $c['apellido_paciente']) ?></td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($c['cedula']) ?></span></td>
              <td><?= htmlspecialchars($c['nombre_doctor'] . ' ' . $c['apellido_doctor']) ?></td>
              <td><small><?= htmlspecialchars($c['tipo_consulta'] ?? 'N/A') ?></small></td>
              <td><?= date('d/m/Y', strtotime($c['fecha_cita'])) ?></td>
              <td><strong><?= date('H:i', strtotime($c['hora_cita'])) ?></strong></td>
              <td>
                <?php if($c['estado'] === 'I'): ?>
                  <span class="badge bg-warning text-dark">Pendiente</span>
                <?php else: ?>
                  <span class="badge bg-success">Atendida</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if($c['estado'] === 'I'): ?>
                  <button class="btn btn-sm btn-success" onclick="marcarAtendida(<?= $c['id_cita'] ?>)" title="Marcar como atendida">
                    <i class="bi bi-check-circle"></i>
                  </button>
                <?php endif; ?>
                
                <button class="btn btn-sm btn-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalCancelarCita"
                        data-id="<?= $c['id_cita'] ?>"
                        data-paciente="<?= htmlspecialchars($c['nombre_paciente'] . ' ' . $c['apellido_paciente']) ?>"
                        data-fecha="<?= date('d/m/Y', strtotime($c['fecha_cita'])) ?>"
                        data-hora="<?= date('H:i', strtotime($c['hora_cita'])) ?>"
                        title="Cancelar cita">
                  <i class="bi bi-x-circle"></i>
                </button>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ✅ Modal: Agregar Cita CON SISTEMA DE HORARIOS IGUAL AL DE PACIENTE -->
<div class="modal fade" id="modalAgregarCita" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="formAgregarCita" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Agendar Nueva Cita</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <!-- Selector de paciente -->
          <div class="col-12">
            <label class="form-label"><i class="bi bi-person"></i> Paciente</label>
            <select name="id_paciente" id="selectPaciente" class="form-select" required>
              <option value="">-- Buscar paciente por nombre o cédula --</option>
              <?php 
              mysqli_data_seek($pacientes, 0);
              while ($p = mysqli_fetch_assoc($pacientes)) { ?>
                <option value="<?= $p['id_paciente'] ?>">
                  <?= htmlspecialchars($p['nombre_completo']) ?> - CI: <?= htmlspecialchars($p['cedula']) ?>
                </option>
              <?php } ?>
            </select>
            <small class="text-muted">Si el paciente no está registrado, primero regístralo en "Gestionar Pacientes"</small>
          </div>

          <!-- Selector de doctor -->
          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-person-badge"></i> Doctor</label>
            <select name="id_doctor" id="selectDoctor" class="form-select" required>
              <option value="">-- Seleccione un doctor --</option>
              <?php 
              mysqli_data_seek($doctores, 0);
              while ($d = mysqli_fetch_assoc($doctores)) { ?>
                <option value="<?= $d['id_doctor'] ?>">
                  <?= htmlspecialchars($d['nombre']) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <!-- Fecha de la cita -->
          <div class="col-md-6">
            <label class="form-label"><i class="bi bi-calendar-event"></i> Fecha de la cita</label>
            <input type="date" name="fecha_cita" id="fecha_cita" class="form-control" required min="<?= date('Y-m-d') ?>">
            <small class="text-muted">Lunes a Viernes únicamente</small>
          </div>

          <!-- Tipo de consulta -->
          <div class="col-12">
            <label class="form-label"><i class="bi bi-clipboard-pulse"></i> Tipo de consulta</label>
            <select name="id_consultas" class="form-select" required>
              <option value="">-- Seleccione el tipo de consulta --</option>
              <?php 
              mysqli_data_seek($consultas, 0);
              while ($con = mysqli_fetch_assoc($consultas)) { ?>
                <option value="<?= $con['id_consultas'] ?>">
                  <?= htmlspecialchars($con['tipo']) ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <!-- ✅ SELECTOR DE HORARIOS (igual que principal.php) -->
          <div class="col-12">
            <label class="form-label"><i class="bi bi-clock"></i> Seleccione un horario disponible</label>
            <div id="horarios-container" class="time-slots-container">
              <div class="loading-indicator">
                <i class="bi bi-info-circle"></i>
                <p>Selecciona un doctor y una fecha para ver los horarios disponibles</p>
              </div>
            </div>
            <input type="hidden" name="hora_cita" id="hora" required>
          </div>

          <!-- Estado inicial -->
          <div class="col-12">
            <label class="form-label"><i class="bi bi-info-circle"></i> Estado inicial</label>
            <select name="estado" class="form-select" required>
              <option value="I" selected>Pendiente</option>
              <option value="A">Atendida (si ya fue realizada)</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">
          <i class="bi bi-save"></i> Confirmar Cita
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Cancelar Cita -->
<div class="modal fade" id="modalCancelarCita" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Cancelar Cita</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cancel-id">
        <div class="alert alert-warning">
          <strong>¿Está segura de cancelar esta cita?</strong>
        </div>
        <p><strong>Paciente:</strong> <span id="cancel-paciente"></span></p>
        <p><strong>Fecha:</strong> <span id="cancel-fecha"></span></p>
        <p><strong>Hora:</strong> <span id="cancel-hora"></span></p>
        <div class="alert alert-info small">
          <i class="bi bi-info-circle"></i> La cita se eliminará permanentemente y el horario quedará disponible.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" onclick="confirmarCancelacion()">
          <i class="bi bi-trash"></i> Sí, cancelar
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Inicializar Select2
$(document).ready(function() {
  $('#selectPaciente').select2({
    theme: 'bootstrap-5',
    dropdownParent: $('#modalAgregarCita'),
    placeholder: 'Buscar paciente...',
    allowClear: true,
    language: {
      noResults: function() { return "No se encontró el paciente"; },
      searching: function() { return "Buscando..."; }
    }
  });
});

// ✅ VALIDACIÓN DE FECHA: BLOQUEAR FINES DE SEMANA (igual que principal.php)
$('#fecha_cita').on('change', function() {
  const fecha = new Date(this.value + 'T00:00:00');
  const day = fecha.getUTCDay();
  
  if (day === 0 || day === 6) {
    alert('⚠️ No se permiten citas los fines de semana. Por favor selecciona un día de Lunes a Viernes.');
    $(this).val('');
    $('#horarios-container').html('<div class="loading-indicator"><i class="bi bi-exclamation-triangle"></i><p>Selecciona una fecha válida</p></div>');
  }
});

// ✅ SISTEMA DE HORARIOS EN TIEMPO REAL (igual que principal.php)
function cargarHorarios() {
  const doctor = $("#selectDoctor").val();
  const fecha = $("#fecha_cita").val();
  const contenedor = $("#horarios-container");
  
  // Limpiar horario seleccionado
  $("#hora").val('');

  if (!doctor || !fecha) {
    contenedor.html('<div class="loading-indicator"><i class="bi bi-info-circle"></i><p>Selecciona un doctor y una fecha para ver los horarios disponibles</p></div>');
    return;
  }

  // Mostrar indicador de carga
  contenedor.html('<div class="loading-indicator"><i class="bi bi-spinner-border"></i><p>Cargando horarios disponibles...</p></div>');

  // Petición AJAX para obtener horarios
  $.ajax({
    url: "../ajax/horarios_disponibles.php",
    method: "GET",
    data: { doctor: doctor, fecha: fecha, _t: new Date().getTime() },
    cache: false,
    dataType: "json",
    success: function(data) {
      contenedor.empty();

      if (data.error) {
        contenedor.html('<div class="loading-indicator"><i class="bi bi-exclamation-triangle"></i><p>' + data.error + '</p></div>');
        return;
      }

      if (data.length === 0) {
        contenedor.html('<div class="loading-indicator"><i class="bi bi-calendar-x"></i><p>No hay horarios disponibles para esta fecha</p></div>');
        return;
      }

      // Crear botones de horarios
      data.forEach(function(horario) {
        const div = $("<div>")
          .addClass("time-slot")
          .addClass(horario.disponible ? "available" : "occupied")
          .html('<i class="bi bi-clock"></i> ' + horario.hora)
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
      contenedor.html('<div class="loading-indicator"><i class="bi bi-exclamation-triangle"></i><p>Error al cargar horarios. Por favor intenta nuevamente.</p></div>');
    }
  });
}

// Cargar horarios al cambiar doctor o fecha
$("#fecha_cita, #selectDoctor").on("change", cargarHorarios);

// Función para mostrar alertas
function showAlert(message, type='success') {
  const alert = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  `;
  $('#alert-container').html(alert);
  $('html, body').animate({ scrollTop: 0 }, 300);
  setTimeout(() => $('.alert').fadeOut(400, function() { $(this).remove(); }), 5000);
}

// Marcar cita como atendida
function marcarAtendida(id) {
  if(!confirm('¿Marcar esta cita como atendida?')) return;
  
  $.post("../crud/cita_UPDATE.php", {
    id_cita: id,
    estado: 'A'
  }, function(r){
    if(r.status==="success") {
      showAlert("✅ Cita marcada como atendida");
      setTimeout(() => location.reload(), 1500);
    } else {
      showAlert("❌ Error: "+(r.msg||"Desconocido"), "danger");
    }
  },"json").fail(function(){
    showAlert("❌ Error de conexión", "danger");
  });
}

// Preparar modal de cancelación
document.getElementById('modalCancelarCita').addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  $('#cancel-id').val(b.dataset.id);
  $('#cancel-paciente').text(b.dataset.paciente);
  $('#cancel-fecha').text(b.dataset.fecha);
  $('#cancel-hora').text(b.dataset.hora);
});

// Confirmar cancelación
function confirmarCancelacion() {
  const id = $('#cancel-id').val();
  
  $.post("../crud/cita_DELETE.php", {
    id_cita: id
  }, function(r){
    if(r.status==="success") {
      const modalEl = document.getElementById('modalCancelarCita');
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if(modalInstance) modalInstance.hide();
      
      showAlert("✅ Cita cancelada correctamente");
      setTimeout(() => location.reload(), 1500);
    } else {
      showAlert("❌ Error: "+(r.msg||"Desconocido"), "danger");
    }
  },"json").fail(function(){
    showAlert("❌ Error de conexión", "danger");
  });
}

// ✅ AGREGAR CITA CON VALIDACIÓN DE HORARIO
$("#formAgregarCita").submit(function(e){
  e.preventDefault();
  
  // Validar que se haya seleccionado un horario
  if (!$("#hora").val()) {
    alert('⚠️ Por favor selecciona un horario disponible antes de confirmar la cita.');
    $("#horarios-container").css('border', '2px solid #dc3545');
    return false;
  }
  
  const btn = $(this).find('button[type="submit"]');
  btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Guardando...');
  
  $.post("../crud/citaa_INSERT.php", $(this).serialize(), function(r){
    if(r.status==="success") {
      showAlert("✅ Cita agendada correctamente");
      setTimeout(() => location.reload(), 1500);
    } else {
      btn.prop('disabled', false).html('<i class="bi bi-save"></i> Confirmar Cita');
      showAlert("❌ Error: "+(r.msg||"Desconocido"), "danger");
    }
  },"json").fail(function(){
    btn.prop('disabled', false).html('<i class="bi bi-save"></i> Confirmar Cita');
    showAlert("❌ Error de conexión", "danger");
  });
});

// Limpiar backdrop de modales
$('.modal').on('hidden.bs.modal', function () {
  $('.modal-backdrop').remove();
  $('body').removeClass('modal-open').css('padding-right', '');
});
</script>
</body>
</html>