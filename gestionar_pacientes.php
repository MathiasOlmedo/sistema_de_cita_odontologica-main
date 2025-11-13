<?php 
include_once('./php/conexionDB.php');

$where = "1=1";
if (!empty($_GET['nombre'])) {
  $nombre = mysqli_real_escape_string($link, $_GET['nombre']);
  $where .= " AND nombre LIKE '%$nombre%'";
}
if (!empty($_GET['apellido'])) {
  $apellido = mysqli_real_escape_string($link, $_GET['apellido']);
  $where .= " AND apellido LIKE '%$apellido%'";
}

$pacientes = mysqli_query($link, "SELECT * FROM pacientes WHERE $where");
?>

<!-- Espacio para mensajes -->
<div id="alert-placeholder"></div>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title">Gestión de Pacientes</h5>
    <p class="card-text">Administra pacientes: búsqueda, alta, edición y baja.</p>
  </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-3" id="form-filtros-pacientes">
      <div class="col-md-4"><input type="text" name="nombre" class="form-control" placeholder="Buscar por nombre"></div>
      <div class="col-md-4"><input type="text" name="apellido" class="form-control" placeholder="Buscar por apellido"></div>
      <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
    </form>
  </div>
</div>

<!-- Grilla -->
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <h6 class="card-title">Lista de Pacientes</h6>
      <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarPaciente">
        <i class="bi bi-plus-circle"></i> Agregar
      </button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Apellido</th><th>Teléfono</th>
          <th>Sexo</th><th>Correo</th><th>Clave</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = mysqli_fetch_assoc($pacientes)) { ?>
        <tr>
          <td><?= $p['id_paciente'] ?></td>
          <td><?= $p['nombre'] ?></td>
          <td><?= $p['apellido'] ?></td>
          <td><?= $p['telefono'] ?></td>
          <td><?= $p['sexo'] ?></td>
          <td><?= $p['correo_electronico'] ?></td>
          <td><?= $p['clave'] ?></td>
          <td>
            <button class="btn btn-warning btn-sm"
              data-bs-toggle="modal" data-bs-target="#modalEditarPaciente"
              data-id="<?= $p['id_paciente'] ?>"
              data-nombre="<?= $p['nombre'] ?>"
              data-apellido="<?= $p['apellido'] ?>"
              data-telefono="<?= $p['telefono'] ?>"
              data-sexo="<?= $p['sexo'] ?>"
              data-correo="<?= $p['correo_electronico'] ?>"
              data-clave="<?= $p['clave'] ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#modalEliminarPaciente"
              data-id="<?= $p['id_paciente'] ?>">
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
    <p>Total Pacientes: <strong><?= mysqli_num_rows($pacientes) ?></strong></p>
  </div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregarPaciente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAgregarPaciente" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Agregar Paciente</h5></div>
      <div class="modal-body">
        <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required>
        <input type="text" name="apellido" class="form-control mb-2" placeholder="Apellido" required>
        <input type="text" name="telefono" class="form-control mb-2" placeholder="Teléfono">
        <input type="text" name="sexo" class="form-control mb-2" placeholder="Sexo">
        <input type="email" name="correo" class="form-control mb-2" placeholder="Correo" required>
        <!-- 🔑 Clave -->
        <input type="text" name="clave" class="form-control mb-2" placeholder="Contraseña (clave)" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditarPaciente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEditarPaciente" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar Paciente</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_paciente" id="edit-id">
        <input type="text" name="nombre" id="edit-nombre" class="form-control mb-2" placeholder="Nombre">
        <input type="text" name="apellido" id="edit-apellido" class="form-control mb-2" placeholder="Apellido">
        <input type="text" name="telefono" id="edit-telefono" class="form-control mb-2" placeholder="Teléfono">
        <input type="text" name="sexo" id="edit-sexo" class="form-control mb-2" placeholder="Sexo">
        <input type="email" name="correo" id="edit-correo" class="form-control mb-2" placeholder="Correo">
        <!-- 🔑 Clave -->
        <input type="text" name="clave" id="edit-clave" class="form-control mb-2" placeholder="Contraseña (clave)">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminarPaciente" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEliminarPaciente" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Eliminar Paciente</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_paciente" id="delete-id">
        <p>¿Seguro que deseas eliminar este paciente?</p>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Rellenar modal editar
  var modalEditar = document.getElementById('modalEditarPaciente')
  modalEditar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget
    document.getElementById('edit-id').value = button.getAttribute('data-id')
    document.getElementById('edit-nombre').value = button.getAttribute('data-nombre')
    document.getElementById('edit-apellido').value = button.getAttribute('data-apellido')
    document.getElementById('edit-telefono').value = button.getAttribute('data-telefono')
    document.getElementById('edit-sexo').value = button.getAttribute('data-sexo')
    document.getElementById('edit-correo').value = button.getAttribute('data-correo')
    document.getElementById('edit-clave').value = button.getAttribute('data-clave')
  })

  var modalEliminar = document.getElementById('modalEliminarPaciente')
  modalEliminar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget
    document.getElementById('delete-id').value = button.getAttribute('data-id')
  })

  // 🟢 Función de alerta con reload automático
  function showAndReload(message, type="success") {
    $("#alert-placeholder").html(
      `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
         ${message}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
       </div>`
    );
    setTimeout(() => {
      loadContent('gestionar_pacientes.php');
    }, 1500);
  }

  // AJAX Agregar
  $("#formAgregarPaciente").submit(function(e){
    e.preventDefault();
    $.post("crud/paciente_INSERT.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        let modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarPaciente'));
        modal.hide();
        showAndReload("✅ Paciente agregado con éxito");
      }
      else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // AJAX Editar
  $("#formEditarPaciente").submit(function(e){
    e.preventDefault();
    $.post("crud/paciente_UPDATE.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        let modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarPaciente'));
        modal.hide();
        showAndReload("✅ Paciente actualizado con éxito");
      }
      else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // AJAX Eliminar
  $("#formEliminarPaciente").submit(function(e){
    e.preventDefault();
    $.post("crud/paciente_DELETE.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        let modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarPaciente'));
        modal.hide();
        showAndReload("✅ Paciente eliminado con éxito");
      }
      else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // Filtros con AJAX
  $("#form-filtros-pacientes").submit(function(e){
    e.preventDefault();
    $.get("gestionar_pacientes.php", $(this).serialize(), function(data){
      $("#dynamic-content").html(data);
    });
  });
</script>
