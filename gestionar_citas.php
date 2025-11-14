<?php 
include_once('./php/conexionDB.php');

$where = "1=1";
if (!empty($_GET['paciente'])) {
  $paciente = mysqli_real_escape_string($link, $_GET['paciente']);
  $where .= " AND p.nombre LIKE '%$paciente%'";
}
if (!empty($_GET['fecha'])) {
  $fecha = mysqli_real_escape_string($link, $_GET['fecha']);
  $where .= " AND c.fecha_cita = '$fecha'";
}

$citas = mysqli_query($link, "
  SELECT c.id_cita, p.nombre AS paciente, d.nombreD AS dentista, c.fecha_cita, c.hora_cita, c.estado
  FROM citas c
  JOIN pacientes p ON c.id_paciente = p.id_paciente
  JOIN doctor d ON c.id_doctor = d.id_doctor
  WHERE $where
");
?>

<div id="alert-placeholder"></div>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title">Gestión de Citas</h5>
    <p class="card-text">Administra citas: búsqueda, alta, edición y baja.</p>
  </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-3" id="form-filtros-citas">
      <div class="col-md-6"><input type="text" name="paciente" class="form-control" placeholder="Buscar por paciente"></div>
      <div class="col-md-6"><input type="date" name="fecha" class="form-control"></div>
      <div class="col-md-12"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
    </form>
  </div>
</div>

<!-- Grilla -->
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <h6 class="card-title">Lista de Citas</h6>
      <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarCita">
        <i class="bi bi-plus-circle"></i> Agregar
      </button>
    </div>
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Paciente</th><th>Dentista</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php while ($c = mysqli_fetch_assoc($citas)) { ?>
        <tr>
          <td><?= $c['id_cita'] ?></td>
          <td><?= $c['paciente'] ?></td>
          <td><?= $c['dentista'] ?></td>
          <td><?= $c['fecha_cita'] ?></td>
          <td><?= $c['hora_cita'] ?></td>
          <td><?= $c['estado'] ?></td>
          <td>
            <button class="btn btn-warning btn-sm"
              data-bs-toggle="modal" data-bs-target="#modalEditarCita"
              data-id="<?= $c['id_cita'] ?>"
              data-fecha="<?= $c['fecha_cita'] ?>"
              data-hora="<?= $c['hora_cita'] ?>"
              data-estado="<?= $c['estado'] ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#modalEliminarCita"
              data-id="<?= $c['id_cita'] ?>">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Resumen -->
<div class="card">
  <div class="card-body">
    <p>Total Citas: <strong><?= mysqli_num_rows($citas) ?></strong></p>
  </div>
</div>

<!-- Modales -->
<!-- Agregar -->
<div class="modal fade" id="modalAgregarCita" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAgregarCita" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Agregar Cita</h5></div>
      <div class="modal-body">
        <input type="number" name="id_paciente" class="form-control mb-2" placeholder="ID Paciente" required>
        <input type="number" name="id_doctor" class="form-control mb-2" placeholder="ID Dentista" required>
        <input type="date" name="fecha_cita" class="form-control mb-2" required>
        <input type="time" name="hora_cita" class="form-control mb-2" required>
        <input type="text" name="estado" class="form-control mb-2" placeholder="Estado" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Editar -->
<div class="modal fade" id="modalEditarCita" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEditarCita" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar Cita</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_cita" id="edit-id">
        <input type="date" name="fecha_cita" id="edit-fecha" class="form-control mb-2" required>
        <input type="time" name="hora_cita" id="edit-hora" class="form-control mb-2" required>
        <input type="text" name="estado" id="edit-estado" class="form-control mb-2" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Eliminar -->
<div class="modal fade" id="modalEliminarCita" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEliminarCita" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Eliminar Cita</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_cita" id="delete-id">
        <p>¿Seguro que deseas eliminar esta cita?</p>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Rellenar modal editar
  var modalEditar = document.getElementById('modalEditarCita')
  modalEditar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget
    document.getElementById('edit-id').value = button.getAttribute('data-id')
    document.getElementById('edit-fecha').value = button.getAttribute('data-fecha')
    document.getElementById('edit-hora').value = button.getAttribute('data-hora')
    document.getElementById('edit-estado').value = button.getAttribute('data-estado')
  })

  var modalEliminar = document.getElementById('modalEliminarCita')
  modalEliminar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget
    document.getElementById('delete-id').value = button.getAttribute('data-id')
  })

  function showAndReload(message, type="success") {
    $("#alert-placeholder").html(
      `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
         ${message}
         <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
       </div>`
    );
    setTimeout(() => loadContent('gestionar_citas.php'), 1500);
  }

  // AJAX Agregar
  $("#formAgregarCita").submit(function(e){
    e.preventDefault();
    $.post("crud/cita_INSERT.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        bootstrap.Modal.getInstance(document.getElementById('modalAgregarCita')).hide();
        showAndReload("✅ Cita agregada con éxito");
      } else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // AJAX Editar
  $("#formEditarCita").submit(function(e){
    e.preventDefault();
    $.post("crud/cita_UPDATE.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        bootstrap.Modal.getInstance(document.getElementById('modalEditarCita')).hide();
        showAndReload("✅ Cita actualizada con éxito");
      } else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // AJAX Eliminar
  $("#formEliminarCita").submit(function(e){
    e.preventDefault();
    $.post("crud/cita_DELETE.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        bootstrap.Modal.getInstance(document.getElementById('modalEliminarCita')).hide();
        showAndReload("✅ Cita eliminada con éxito");
      } else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // Filtros con AJAX
  $("#form-filtros-citas").submit(function(e){
    e.preventDefault();
    $.get("gestionar_citas.php", $(this).serialize(), function(data){
      $("#dynamic-content").html(data);
    });
  });
</script>
