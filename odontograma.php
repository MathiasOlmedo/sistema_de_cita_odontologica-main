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
<meta name="viewport" content="width=device-width, initial-scale=1">
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
    --brand-hover:#0b5ed7;
    --brand-100:#e7f1ff;
    --brand-50:#f0f7ff;
    --surface:#f5f7fa;
    --text:#212529;
    --text-muted:#6c757d;
    --sidebar-w:240px;
    --maxw:1600px;
    --radius:10px;
    --transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
*{ box-sizing:border-box; margin:0; padding:0; }
body {
    margin:0; 
    background:var(--surface); 
    color:var(--text);
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
}

/* ===== Sidebar mejorado ===== */
.sidebar{
    background:linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
    border-right:1px solid rgba(0,0,0,.05);
    box-shadow:2px 0 12px rgba(0,0,0,.03);
    position:fixed; top:0; left:0;
    width:var(--sidebar-w); height:100vh;
    padding:1rem 0.75rem;
    overflow-y:auto;
    overflow-x:hidden;
    z-index:1030;
    transition:var(--transition);
}
.sidebar::-webkit-scrollbar{ width:4px; }
.sidebar::-webkit-scrollbar-track{ background:transparent; }
.sidebar::-webkit-scrollbar-thumb{ background:rgba(0,0,0,.1); border-radius:2px; }
.sidebar::-webkit-scrollbar-thumb:hover{ background:rgba(0,0,0,.2); }

.brand{ 
    display:flex; align-items:center; gap:.65rem; 
    padding:.75rem .65rem; border-radius:var(--radius);
    margin-bottom:1rem;
    transition:var(--transition);
    cursor:pointer;
}
.brand:hover{ background:var(--brand-50); transform:translateX(2px); }
.brand img{ transition:var(--transition); }
.brand:hover img{ transform:scale(1.05); }
.brand-title{ margin:0; font-weight:700; letter-spacing:.2px; color:var(--brand); font-size:1rem; }

.profile{ 
    text-align:center; 
    margin:0.75rem 0 1rem;
    padding:0.75rem;
    background:var(--brand-50);
    border-radius:var(--radius);
    transition:var(--transition);
}
.profile:hover{ background:var(--brand-100); box-shadow:0 2px 8px rgba(13,110,253,.1); }
.profile img{ 
    width:80px; height:80px; 
    object-fit:cover;
    border:3px solid #fff;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
    transition:var(--transition);
}
.profile:hover img{ transform:scale(1.05); box-shadow:0 4px 12px rgba(0,0,0,.15); }
.profile .name{ margin:.5rem 0 .2rem; font-weight:600; font-size:0.95rem; }

.nav-menu{ display:flex; flex-direction:column; gap:0.25rem; }
.nav-menu .nav-link{
    border-radius:var(--radius);
    color:var(--text);
    display:flex; align-items:center; gap:.65rem;
    padding:.7rem .75rem;
    text-decoration:none;
    font-size:0.9rem;
    transition:var(--transition);
    position:relative;
    margin:0 0.25rem;
}
.nav-menu .nav-link i{
    width:20px;
    text-align:center;
    transition:var(--transition);
}
.nav-menu .nav-link:hover{
    background:var(--brand-100);
    color:var(--brand);
    text-decoration:none;
    font-weight:600;
    transform:translateX(4px);
    box-shadow:0 2px 6px rgba(13,110,253,.15);
}
.nav-menu .nav-link:hover i{
    transform:scale(1.15);
    color:var(--brand);
}
.nav-menu .nav-link.active{
    background:linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%);
    color:#fff;
    font-weight:600;
    box-shadow:0 4px 12px rgba(13,110,253,.3);
}
.nav-menu .nav-link.active i{ color:#fff; }
.nav-menu .nav-link.active::before{
    content:'';
    position:absolute;
    left:-0.75rem;
    top:50%;
    transform:translateY(-50%);
    width:4px;
    height:60%;
    background:var(--brand);
    border-radius:0 4px 4px 0;
}

/* ===== Main mejorado ===== */
.main{ 
    margin-left:var(--sidebar-w); 
    min-height:100vh; 
    display:flex; 
    flex-direction:column; 
    padding:2rem;
    transition:var(--transition);
}
.main .container,
.main .card,
.main .table-responsive{
    max-width:var(--maxw);
    margin:0 auto;
    width:100%;
}

/* ===== Topbar ===== */
.topbar{
    background:#fff;
    border-bottom:1px solid rgba(0,0,0,.06);
    padding:1rem 0 1rem 0;
    margin-bottom:1.5rem;
    margin-left:-2rem;
    margin-right:-2rem;
    margin-top:-2rem;
    padding-left:2rem;
    padding-right:2rem;
    box-shadow:0 2px 8px rgba(0,0,0,.03);
}
.topbar h2{
    margin:0;
    font-weight:700;
    font-size:1.5rem;
    color:var(--brand);
}
.topbar p{
    margin:0.25rem 0 0 0;
    color:var(--text-muted);
    font-size:0.95rem;
}

/* ===== Cards mejoradas ===== */
.card-section{
    background:#fff;
    border-radius:var(--radius);
    border:1px solid rgba(0,0,0,.06);
    box-shadow:0 4px 16px rgba(0,0,0,.08);
    margin-bottom:2rem;
    overflow:hidden;
    transition:var(--transition);
}
.card-section:hover{
    box-shadow:0 8px 24px rgba(0,0,0,.12);
    transform:translateY(-2px);
}

.card-header-custom{
    padding:1.25rem 1.75rem;
    background:linear-gradient(135deg, #fff 0%, #fafbfc 100%);
    border-bottom:1px solid rgba(0,0,0,.06);
    display:flex;
    align-items:center;
    gap:0.75rem;
}
.card-header-custom h3{
    margin:0;
    font-weight:700;
    font-size:1.15rem;
    color:var(--brand);
    transition:var(--transition);
}
.card-section:hover .card-header-custom h3{
    color:var(--brand-hover);
}
.card-header-custom i{
    font-size:1.25rem;
    color:var(--brand);
    transition:var(--transition);
}
.card-section:hover .card-header-custom i{
    transform:scale(1.1);
}

.card-body-custom{
    padding:1.75rem;
}

/* ===== Odontograma ===== */
#odontograma {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}
.filas-container{
    display:flex;
    flex-direction:column;
    gap:1.5rem;
    width:100%;
}
.fila-label{
    text-align:center;
    font-weight:600;
    color:var(--brand);
    font-size:0.95rem;
    margin-bottom:0.5rem;
}
.fila-dientes { 
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding:1rem;
    background:linear-gradient(135deg, rgba(247,248,250,0.5) 0%, rgba(240,247,255,0.3) 100%);
    border-radius:var(--radius);
    border:1px dashed rgba(13,110,253,.15);
}
.diente { 
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    transition:var(--transition);
}
.diente img { 
    width: 50px;
    height: auto;
    cursor: pointer;
    transition:var(--transition);
    filter:drop-shadow(0 2px 4px rgba(0,0,0,.1));
}
.diente img:hover {
    transform:scale(1.15);
    filter:drop-shadow(0 4px 8px rgba(13,110,253,.3));
}
.num-diente { 
    margin-top: 0px;
    text-align:center;
}
.nombre-diente {
    display: inline-block;
    padding: 4px 8px;
    border: 1px solid rgba(13,110,253,.25);
    border-radius: var(--radius);
    font-size: 11px;
    background-color: var(--brand-50);
    color:var(--brand);
    font-weight:500;
    transition:var(--transition);
}
.diente:hover .nombre-diente{
    background-color:var(--brand-100);
    border-color:var(--brand);
}

/* ===== Tabla procedimientos ===== */
.tabla-card{
    background:#fff;
    border-radius:var(--radius);
    border:1px solid rgba(0,0,0,.06);
    box-shadow:0 4px 16px rgba(0,0,0,.08);
    overflow:hidden;
    transition:var(--transition);
}
.tabla-card:hover{
    box-shadow:0 8px 24px rgba(0,0,0,.12);
    transform:translateY(-2px);
}

#tablaProcedimientos{
    width:100%;
    margin-bottom:0;
}
#tablaProcedimientos thead{
    background:linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%);
}
#tablaProcedimientos th{
    color:#fff;
    font-weight:600;
    padding:1rem;
    text-align:center;
    border:0;
}
#tablaProcedimientos tbody tr{
    border-bottom:1px solid rgba(0,0,0,.06);
    transition:var(--transition);
}
#tablaProcedimientos tbody tr:hover{
    background-color:var(--brand-50);
}
#tablaProcedimientos td{
    text-align:center;
    padding:0.75rem 1rem;
    color:var(--text);
}

.tabla-footer{
    padding:1.5rem 1.75rem;
    background:linear-gradient(135deg, #fff 0%, #fafbfc 100%);
    border-top:1px solid rgba(0,0,0,.06);
    text-align:right;
}
.tabla-footer strong{
    font-size:1.1rem;
    color:var(--brand);
}

/* ===== Botón Terminar ===== */
.btn-terminar-container{
    display:flex;
    gap:1rem;
    justify-content:flex-end;
    margin-top:2rem;
}
#btnTerminar{
    background:linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%);
    border:0;
    color:#fff;
    font-weight:600;
    padding:0.75rem 2rem;
    border-radius:var(--radius);
    transition:var(--transition);
    box-shadow:0 4px 12px rgba(13,110,253,.3);
}
#btnTerminar:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(13,110,253,.4);
    color:#fff;
    text-decoration:none;
}

/* ===== Modales mejorados ===== */
.modal-content{
    border:1px solid rgba(0,0,0,.06);
    border-radius:var(--radius);
    box-shadow:0 8px 24px rgba(0,0,0,.12);
}
.modal-header{
    background:linear-gradient(135deg, var(--brand-50) 0%, #fff 100%);
    border-bottom:1px solid rgba(0,0,0,.06);
    padding:1.25rem 1.75rem;
}
.modal-header h5{
    color:var(--brand);
    font-weight:700;
}
.modal-body{
    padding:1.75rem;
}
.form-group label{
    font-weight:600;
    color:var(--text);
    margin-bottom:0.5rem;
    font-size:0.95rem;
}
.form-control{
    border:1px solid rgba(0,0,0,.1);
    border-radius:var(--radius);
    padding:0.75rem;
    transition:var(--transition);
    width: 100%;
    min-width: 120px;
}
.form-control:focus{
    border-color:var(--brand);
    box-shadow:0 0 0 0.2rem var(--brand-50);
}
.modal-footer{
    border-top:1px solid rgba(0,0,0,.06);
    padding:1.25rem 1.75rem;
    background:#f5f7fa;
}

/* ===== Estilos específicos para filtros ===== */
#filtroSexo {
    min-width: 130px;
    width: 100%;
}

#filtroSexo option {
    padding: 8px 12px;
    background: white;
    color: var(--text);
}

.row.g-2.mb-3 {
    align-items: flex-end;
}

.row.g-2.mb-3 .col-md-3 {
    display: flex;
    flex-direction: column;
}

.row.g-2.mb-3 label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text);
    font-size: 0.9rem;
}

/* Estilo para los formularios en modales */
.modal-body .form-group {
    margin-bottom: 1rem;
}

/* ===== Responsive ===== */
@media (max-width:992px){
    :root{ --sidebar-w:220px; }
    .sidebar{ padding:0.75rem 0.5rem; }
    .main{ padding:1.5rem 1rem; }
    .topbar{ margin-left:-1rem; margin-right:-1rem; padding-left:1rem; padding-right:1rem; }
}
@media (max-width:575.98px){
    :root{ --sidebar-w:200px; }
    .main{ padding:1rem 0.75rem; }
    .topbar{ margin-left:-0.75rem; margin-right:-0.75rem; padding-left:0.75rem; padding-right:0.75rem; }
    .fila-dientes { padding:0.75rem; gap:0.5rem; }
    .card-header-custom{ padding:1rem 1.25rem; }
    .card-body-custom{ padding:1.25rem; }
    .diente img { width: 40px; }
    .nombre-diente { font-size: 10px; padding:3px 6px; }
    .btn-terminar-container{ flex-direction:column; }
    #btnTerminar{ width:100%; }
    
    /* Ajustes específicos para filtros en móviles */
    .row.g-2.mb-3 .col-md-3 {
        margin-bottom: 1rem;
    }
    
    #filtroSexo {
        min-width: 100%;
    }
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
        <a href="Admin/inicioAdmin.php" class="nav-link"><i class="far fa-calendar-check"></i> Citas pendientes</a>
        <a href="Admin/calendar.php" class="nav-link"><i class="far fa-calendar-alt"></i> Calendario</a>
        <a href="odontograma.php" class="nav-link active"><i class="fas fa-tooth"></i> Odontograma</a>
        <a href="Admin/presupuestos_doctor.php" class="nav-link"><i class="fas fa-file-invoice-dollar"></i> Presupuestos</a>
        <a href="Admin/reportes_doctor.php" class="nav-link"><i class="fas fa-chart-line"></i> Reportes</a>
        <a href="php/cerrar.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
    </nav>
</aside>

<!-- Main -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <h2><i class="fas fa-tooth"></i> Odontograma</h2>
        <p>Dr. <?= htmlspecialchars($nombreDoctor) ?></p>
    </div>

    <div class="container">
        <!-- Tarjeta Odontograma -->
        <div class="card-section" id="odontogramCard" style="display:none;">
            <div class="card-header-custom">
                <i class="fas fa-smile"></i>
                <h3>Gráfico Dental</h3>
            </div>
            <div class="card-body-custom">
                <div id="odontograma">
                    <!-- Dientes superiores -->
                    <div class="filas-container">
                        <div>
                            <div class="fila-label">Dientes Superiores</div>
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
                        </div>

                        <!-- Dientes inferiores -->
                        <div>
                            <div class="fila-label">Dientes Inferiores</div>
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta Procedimientos -->
        <div class="tabla-card" id="tablaProcedimientosCard" style="display:none;">
            <table id="tablaProcedimientos">
                <thead>
                    <tr>
                        <th>Diente</th>
                        <th>Lado</th>
                        <th>Procedimiento</th>
                        <th>Precio</th>
                        <th>Observación</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="tabla-footer">
                <strong>Total: Gs. <span id="totalPrecio">0</span></strong>
            </div>
        </div>

        <!-- Botón Terminar -->
        <div class="btn-terminar-container">
            <button id="btnTerminar" class="btn btn-primary" style="display:none;">Terminar y Generar Presupuesto</button>
        </div>
        
        <!-- Toast container -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
            <div id="toastContainer"></div>
        </div>
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
              <option value="Frontal">Frontal</option>
              <option value="Lateral">Lateral</option>
              <option value="Izquierdo">Izquierdo</option>
              <option value="Derecho">Derecho</option>
              <option value="Anterior">Anterior</option>
              <option value="Posterior">Posterior</option>
            </select>
          </div>
          <div class="form-group">
            <label>Procedimiento</label>
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
            <label>Precio</label>
            <input type="number" class="form-control" id="precio" step="0.01" required>
          </div>
          <div class="form-group">
            <label>Observación</label>
            <textarea class="form-control" id="observacion" rows="3"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Agregar</button>
          </div>
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
        <h5><i class="fa-solid fa-user-injured"></i> Seleccionar paciente</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <div class="form-group">
              <label for="buscarPaciente">Buscar</label>
              <input type="text" id="buscarPaciente" class="form-control" placeholder="Nombre, correo o teléfono">
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group">
              <label for="filtroSexo">Sexo</label>
              <select id="filtroSexo" class="form-control">
                <option value="">Todos</option>
                <option value="Femenino">Femenino</option>
                <option value="Masculino">Masculino</option>
              </select>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group">
              <label>&nbsp;</label>
              <button id="btnBuscarPac" class="btn btn-outline-primary w-100">
                <i class="fa fa-search"></i> Buscar
              </button>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover" id="pacientesTable">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Sexo</th>
                <th>Fecha Nac.</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <small id="pacInfo" class="text-muted"></small>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  let selectedPaciente = null;
  let procedimientosArray = [];

  // Mostrar toast Bootstrap 5
  function showToast(msg, type='info'){
    const toastHtml = `
      <div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">${msg}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>`;
    const $toast = $(toastHtml);
    $('#toastContainer').append($toast);
    const toast = new bootstrap.Toast($toast[0], { delay: 3000 });
    toast.show();
    $toast.on('hidden.bs.toast', function(){ $toast.remove(); });
  }

  // --- Cargar pacientes ---
  function renderPacientes(items){
    const $tb = $('#pacientesTable tbody');
    $tb.empty();
    if(!items || items.length===0){
      $tb.append('<tr><td colspan="6" class="text-center text-muted">No se encontraron pacientes</td></tr>');
      return;
    }
    items.forEach(p=>{
      const nombre = (p.nombre||'') + ' ' + (p.apellido||'');
      const row = $('<tr>');
      row.append($('<td>').text(nombre));
      row.append($('<td>').text(p.correo_electronico||''));
      row.append($('<td>').text(p.telefono||''));
      row.append($('<td>').text(p.sexo||''));
      row.append($('<td>').text(p.fecha_nacimiento||''));
      const btn = $(`<button class="btn btn-sm btn-primary btnSelectPaciente" 
                       data-id="${p.id_paciente}" data-nombre="${nombre}" 
                       data-correo="${p.correo_electronico||''}" data-telefono="${p.telefono||''}">Seleccionar</button>`);
      row.append($('<td>').append(btn));
      $tb.append(row);
    });
  }

  function cargarPacientes(){
    $.get('php/pacientes_search.php', { q: $('#buscarPaciente').val(), sexo: $('#filtroSexo').val() })
      .done(function(res){
        const r = (typeof res==='object')? res : JSON.parse(res||'{}');
        if(r.status==='ok' && Array.isArray(r.items)) renderPacientes(r.items);
        else renderPacientes([]);
      }).fail(function(){ renderPacientes([]); });
  }

  function debounce(fn, delay){ let t = null; return function(){ clearTimeout(t); const args = arguments; t = setTimeout(()=>fn.apply(this,args),delay); } }

  $('#btnBuscarPac').on('click', cargarPacientes);
  $('#buscarPaciente').on('keyup', debounce(cargarPacientes, 350));
  $('#filtroSexo').on('change', cargarPacientes);

  // Seleccionar paciente
  $(document).on('click', '.btnSelectPaciente', function(){
    selectedPaciente = {
      id: $(this).data('id'),
      nombre: $(this).data('nombre'),
      correo: $(this).data('correo'),
      telefono: $(this).data('telefono')
    };
    $('#seleccionarPacienteModal').modal('hide');

    // Mostrar UI
    $('#odontogramCard, #tablaProcedimientosCard, #btnTerminar').fadeIn();
    $('#pacienteSelectedBadge').remove();
    $('.topbar').append(`<div id="pacienteSelectedBadge" class="ms-auto"><strong>Paciente:</strong> <span class="badge bg-primary">${selectedPaciente.nombre}</span></div>`);
    showToast('Paciente seleccionado: ' + selectedPaciente.nombre, 'success');
  });

  // Abrir modal procedimiento
  $(document).on('click', '.odontograma-btn', function(){
    if(!selectedPaciente){ showToast('Selecciona primero un paciente.','warning'); $('#seleccionarPacienteModal').modal('show'); return; }
    const diente = $(this).data('diente');
    $('#dienteSeleccionado').text(diente);
    $('#dienteId').val(diente);
    $('#procedimientoModal').modal('show');
  });

  // Agregar procedimiento
  $('#formProcedimiento').on('submit', function(e){
    e.preventDefault();
    const diente = $('#dienteId').val();
    const lado = $('#lado').val();
    const proc = $('#procedimiento').val();
    const precio = parseFloat($('#precio').val()||0);
    const obs = $('#observacion').val();

    procedimientosArray.push({diente, lado, procedimiento:proc, precio, observacion:obs});
    renderTablaProcedimientos();
    $('#procedimientoModal').modal('hide');
    $('#formProcedimiento')[0].reset();
    showToast('Procedimiento agregado.','success');
  });

  // Renderizar tabla
  function renderTablaProcedimientos(){
    const $tbody = $('#tablaProcedimientos tbody');
    $tbody.empty();
    let total = 0;
    procedimientosArray.forEach((p,i)=>{
      total += p.precio;
      const row = $(`
        <tr>
          <td>${p.diente}</td>
          <td>${p.lado}</td>
          <td>${p.procedimiento}</td>
          <td>${p.precio.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".")}</td>
          <td>${$('<div>').text(p.observacion).html()}</td>
          <td><button class="btn btn-sm btn-danger btnEliminar" data-index="${i}">&times;</button></td>
        </tr>`);
      $tbody.append(row);
    });
    $('#totalPrecio').text(total.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, "."));
  }

  // Eliminar procedimiento
  $(document).on('click','.btnEliminar', function(){
    const index = $(this).data('index');
    procedimientosArray.splice(index,1);
    renderTablaProcedimientos();
    showToast('Procedimiento eliminado.','info');
  });

  // Generar presupuesto
  $('#btnTerminar').on('click', function(){
    if(!selectedPaciente){ showToast('Selecciona primero un paciente.','warning'); return; }
    if(procedimientosArray.length===0){ showToast('Agrega al menos un procedimiento.','warning'); return; }

    $.ajax({
      url: 'php/generar_presupuesto.php',
      method: 'POST',
      data: {
        paciente_nombre: selectedPaciente.nombre,
        paciente_correo: selectedPaciente.correo,
        paciente_telefono: selectedPaciente.telefono,
        id_paciente: selectedPaciente.id,
        procedimientos: JSON.stringify(procedimientosArray)
      },
      success: function(res){
        const r = (typeof res==='object')? res : JSON.parse(res||'{}');
        if(r.status==='ok'){
          showToast('Presupuesto generado correctamente.','success');
          if(r.pdf_url) window.open(r.pdf_url,'_blank');
          if(r.redirect) window.location.href = r.redirect;
        } else { showToast('Error: '+(r.message||'Error desconocido'),'danger'); }
      },
      error: function(){ showToast('Error al generar presupuesto.','danger'); }
    });
  });

  // Inicial
  $('#seleccionarPacienteModal').modal('show');
  cargarPacientes();
});
</script>

</div><!-- /app -->
</body>
</html>