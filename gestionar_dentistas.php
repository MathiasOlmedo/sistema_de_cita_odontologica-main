<?php 
include_once('./php/conexionDB.php');

// Traer especialidades para el <select>
$especialidades = mysqli_query($link, "SELECT id_especialidad, tipo FROM especialidad");

$where = "1=1";
if (!empty($_GET['nombre'])) {
  $nombre = mysqli_real_escape_string($link, $_GET['nombre']);
  $where .= " AND nombreD LIKE '%$nombre%'";
}
if (!empty($_GET['apellido'])) {
  $apellido = mysqli_real_escape_string($link, $_GET['apellido']);
  $where .= " AND apellido LIKE '%$apellido%'";
}

$dentistas = mysqli_query($link, "SELECT * FROM doctor WHERE $where");
?>

<div id="alert-placeholder"></div>

<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title">Gestión de Dentistas</h5>
    <p class="card-text">Administra dentistas: búsqueda, alta, edición y baja.</p>
  </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-3" id="form-filtros-dentistas">
      <div class="col-md-6"><input type="text" name="nombre" class="form-control" placeholder="Buscar por nombre"></div>
      <div class="col-md-6"><input type="text" name="apellido" class="form-control" placeholder="Buscar por apellido"></div>
      <div class="col-md-12"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
    </form>
  </div>
</div>

<!-- Grilla -->
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <h6 class="card-title">Lista de Dentistas</h6>
      <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarDentista">
        <i class="bi bi-plus-circle"></i> Agregar
      </button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Apellido</th><th>Teléfono</th>
          <th>Sexo</th><th>Correo</th><th>Clave</th><th>Especialidad</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($d = mysqli_fetch_assoc($dentistas)) { ?>
        <tr>
          <td><?= $d['id_doctor'] ?></td>
          <td><?= $d['nombreD'] ?></td>
          <td><?= $d['apellido'] ?></td>
          <td><?= $d['telefono'] ?></td>
          <td><?= $d['sexo'] ?></td>
          <td><?= $d['correo_eletronico'] ?></td>
          <td><?= $d['clave'] ?></td>
          <td><?= $d['id_especialidad'] ?></td>
          <td>
            <button class="btn btn-warning btn-sm"
              data-bs-toggle="modal" data-bs-target="#modalEditarDentista"
              data-id="<?= $d['id_doctor'] ?>"
              data-nombre="<?= $d['nombreD'] ?>"
              data-apellido="<?= $d['apellido'] ?>"
              data-telefono="<?= $d['telefono'] ?>"
              data-sexo="<?= $d['sexo'] ?>"
              data-correo="<?= $d['correo_eletronico'] ?>"
              data-especialidad="<?= $d['id_especialidad'] ?>"
              data-clave="<?= $d['clave'] ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-danger btn-sm"
              data-bs-toggle="modal" data-bs-target="#modalEliminarDentista"
              data-id="<?= $d['id_doctor'] ?>">
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
    <p>Total Dentistas: <strong><?= mysqli_num_rows($dentistas) ?></strong></p>
  </div>
</div>

<!-- Modales -->
<!-- Agregar -->
<div class="modal fade" id="modalAgregarDentista" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAgregarDentista" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Agregar Dentista</h5></div>
      <div class="modal-body">
        <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required>
        <input type="text" name="apellido" class="form-control mb-2" placeholder="Apellido" required>
        <input type="text" name="telefono" class="form-control mb-2" placeholder="Teléfono">
        <input type="text" name="sexo" class="form-control mb-2" placeholder="Sexo">
        <input type="email" name="correo" class="form-control mb-2" placeholder="Correo" required>
        <input type="text" name="clave" class="form-control mb-2" placeholder="Contraseña (clave)" required>
        <select name="especialidad" class="form-control mb-2" required>
          <option value="">-- Selecciona Especialidad --</option>
          <?php while($esp = mysqli_fetch_assoc($especialidades)) { ?>
            <option value="<?= $esp['id_especialidad'] ?>"><?= $esp['tipo'] ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Editar -->
<div class="modal fade" id="modalEditarDentista" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEditarDentista" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar Dentista</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_doctor" id="edit-id">
        <input type="text" name="nombre" id="edit-nombre" class="form-control mb-2" placeholder="Nombre">
        <input type="text" name="apellido" id="edit-apellido" class="form-control mb-2" placeholder="Apellido">
        <input type="text" name="telefono" id="edit-telefono" class="form-control mb-2" placeholder="Teléfono">
        <input type="text" name="sexo" id="edit-sexo" class="form-control mb-2" placeholder="Sexo">
        <input type="email" name="correo" id="edit-correo" class="form-control mb-2" placeholder="Correo">
        <input type="text" name="clave" id="edit-clave" class="form-control mb-2" placeholder="Nueva contraseña (clave)">
        <select name="especialidad" id="edit-especialidad" class="form-control mb-2" required>
          <option value="">-- Selecciona Especialidad --</option>
          <?php 
          mysqli_data_seek($especialidades, 0); // reset puntero
          while($esp = mysqli_fetch_assoc($especialidades)) { ?>
            <option value="<?= $esp['id_especialidad'] ?>"><?= $esp['tipo'] ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Eliminar -->
<div class="modal fade" id="modalEliminarDentista" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEliminarDentista" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Eliminar Dentista</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_doctor" id="delete-id">
        <p>¿Seguro que deseas eliminar este dentista?</p>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Rellenar modal editar
  var modalEditar = document.getElementById('modalEditarDentista')
  modalEditar.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget
    document.getElementById('edit-id').value = button.getAttribute('data-id')
    document.getElementById('edit-nombre').value = button.getAttribute('data-nombre')
    document.getElementById('edit-apellido').value = button.getAttribute('data-apellido')
    document.getElementById('edit-telefono').value = button.getAttribute('data-telefono')
    document.getElementById('edit-sexo').value = button.getAttribute('data-sexo')
    document.getElementById('edit-correo').value = button.getAttribute('data-correo')
    document.getElementById('edit-clave').value = button.getAttribute('data-clave')
    document.getElementById('edit-especialidad').value = button.getAttribute('data-especialidad')
  })

  var modalEliminar = document.getElementById('modalEliminarDentista')
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
    setTimeout(() => loadContent('gestionar_dentistas.php'), 1500);
  }

  // AJAX Agregar
  $("#formAgregarDentista").submit(function(e){
    e.preventDefault();
    $.post("crud/dentista_INSERT.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        bootstrap.Modal.getInstance(document.getElementById('modalAgregarDentista')).hide();
        showAndReload("✅ Dentista agregado con éxito");
      } else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // AJAX Editar
  $("#formEditarDentista").submit(function(e){
    e.preventDefault();
    $.post("crud/dentista_UPDATE.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        bootstrap.Modal.getInstance(document.getElementById('modalEditarDentista')).hide();
        showAndReload("✅ Dentista actualizado con éxito");
      } else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // AJAX Eliminar
  $("#formEliminarDentista").submit(function(e){
    e.preventDefault();
    $.post("crud/dentista_DELETE.php", $(this).serialize(), function(resp){
      if(resp.status==="success"){ 
        bootstrap.Modal.getInstance(document.getElementById('modalEliminarDentista')).hide();
        showAndReload("✅ Dentista eliminado con éxito");
      } else showAndReload("❌ Error: "+resp.msg,"danger");
    },"json");
  });

  // Filtros AJAX
  $("#form-filtros-dentistas").submit(function(e){
    e.preventDefault();
    $.get("gestionar_dentistas.php", $(this).serialize(), function(data){
      $("#dynamic-content").html(data);
    });
  });
</script>
