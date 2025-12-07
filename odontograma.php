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
<title>Odontograma - Perfect Teeth</title>

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

/* ===== Sidebar ===== */
.sidebar{
    background:#fff;
    border-right:0 !important;
    position:fixed; top:0; left:0;
    width:var(--sidebar-w); height:100vh;
    padding:1.25rem 1rem;
    overflow-y:hidden; overflow-x:hidden;
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
.fila-dientes { display: flex; justify-content: center; margin-bottom: 10px; }
.diente { display: flex; flex-direction: column; align-items: center; margin: 2px; }
.diente img { width: 50px; height: auto; cursor: pointer; }
.num-diente { margin-top: 2px; }
.nombre-diente {
    display: inline-block;
    padding: 2px 5px;
    border: 1px solid #555;
    border-radius: 4px;
    font-size: 11px;
    background-color: #f0f0f0;
}

/* Tabla procedimientos */
#tablaProcedimientos th, #tablaProcedimientos td { text-align:center; }
#pacientesTable tbody tr td .btn { white-space: nowrap; }

/* Card inicio odontograma */
#btnIniciarOdontograma .card {
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: var(--radius);
}
#btnIniciarOdontograma .card-body {
    padding: 3rem 2rem;
}
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
        <a href="admin/inicioAdmin.php" class="nav-link"><i class="far fa-calendar-check"></i> Citas pendientes</a>
        <a href="admin/calendar.php" class="nav-link"><i class="far fa-calendar-alt"></i> Calendario</a>
        <a href="admin/historial_medico.php" class="nav-link"><i class="fas fa-tooth"></i> Historial médico</a>
        <a href="odontograma.php" class="nav-link active"><i class="fas fa-tooth"></i> Odontograma</a>
        <a href="Admin/presupuestos_doctor.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Presupuestos</a>
        <a href="reportes_doctor.php" class="nav-link"><i class="fas fa-chart-line"></i> Reportes</a>
        <a href="php/cerrar.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
    </nav>
</aside>

<!-- Main -->
<div class="main">
    <h2>Odontograma</h2>
    <p>Dr. <?= htmlspecialchars($nombreDoctor) ?></p>

    <!-- Botón para iniciar odontograma (aparece si se cierra el modal sin seleccionar) -->
    <div id="btnIniciarOdontograma" class="text-center my-5" style="display:none;">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <i class="fas fa-tooth fa-3x text-primary mb-3"></i>
                <h4>¿Deseas crear un odontograma?</h4>
                <p class="text-muted">Selecciona un paciente para comenzar</p>
                <button id="btnSeleccionarPaciente" class="btn btn-primary btn-lg">
                    <i class=""></i> Seleccionar Paciente
                </button>
            </div>
        </div>
    </div>

    <!-- Odontograma -->
    <div id="odontograma" class="mb-3" style="display:none;">
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

    <!-- Tabla -->
    <div id="tablaProcedimientos" style="display:none;">
        <table class="table table-bordered">
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
        <div class="text-end"><strong>Total: $<span id="totalPrecio">0.00</span></strong></div>
    </div>

    <!-- Botón Terminar -->
    <div class="mt-3">
        <button id="btnTerminar" class="btn btn-primary" style="display:none;">Terminar</button>
    </div>
</div>

<!-- Modal procedimiento -->
<div class="modal fade" id="procedimientoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5>Procedimiento para el diente <span id="dienteSeleccionado"></span></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="formProcedimiento">
          <input type="hidden" id="dienteId">
          <div class="form-group">
            <label>Lado</label>
            <select class="form-control" id="lado">
              <option value="Frontal">Frontal</option><option value="Lateral">Lateral</option>
              <option value="Izquierdo">Izquierdo</option><option value="Derecho">Derecho</option>
              <option value="Anterior">Anterior</option><option value="Posterior">Posterior</option>
            </select>
          </div>
          <div class="form-group">
            <label>Procedimiento</label>
            <select class="form-control" id="procedimiento">
              <option value="Amalgama">Amalgama</option><option value="Caries">Caries</option>
              <option value="Endodoncia">Endodoncia</option><option value="Resina">Resina</option>
              <option value="Implante">Implante</option><option value="Sellante">Sellante</option>
              <option value="Corona">Corona</option><option value="Normal">Normal</option>
            </select>
          </div>
          <div class="form-group">
            <label>Precio</label>
            <input type="number" class="form-control" id="precio" step="0.01" required>
          </div>
          <div class="form-group">
            <label>Observación</label>
            <textarea class="form-control" id="observacion"></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Agregar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Seleccionar Paciente -->
<div class="modal fade" id="seleccionarPacienteModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5><i class=""></i> Seleccionar paciente</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label>Buscar</label>
            <input type="text" id="buscarPaciente" class="form-control" placeholder="Nombre, correo o teléfono">
          </div>
          <div class="col-md-3">
            <label>Sexo</label>
            <select id="filtroSexo" class="form-control">
              <option value="">Todos</option><option value="Femenino">Femenino</option><option value="Masculino">Masculino</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button id="btnBuscarPac" class="btn btn-outline-primary w-100"><i class="fa fa-search"></i> Buscar</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover" id="pacientesTable">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Sexo</th><th>Fecha Nac.</th><th></th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <small id="pacInfo" class="text-muted"></small>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  let selectedPaciente=null;

  // Mostrar modal al iniciar
  $('#seleccionarPacienteModal').modal('show');
  cargarPacientes({reset:true});

  // Detectar cuando se cierra el modal sin seleccionar paciente
  $('#seleccionarPacienteModal').on('hidden.bs.modal', function () {
    if(!selectedPaciente){
      $('#btnIniciarOdontograma').fadeIn();
    }
  });

  // Botón para abrir nuevamente el modal
  $('#btnSeleccionarPaciente').on('click', function(){
    $('#btnIniciarOdontograma').hide();
    $('#seleccionarPacienteModal').modal('show');
  });

  // Buscar pacientes
  function debounce(fn,delay){let t=null;return function(){clearTimeout(t);const ctx=this,args=arguments;t=setTimeout(()=>fn.apply(ctx,args),delay);}}
  function renderPacientes(items){
    const $tb=$('#pacientesTable tbody');$tb.empty();
    items.forEach(p=>{
      const nombre=`${p.nombre} ${p.apellido}`;
      $tb.append(`<tr><td>${nombre}</td><td>${p.correo_electronico}</td><td>${p.telefono}</td><td>${p.sexo}</td><td>${p.fecha_nacimiento}</td>
        <td><button class="btn btn-sm btn-primary btnSelectPaciente" data-id="${p.id_paciente}" data-nombre="${nombre}" data-correo="${p.correo_electronico}" data-telefono="${p.telefono}">Seleccionar</button></td></tr>`);
    });
  }
  function cargarPacientes(){ $.get('php/pacientes_search.php',{q:$('#buscarPaciente').val(),sexo:$('#filtroSexo').val()},function(res){const r=(typeof res==='object')?res:JSON.parse(res);if(r.status==='ok')renderPacientes(r.items);}); }
  $('#btnBuscarPac').on('click',()=>cargarPacientes());
  $('#buscarPaciente').on('keyup',debounce(()=>cargarPacientes(),400));
  $('#filtroSexo').on('change',()=>cargarPacientes());

  // Seleccionar paciente
  $(document).on('click','.btnSelectPaciente',function(){
    selectedPaciente={id:$(this).data('id'),nombre:$(this).data('nombre'),correo:$(this).data('correo'),telefono:$(this).data('telefono')};
    $('#seleccionarPacienteModal').modal('hide');
    $('#btnIniciarOdontograma').hide(); // Ocultar el botón al seleccionar
    $('#odontograma, #tablaProcedimientos, #btnTerminar').fadeIn();
    alert(`Paciente seleccionado: ${selectedPaciente.nombre}`);
  });

  // Modal procedimiento
  $('.odontograma-btn').on('click',function(){
    if(!selectedPaciente){alert('Selecciona primero un paciente.');return;}
    const diente=$(this).data('diente');
    $('#dienteSeleccionado').text(diente);
    $('#dienteId').val(diente);
    $('#procedimientoModal').modal('show');
  });

  $('#formProcedimiento').on('submit',function(e){
    e.preventDefault();
    const diente=$('#dienteId').val(), lado=$('#lado').val(), proc=$('#procedimiento').val(), precio=parseFloat($('#precio').val()||'0'), obs=$('#observacion').val();
    $('#tablaProcedimientos tbody').append(`<tr><td>${diente}</td><td>${lado}</td><td>${proc}</td><td>${precio.toFixed(2)}</td><td>${obs}</td></tr>`);
    let total=parseFloat($('#totalPrecio').text()||'0');total+=precio;$('#totalPrecio').text(total.toFixed(2));
    $('#procedimientoModal').modal('hide');$('#formProcedimiento')[0].reset();
  });

  // Terminar y generar presupuesto
  $('#btnTerminar').on('click',function(){
    if(!selectedPaciente){alert('Selecciona primero un paciente.');return;}
    const procedimientos=[];$('#tablaProcedimientos tbody tr').each(function(){
      procedimientos.push({diente:$(this).find('td:eq(0)').text(),lado:$(this).find('td:eq(1)').text(),procedimiento:$(this).find('td:eq(2)').text(),precio:parseFloat($(this).find('td:eq(3)').text()),observacion:$(this).find('td:eq(4)').text()});
    });
    if(procedimientos.length===0){alert('Agrega al menos un procedimiento.');return;}
    $.ajax({
      url:'php/generar_presupuesto.php',method:'POST',
      data:{paciente_nombre:selectedPaciente.nombre,paciente_correo:selectedPaciente.correo,paciente_telefono:selectedPaciente.telefono,id_paciente:selectedPaciente.id,procedimientos:JSON.stringify(procedimientos)},
      success:function(res){const r=(typeof res==='object')?res:JSON.parse(res);
        if(r.status==='ok'){
          alert('✓ Presupuesto generado correctamente para ' + selectedPaciente.nombre);
          //if(r.pdf_url)window.open(r.pdf_url,'_blank');
          // Limpiar el formulario para crear un nuevo odontograma
          selectedPaciente = null;
          $('#tablaProcedimientos tbody').empty();
          $('#totalPrecio').text('0.00');
          $('#odontograma, #tablaProcedimientos, #btnTerminar').hide();
          $('#btnIniciarOdontograma').fadeIn();
        }
        else alert('Error: '+r.message);
      },
      error:()=>alert('Error al generar presupuesto.')
    });
  });
});
</script>

</div><!-- /app -->
</body>
</html>