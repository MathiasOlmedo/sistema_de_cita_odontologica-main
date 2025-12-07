<?php
// Admin/cita_editar_modal.php
session_start();
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

$id_cita = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_cita === 0) {
    echo '<div class="alert alert-danger">ID de cita inválido</div>';
    exit;
}

// Obtener datos de la cita
$stmt = $link->prepare("
    SELECT c.*, 
           CONCAT(p.nombre, ' ', p.apellido) AS paciente_nombre,
           CONCAT(d.nombreD, ' ', d.apellido) AS doctor_nombre,
           con.tipo AS consulta_tipo
    FROM citas c
    INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
    INNER JOIN doctor d ON c.id_doctor = d.id_doctor
    LEFT JOIN consultas con ON c.id_consultas = con.id_consultas
    WHERE c.id_cita = ?
");
$stmt->bind_param('i', $id_cita);
$stmt->execute();
$result = $stmt->get_result();
$cita = $result->fetch_assoc();
$stmt->close();

if (!$cita) {
    echo '<div class="alert alert-danger">Cita no encontrada</div>';
    exit;
}

// Obtener lista de consultas
$consultas = mysqli_query($link, "SELECT id_consultas, tipo FROM consultas ORDER BY tipo ASC");

// Obtener lista de doctores
$doctores = mysqli_query($link, "SELECT id_doctor, CONCAT(nombreD, ' ', apellido) AS nombre FROM doctor ORDER BY nombreD ASC");
?>

<form id="formEditarCita">
    <input type="hidden" name="id_cita" value="<?= $cita['id_cita'] ?>">
    
    <div class="modal-body">
        <!-- Información del paciente (solo lectura) -->
        <div class="alert alert-info mb-3">
            <strong><i class="fa fa-user"></i> Paciente:</strong> <?= htmlspecialchars($cita['paciente_nombre']) ?>
        </div>

        <!-- Fecha de la cita -->
        <div class="form-group mb-3">
            <label for="edit-fecha"><i class="fa fa-calendar"></i> Fecha de la cita</label>
            <input type="date" 
                   class="form-control" 
                   id="edit-fecha" 
                   name="fecha_cita" 
                   value="<?= $cita['fecha_cita'] ?>" 
                   required 
                   min="<?= date('Y-m-d') ?>">
        </div>

        <!-- Hora de la cita -->
        <div class="form-group mb-3">
            <label for="edit-hora"><i class="fa fa-clock"></i> Hora de la cita</label>
            <input type="time" 
                   class="form-control" 
                   id="edit-hora" 
                   name="hora_cita" 
                   value="<?= substr($cita['hora_cita'], 0, 5) ?>" 
                   required>
        </div>

        <!-- Tipo de consulta -->
        <div class="form-group mb-3">
            <label for="edit-consulta"><i class="fa fa-stethoscope"></i> Tipo de consulta</label>
            <select class="form-control" id="edit-consulta" name="id_consultas" required>
                <?php while ($con = mysqli_fetch_assoc($consultas)): ?>
                    <option value="<?= $con['id_consultas'] ?>" 
                            <?= $cita['id_consultas'] == $con['id_consultas'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($con['tipo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Estado de la cita -->
        <div class="form-group mb-3">
            <label for="edit-estado"><i class="fa fa-info-circle"></i> Estado</label>
            <select class="form-control" id="edit-estado" name="estado" required>
                <option value="I" <?= $cita['estado'] === 'I' ? 'selected' : '' ?>>Pendiente</option>
                <option value="A" <?= $cita['estado'] === 'A' ? 'selected' : '' ?>>Realizada</option>
            </select>
        </div>

        <!-- Diagnóstico (si está realizada) -->
        <div id="diagnostico-container" style="display: <?= $cita['estado'] === 'A' ? 'block' : 'none' ?>;">
            <div class="form-group mb-3">
                <label for="edit-descripcion"><i class="fa fa-file-text"></i> Diagnóstico</label>
                <textarea class="form-control" 
                          id="edit-descripcion" 
                          name="descripcion" 
                          rows="3" 
                          placeholder="Descripción del diagnóstico"><?= htmlspecialchars($cita['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="form-group mb-3">
                <label for="edit-medicina"><i class="fa fa-medkit"></i> Medicina prescrita (opcional)</label>
                <input type="text" 
                       class="form-control" 
                       id="edit-medicina" 
                       name="medicina" 
                       placeholder="Medicamento recomendado">
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="btnCancelarCita">
            <i class="fa fa-trash"></i> Cancelar Cita
        </button>
        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> Guardar Cambios
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cerrar
        </button>
    </div>
</form>

<script>
$(function() {
    // Mostrar/ocultar diagnóstico según estado
    $('#edit-estado').on('change', function() {
        if ($(this).val() === 'A') {
            $('#diagnostico-container').slideDown();
        } else {
            $('#diagnostico-container').slideUp();
        }
    });

    // Enviar formulario de edición
    $('#formEditarCita').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '../crud/cita_UPDATE.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('✅ Cita actualizada correctamente');
                    $('#modalEditarCita').modal('hide');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (response.msg || 'Desconocido'));
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Cambios');
                }
            },
            error: function() {
                alert('❌ Error de conexión al servidor');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar Cambios');
            }
        });
    });

    // Cancelar cita
    $('#btnCancelarCita').on('click', function() {
        if (!confirm('⚠️ ¿Está seguro de cancelar esta cita? Esta acción no se puede deshacer.')) {
            return;
        }

        $.ajax({
            url: '../crud/cita_DELETE.php',
            method: 'POST',
            data: { id_cita: <?= $id_cita ?> },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('✅ Cita cancelada correctamente');
                    $('#modalEditarCita').modal('hide');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (response.msg || 'Desconocido'));
                }
            },
            error: function() {
                alert('❌ Error de conexión al servidor');
            }
        });
    });
});
</script>