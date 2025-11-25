<?php
session_start();
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

$id = $_GET['id'] ?? 0;

// Obtener los datos de esa cita
$row = ConsultarCitas($link, $id);
?>

<!-- SOLO CONTENIDO FUNCIONAL PARA EL MODAL -->
<form action="../crud/realizar_consultasUPDATE.php?accion=UDT" method="POST" autocomplete="off">

    <input type="hidden" name="id" value="<?php echo $row['id_cita']; ?>">

    <div class="p-3 mb-2 bg-primary text-white text-center rounded">
        <strong>Realizar diagnóstico</strong>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title">Diagnóstico</h5>
            <p class="card-text">
                Con el fin de identificar la afección mediante una buena interpretación
                de los síntomas y resultados obtenidos.
            </p>
        </div>
    </div>

    <div class="form-group mb-3">
        <label for="">Descripción</label>
        <input class="form-control" type="text" name="Descripción"
               placeholder="Descripción" required>
    </div>

    <div class="form-group mb-3">
        <label for="">Medicina (opcional)</label>
        <input class="form-control" type="text" name="Medicina"
               placeholder="Medicina opcional">
    </div>

    <div class="text-end mt-4">
        <button class="btn btn-success">
            <i class="far fa-save"></i> Guardar cambios
        </button>
    </div>

</form>
