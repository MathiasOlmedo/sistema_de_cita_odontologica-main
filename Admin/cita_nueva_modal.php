<?php
// Admin/cita_nueva_modal.php
session_start();
include_once('../php/conexionDB.php');
include_once('../php/consultas.php');

// Obtener lista de pacientes
$pacientes = mysqli_query($link, "SELECT id_paciente, CONCAT(nombre, ' ', apellido) AS nombre_completo, cedula FROM pacientes ORDER BY nombre ASC");

// Obtener lista de consultas
$consultas = mysqli_query($link, "SELECT id_consultas, tipo FROM consultas ORDER BY tipo ASC");

// Obtener lista de doctores
$doctores = mysqli_query($link, "SELECT id_doctor, CONCAT(nombreD, ' ', apellido) AS nombre FROM doctor ORDER BY nombreD ASC");
?>

<form id="formNuevaCita">
    <div class="modal-body">
        <!-- Selector de paciente -->
        <div class="form-group mb-3">
            <label for="nuevo-paciente"><i class="fa fa-user"></i> Paciente</label>
            <select class="form-control" id="nuevo-paciente" name="id_paciente" required>
                <option value="">-- Seleccione un paciente --</option>
                <?php while ($pac = mysqli_fetch_assoc($pacientes)): ?>
                    <option value="<?= $pac['id_paciente'] ?>">
                        <?= htmlspecialchars($pac['nombre_completo']) ?> - CI: <?= htmlspecialchars($pac['cedula']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small class="text-muted">Si el paciente no está registrado, primero debe agregarlo en "Gestión de Pacientes"</small>
        </div>

        <!-- Fecha de la cita -->
        <div class="form-group mb-3">
            <label for="nuevo-fecha"><i class="fa fa-calendar"></i> Fecha de la cita</label>
            <input type="date" 
                   class="form-control" 
                   id="nuevo-fecha" 
                   name="fecha_cita" 
                   required 
                   min="<?= date('Y-m-d') ?>">
            <small class="text-muted">Solo días hábiles (Lunes a Viernes)</small>
        </div>

         <!-- Doctor asignado -->
        <div class="form-group mb-3">
            <label for="nuevo-doctor"><i class="fa fa-user-md"></i> Doctor</label>
            <select class="form-control" id="nuevo-doctor" name="id_doctor" required>
                <option value="">-- Seleccione un doctor --</option>
                <?php while ($doc = mysqli_fetch_assoc($doctores)): ?>
                    <option value="<?= $doc['id_doctor'] ?>">
                        <?= htmlspecialchars($doc['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>


        <!-- Hora de la cita -->
        <div class="form-group mb-3">
            <label for="nuevo-hora"><i class="fa fa-clock"></i> Seleccione un horario disponible</label>
            <div id="horarios-container-nuevo" class="time-slots-container">
                <div class="loading-indicator">
                    <i class="fa fa-info-circle"></i>
                    <p>Seleccione una fecha para ver horarios disponibles</p>
                </div>
            </div>
            <input type="hidden" name="hora_cita" id="nuevo-hora" required>
        </div>

        <!-- Tipo de consulta -->
        <div class="form-group mb-3">
            <label for="nuevo-consulta"><i class="fa fa-stethoscope"></i> Tipo de consulta</label>
            <select class="form-control" id="nuevo-consulta" name="id_consultas" required>
                <option value="">-- Seleccione tipo de consulta --</option>
                <?php 
                mysqli_data_seek($consultas, 0);
                while ($con = mysqli_fetch_assoc($consultas)): ?>
                    <option value="<?= $con['id_consultas'] ?>">
                        <?= htmlspecialchars($con['tipo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

       
        <!-- Estado inicial -->
        <input type="hidden" name="estado" value="I">
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> Agendar Cita
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
    Cancelar
</button>
    </div>
</form>

<script>
$(function() {
    // Validar fin de semana
    $('#nuevo-fecha').on('change', function() {
        const fecha = new Date(this.value + 'T00:00:00');
        const day = fecha.getUTCDay();
        
        if (day === 0 || day === 6) {
            alert('⚠️ No se permiten citas los fines de semana. Seleccione de Lunes a Viernes.');
            $(this).val('');
            $('#horarios-container-nuevo').html('<div class="loading-indicator"><i class="fa fa-exclamation-triangle"></i><p>Seleccione una fecha válida</p></div>');
        } else {
            cargarHorariosNuevo();
        }
    });

    // Cargar horarios al cambiar doctor
    $('#nuevo-doctor').on('change', cargarHorariosNuevo);

    function cargarHorariosNuevo() {
        const doctor = $('#nuevo-doctor').val();
        const fecha = $('#nuevo-fecha').val();
        const contenedor = $('#horarios-container-nuevo');

        $('#nuevo-hora').val('');

        if (!doctor || !fecha) {
            contenedor.html('<div class="loading-indicator"><i class="fa fa-info-circle"></i><p>Seleccione doctor y fecha</p></div>');
            return;
        }

        contenedor.html('<div class="loading-indicator"><i class="fa fa-spinner fa-spin"></i><p>Cargando horarios...</p></div>');

        $.ajax({
            url: '../ajax/horarios_disponibles.php',
            method: 'GET',
            data: { doctor: doctor, fecha: fecha, _t: new Date().getTime() },
            cache: false,
            dataType: 'json',
            success: function(data) {
                contenedor.empty();

                if (data.length === 0) {
                    contenedor.html('<div class="loading-indicator"><i class="fa fa-calendar-times-o"></i><p>Sin horarios disponibles</p></div>');
                    return;
                }

                data.forEach(function(horario) {
                    const div = $('<div>')
                        .addClass('time-slot')
                        .addClass(horario.disponible ? 'available' : 'occupied')
                        .html('<i class="fa fa-clock-o"></i> ' + horario.hora)
                        .data('hora', horario.hora);

                    if (horario.disponible) {
                        div.on('click', function() {
                            $('.time-slot.available').removeClass('selected');
                            $(this).addClass('selected');
                            $('#nuevo-hora').val($(this).data('hora'));
                        });
                    } else {
                        div.attr('title', 'Horario ocupado');
                    }

                    contenedor.append(div);
                });
            },
            error: function() {
                contenedor.html('<div class="loading-indicator"><i class="fa fa-exclamation-triangle"></i><p>Error al cargar horarios</p></div>');
            }
        });
    }

    // Enviar formulario
    $('#formNuevaCita').on('submit', function(e) {
        e.preventDefault();

        if (!$('#nuevo-hora').val()) {
            alert('⚠️ Por favor seleccione un horario disponible');
            return false;
        }

        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '../crud/citaa_INSERT.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('✅ Cita agendada correctamente');
                    $('#modalNuevaCita').modal('hide');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (response.msg || 'Desconocido'));
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Agendar Cita');
                }
            },
            error: function() {
                alert('❌ Error de conexión al servidor');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Agendar Cita');
            }
        });
    });
});
</script>

<style>
.time-slots-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    max-height: 300px;
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
    transform: scale(1.05);
}

.time-slot.occupied {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.loading-indicator {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}
</style>