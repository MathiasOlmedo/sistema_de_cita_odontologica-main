<?php
include_once("php/conexionDB.php");
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id_doctor'])) {
    header("location: login.php");
    exit();
}

$id_doctor = (int)$_SESSION['id_doctor'];
$nombreDoctor = "Doctor";
$sexoDoctor = null;

$sqlDoc = "SELECT nombreD, apellido, sexo, correo_eletronico FROM doctor WHERE id_doctor = $id_doctor LIMIT 1";
$resDoc = mysqli_query($link, $sqlDoc);
if ($row = mysqli_fetch_assoc($resDoc)) {
    $nombreDoctor = $row['nombreD'] . " " . $row['apellido'];
    $sexoDoctor = $row['sexo'];
    $correoDoctor = $row['correo_eletronico'];
}

$AVATAR_IMG = ($sexoDoctor === 'Femenino') ? 'src/img/odontologa.png' : 'src/img/odontologo.png';
$SIDEBAR_ACTIVE = 'odontograma';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Odontograma Libre</title>

<!-- Bootstrap y Font Awesome -->
<link rel="stylesheet" href="src/css/lib/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="src/css/lib/fontawesome/css/all.min.css">

<!-- jQuery y Bootstrap JS -->
<script src="src/js/jquery.js"></script>
<script src="src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

<style>
:root{
    --brand:#0d6efd;
    --brand-100:#e7f1ff;
    --surface:#f8f9fa;
    --text:#212529;
    --sidebar-w:260px;
    --maxw:1200px;
    --radius:12px;
}
body {
    margin:0; background:var(--surface); color:var(--text);
    font-family: Arial, sans-serif;
}

/* ===== Sidebar unificado ===== */
.sidebar{
    background:#fff;
    border-right:0 !important;
    position:fixed; top:0; left:0;
    width:var(--sidebar-w); height:100vh;
    padding:1.25rem 1rem; overflow-y:hidden; overflow-x:hidden;
}
.brand{ display:flex; align-items:center; gap:.75rem; padding:.5rem .75rem; border-radius:.75rem; }
.brand-title{ margin:0; font-weight:700; letter-spacing:.3px; color:var(--brand); font-size:1.05rem; }

.profile{ text-align:center; margin:1rem 0 1.25rem; }
.profile img{ width:96px; height:96px; object-fit:cover; }
.profile .name{ margin:.75rem 0 .25rem; font-weight:600; }

.nav-menu .nav-link{
    border-radius:.6rem; color:#495057;
    display:flex; align-items:center; gap:.6rem;
    padding:.6rem .75rem; text-decoration:none;
}
.nav-menu .nav-link:hover,
.nav-menu .nav-link.active{
    background:var(--brand-100);
    color:var(--brand);
    text-decoration:none; font-weight:600;
}

/* ===== Main ===== */
.main{ margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; padding:1.25rem; }

/* Odontograma */
#odontograma {
    display: flex; flex-direction: column; align-items: center; margin-bottom: 20px;
}
.fila-dientes {
    display: flex; justify-content: center; margin-bottom: 10px;
}
.diente {
    display: flex; flex-direction: column; align-items: center; margin: 2px;
}
.diente img {
    width: 50px; height: auto; cursor: pointer;
}
.num-diente {
    margin-top: 2px;
}
.nombre-diente {
    display: inline-block;
    padding: 2px 5px;
    border: 1px solid #555;
    border-radius: 4px;
    font-size: 11px;
    background-color: #f0f0f0;
}

/* Tabla de procedimientos */
#tablaProcedimientos th, #tablaProcedimientos td { text-align:center; }

#pacientesTable tbody tr td .btn { white-space: nowrap; }
</style>
</head>

<body>

<div class="app">

<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand mb-2">
        <img src="src/img/logo.png" alt="Perfect Teeth" width="32" height="32">
        <h1 class="brand-title">Perfect Teeth</h1>
    </div>

    <div class="profile">
        <img src="<?= $AVATAR_IMG ?>" class="rounded-circle" alt="Perfil">
        <div class="name"><?= htmlspecialchars($nombreDoctor) ?></div>
        <div class="text-muted small">Panel de odontólogo</div>
    </div>

    <nav class="nav-menu">
        <a href="admin/inicioAdmin.php" class="nav-link <?php if($SIDEBAR_ACTIVE==='citas') echo 'active'; ?>"><i class="far fa-calendar-check"></i> Citas pendientes</a>
        <a href="admin/calendar.php" class="nav-link <?php if($SIDEBAR_ACTIVE==='calendario') echo 'active'; ?>"><i class="far fa-calendar-alt"></i> Calendario</a>
        <a href="odontograma.php" class="nav-link <?php if($SIDEBAR_ACTIVE==='odontograma') echo 'active'; ?>"><i class="fas fa-tooth"></i> Odontograma</a>
        <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='presupuestos'?'active':''); ?>" href="Admin/presupuestos_doctor.php">
        <i class="fas fa-file-invoice-dollar"></i><span>Presupuestos</span>
        </a>
         <a class="nav-link <?php echo ($SIDEBAR_ACTIVE==='presupuestos'?'active':''); ?>" href="reportes_doctor.php">
    <i class="fas fa-file-invoice-dollar"></i><span>Reportes</span>
  </a>
        <a href="php/cerrar.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
    </nav>
</aside>

<!-- Main -->
<div class="main">
    <h2>Odontograma Libre</h2>
    <p>Dr. <?= htmlspecialchars($nombreDoctor) ?></p>

    <!-- Odontograma -->
    <div id="odontograma" class="mb-3">
        <!-- Dientes superiores -->
        <div class="fila-dientes">
            <?php
            $superiores = [
                "18"=>"Molar 3 sup","17"=>"Molar 2 sup","16"=>"Molar 1 sup","15"=>"Premolar 2 sup",
                "14"=>"Premolar 1 sup","13"=>"Canino sup","12"=>"Inc. lateral sup","11"=>"Inc. central sup",
                "21"=>"Inc. central sup","22"=>"Inc. lateral sup","23"=>"Canino sup","24"=>"Premolar 1 sup",
                "25"=>"Premolar 2 sup","26"=>"Molar 1 sup","27"=>"Molar 2 sup","28"=>"Molar 3 sup"
            ];
            foreach($superiores as $diente=>$nombre){
                echo '<div class="diente">';
                echo '<img src="src/img/dentadura-sup-'.$diente.'.png" alt="'.$nombre.'" class="odontograma-btn" data-diente="'.$diente.'">';
                echo '<div class="num-diente"><span class="nombre-diente">'.$nombre.'</span></div>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Dientes inferiores -->
        <div class="fila-dientes">
            <?php
            $inferiores = [
                "48"=>"Molar 3 inf","47"=>"Molar 2 inf","46"=>"Molar 1 inf","45"=>"Premolar 2 inf",
                "44"=>"Premolar 1 inf","43"=>"Canino inf","42"=>"Inc. lateral inf","41"=>"Inc. central inf",
                "31"=>"Inc. central inf","32"=>"Inc. lateral inf","33"=>"Canino inf","34"=>"Premolar 1 inf",
                "35"=>"Premolar 2 inf","36"=>"Molar 1 inf","37"=>"Molar 2 inf","38"=>"Molar 3 inf"
            ];
            foreach($inferiores as $diente=>$nombre){
                echo '<div class="diente">';
                echo '<img src="src/img/dentadura-inf-'.$diente.'.png" alt="'.$nombre.'" class="odontograma-btn" data-diente="'.$diente.'">';
                echo '<div class="num-diente"><span class="nombre-diente">'.$nombre.'</span></div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Tabla de resumen -->
    <table class="table table-bordered" id="tablaProcedimientos">
        <thead>
            <tr>
                <th>Diente</th>
                <th>Lado</th>
                <th>Procedimiento</th>
                <th>Precio</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div class="text-right">
        <strong>Total: $<span id="totalPrecio">0.00</span></strong>
    </div>

    <!-- Botones -->
    <div class="mt-3">
        
        <button id="btnGenerarPresupuesto" class="btn btn-primary ml-2">Generar Presupuesto (PDF)</button>
    </div>
</div><!-- /main -->

<!-- Modal procedimiento -->
<div class="modal fade" id="procedimientoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Procedimiento para el Diente <span id="dienteSeleccionado"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formProcedimiento">
                    <input type="hidden" id="dienteId">
                    <div class="form-group">
                        <label for="lado">Lado del diente</label>
                        <select class="form-control" id="lado">
                            <option value="Frontal">Frontal</option>
                            <option value="Lateral">Lateral</option>
                            <option value="Izquierdo">Izquierdo</option>
                            <option value="Derecho">Derecho</option>
                            <option value="Anterior">Anterior</option>
                            <option value="Posterior">Posterior</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="procedimiento">Procedimiento</label>
                        <select class="form-control" id="procedimiento">
                            <option value="Amalgama">Amalgama</option>
                            <option value="Caries">Caries</option>
                            <option value="Endodoncia">Endodoncia</option>
                            <option value="Resina">Resina</option>
                            <option value="Implante">Implante</option>
                            <option value="Sellante">Sellante</option>
                            <option value="Corona">Corona</option>
                            <option value="Normal">Normal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio</label>
                        <input type="number" class="form-control" id="precio" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="observacion">Observación</label>
                        <textarea class="form-control" id="observacion"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Seleccionar Paciente -->
<div class="modal fade" id="seleccionarPacienteModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-user-injured"></i> Seleccionar paciente</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label">Buscar</label>
            <input type="text" id="buscarPaciente" class="form-control" placeholder="Nombre, apellido, correo o teléfono">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sexo</label>
            <select id="filtroSexo" class="form-control">
              <option value="">Todos</option>
              <option value="Femenino">Femenino</option>
              <option value="Masculino">Masculino</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button id="btnBuscarPac" class="btn btn-outline-primary w-100"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover" id="pacientesTable">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Sexo</th>
                <th>Fecha Nac.</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <!-- filas dinámicas -->
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted" id="pacInfo"></small>
          <div>
            <button id="btnMasPac" class="btn btn-light">Más resultados</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
    // Mostrar modal al hacer clic en un diente
    $('.odontograma-btn').on('click', function(){
        const diente = $(this).data('diente');
        $('#dienteSeleccionado').text(diente);
        $('#dienteId').val(diente);
        $('#procedimientoModal').modal('show');
    });

    // Agregar procedimiento a la tabla
    $('#formProcedimiento').on('submit', function(e){
        e.preventDefault();
        const diente = $('#dienteId').val();
        const lado = $('#lado').val();
        const procedimiento = $('#procedimiento').val();
        const precio = parseFloat($('#precio').val() || '0');
        const observacion = $('#observacion').val();

        $('#tablaProcedimientos tbody').append(`
            <tr>
                <td>${diente}</td>
                <td>${lado}</td>
                <td>${procedimiento}</td>
                <td>${precio.toFixed(2)}</td>
                <td>${observacion}</td>
            </tr>
        `);

        let total = parseFloat($('#totalPrecio').text() || '0');
        total += precio;
        $('#totalPrecio').text(total.toFixed(2));

        $('#procedimientoModal').modal('hide');
        $('#formProcedimiento')[0].reset();
    });

    // Guardar resumen (existente)
    $('#btnGuardarResumen').on('click', function(){
        const procedimientos = [];
        $('#tablaProcedimientos tbody tr').each(function(){
            procedimientos.push({
                diente: $(this).find('td:eq(0)').text(),
                lado: $(this).find('td:eq(1)').text(),
                procedimiento: $(this).find('td:eq(2)').text(),
                precio: parseFloat($(this).find('td:eq(3)').text()),
                observacion: $(this).find('td:eq(4)').text()
            });
        });

        if(procedimientos.length === 0){
            alert('No hay procedimientos para guardar.');
            return;
        }

        $.ajax({
            url: 'php/guardar_resumen.php',
            method: 'POST',
            data: { procedimientos: JSON.stringify(procedimientos) },
            success: function(res){
                try{
                    const r = (typeof res === 'object') ? res : JSON.parse(res);
                    if(r.status === 'ok'){
                        alert('Resumen guardado correctamente');
                        $('#tablaProcedimientos tbody').empty();
                        $('#totalPrecio').text('0.00');
                    } else {
                        alert('Error: ' + r.message);
                    }
                }catch(e){
                    alert('Error inesperado al guardar.');
                }
            },
            error: function(){ alert('Error de conexión con el servidor.'); }
        });
    });

    /* ===== NUEVO: Selección de Paciente para generar presupuesto ===== */

    // Estado de paginación
    let pac_next_offset = 0;
    let pac_last_query = '';
    let pac_last_sexo  = '';

    function renderPacientes(items, append=false, total=0){
        const $tb = $('#pacientesTable tbody');
        if(!append) $tb.empty();
        items.forEach(p => {
            const nombreCompleto = `${p.nombre} ${p.apellido}`;
            $tb.append(`
              <tr>
                <td>${nombreCompleto}</td>
                <td>${p.correo_electronico}</td>
                <td>${p.telefono}</td>
                <td>${p.sexo}</td>
                <td>${p.fecha_nacimiento ?? ''}</td>
                <td class="text-end">
                  <button class="btn btn-sm btn-primary btnSelectPaciente"
                          data-id="${p.id_paciente}"
                          data-nombre="${nombreCompleto.replace(/"/g,'&quot;')}"
                          data-correo="${(p.correo_electronico||'').replace(/"/g,'&quot;')}"
                          data-telefono="${(p.telefono||'').replace(/"/g,'&quot;')}">
                    Seleccionar
                  </button>
                </td>
              </tr>
            `);
        });
        $('#pacInfo').text(total ? `Resultados: ${total}` : '');
    }

    function cargarPacientes({reset=false} = {}){
        const q = $('#buscarPaciente').val().trim();
        const sexo = $('#filtroSexo').val();

        if(reset){
            pac_next_offset = 0;
            pac_last_query = q;
            pac_last_sexo = sexo;
        }

        $('#btnMasPac').prop('disabled', true).text('Cargando...');
        $.ajax({
            url: 'php/pacientes_search.php',
            method: 'GET',
            data: { q: pac_last_query, sexo: pac_last_sexo, limit: 25, offset: pac_next_offset },
            success: function(res){
                try{
                    const r = (typeof res === 'object') ? res : JSON.parse(res);
                    if(r.status === 'ok'){
                        renderPacientes(r.items, /*append=*/pac_next_offset>0, r.total);
                        pac_next_offset = r.next_offset ?? null;
                        if(pac_next_offset !== null){
                            $('#btnMasPac').prop('disabled', false).text('Más resultados');
                        } else {
                            $('#btnMasPac').prop('disabled', true).text('No hay más');
                        }
                    } else {
                        alert('No se pudo cargar pacientes.');
                    }
                }catch(e){
                    console.error(e);
                    alert('Error al interpretar los pacientes.');
                }
            },
            error: function(){
                alert('Error de conexión al cargar pacientes.');
            }
        });
    }

    // Debounce simple para el buscador
    function debounce(fn, delay){
        let t = null;
        return function(){
            clearTimeout(t);
            const ctx = this, args = arguments;
            t = setTimeout(() => fn.apply(ctx, args), delay);
        }
    }

    // Abrir modal y cargar lista inicial
    $('#btnGenerarPresupuesto').on('click', function () {
        const tieneFilas = $('#tablaProcedimientos tbody tr').length > 0;
        if (!tieneFilas) {
            alert('Agrega al menos un procedimiento antes de generar el presupuesto.');
            return;
        }
        $('#seleccionarPacienteModal').modal('show');
        // carga inicial (reset)
        cargarPacientes({reset:true});
    });

    // Buscar
    $('#btnBuscarPac').on('click', function(){
        cargarPacientes({reset:true});
    });
    $('#buscarPaciente').on('keyup', debounce(function(){
        cargarPacientes({reset:true});
    }, 400));
    $('#filtroSexo').on('change', function(){
        cargarPacientes({reset:true});
    });

    // Más resultados
    $('#btnMasPac').on('click', function(){
        if (pac_next_offset !== null) cargarPacientes({reset:false});
    });

    // Seleccionar un paciente y generar presupuesto
    $(document).on('click', '.btnSelectPaciente', function(){
        const paciente = {
            nombre_completo: $(this).data('nombre') || '',
            correo: $(this).data('correo') || '',
            telefono: $(this).data('telefono') || ''
        };

        // Recolectar procedimientos
        const procedimientos = [];
        $('#tablaProcedimientos tbody tr').each(function(){
            procedimientos.push({
                diente: $(this).find('td:eq(0)').text(),
                lado: $(this).find('td:eq(1)').text(),
                procedimiento: $(this).find('td:eq(2)').text(),
                precio: parseFloat($(this).find('td:eq(3)').text()),
                observacion: $(this).find('td:eq(4)').text()
            });
        });

        const payload = {
            paciente_nombre: paciente.nombre_completo,
            paciente_correo: paciente.correo,
            paciente_telefono: paciente.telefono,
            paciente_documento: '', // no existe en la tabla, lo dejamos vacío
            procedimientos: JSON.stringify(procedimientos)
        };

        // Llamar al mismo endpoint de antes
        $.ajax({
            url: 'php/generar_presupuesto.php',
            method: 'POST',
            data: payload,
            success: function (res) {
              try {
                const r = (typeof res === 'object') ? res : JSON.parse(res);
                if (r.status === 'ok') {
                  $('#seleccionarPacienteModal').modal('hide');

                  if (r.pdf_url) window.open(r.pdf_url, '_blank');

                  if (confirm('Presupuesto generado. ¿Ir a la vista de Secretaría?')) {
                    window.location.href = 'secretaria/presupuestos.php';
                  } else {
                    alert('También puedes abrirlo desde Secretaría cuando gustes.');
                  }
                } else {
                  if (r.html_fallback) {
                    const w = window.open('', '_blank');
                    w.document.open(); w.document.write(r.html_fallback); w.document.close();
                  } else {
                    alert('No se pudo generar el PDF: ' + r.message);
                  }
                }
              } catch (e) {
                console.error(e);
                alert('Respuesta inesperada del servidor.');
              }
            },
            error: function () {
              alert('Error de conexión con el servidor.');
            }
        });
    });

});
</script>

</div><!-- /app -->
</body>
</html>
