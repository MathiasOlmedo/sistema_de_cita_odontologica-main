<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

$resultado = MostrarConsultas($link); //mostrar las consultas
$resultadoDentistas = MostrarDentistas($link); //mostrar dentistas

if (isset($_SESSION['id_paciente'])) {
     $vUsuario = $_SESSION['id_paciente'];
     $row = consultarPaciente($link, $vUsuario);
} else {
     $_SESSION['MensajeTexto'] = "Error acceso al sistema  no registrado.";
     $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
     header("Location: ./index.php");
     exit();
}

/* ============================================================
   NUEVO: Consultas para “Mi Panel”
   ============================================================ */

// 1) Mis Presupuestos + Pagos (saldo individual por presupuesto)
$presupuestos = [];
$sqlPres = "
  SELECT 
    p.id_presupuesto, p.folio, p.fecha, p.total, p.estado, p.pdf_path,
    COALESCE(SUM(pg.monto),0) AS pagado
  FROM presupuesto p
  LEFT JOIN pagos pg ON pg.id_presupuesto = p.id_presupuesto
  WHERE p.id_paciente = ?
  GROUP BY p.id_presupuesto
  ORDER BY p.fecha DESC
";
if ($stmt = $link->prepare($sqlPres)) {
  $stmt->bind_param('i', $vUsuario);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) {
    $r['saldo_calc'] = (float)$r['total'] - (float)$r['pagado'];
    if ($r['saldo_calc'] < 0) $r['saldo_calc'] = 0.0; // mostrar 0.00 si está pagado o excedente
    $presupuestos[] = $r;
  }
  $stmt->close();
}

// 2) Tratamientos realizados (de presupuestos APROBADOS)
$tratamientos = [];
$sqlTrat = "
  SELECT 
    pd.diente, pd.lado, pd.procedimiento, pd.precio, 
    p.fecha AS fecha_presupuesto,
    CONCAT(d.nombreD,' ',d.apellido) AS doctor
  FROM presupuesto_detalle pd
  INNER JOIN presupuesto p ON p.id_presupuesto = pd.id_presupuesto
  LEFT JOIN doctor d ON d.id_doctor = p.id_doctor
  WHERE p.id_paciente = ? AND p.estado = 'aprobado'
  ORDER BY p.fecha DESC, pd.id DESC
";
if ($stmt = $link->prepare($sqlTrat)) {
  $stmt->bind_param('i', $vUsuario);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $tratamientos[] = $r;
  $stmt->close();
}

// 3) Mis Citas
$misCitas = [];
$sqlCitas = "
  SELECT 
    c.id_cita, c.fecha_cita, c.hora_cita, c.estado,
    CONCAT(d.nombreD,' ',d.apellido) AS doctor
  FROM citas c
  INNER JOIN doctor d ON d.id_doctor = c.id_doctor
  WHERE c.id_paciente = ?
  ORDER BY c.fecha_cita DESC, c.hora_cita DESC
";
if ($stmt = $link->prepare($sqlCitas)) {
  $stmt->bind_param('i', $vUsuario);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $misCitas[] = $r;
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
     <title>Perfect Teeth </title>
     <!-- ICONO -->
     <link rel="icon" href="./src/img/logo.png" type="image/png" />

     <meta charset="UTF-8">
     <meta http-equiv="X-UA-Compatible" content="IE=Edge">
     <meta name="description" content="">
     <meta name="keywords" content="">
     <meta name="author" content="Tooplate">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

     <link rel="stylesheet" href="src/css/bootstrap.min.css">
     <link rel="stylesheet" href="src/css/font-awesome.min.css">
     <link rel="stylesheet" href="src/css/animate.css">
     <link rel="stylesheet" href="src/css/owl.carousel.css">
     <link rel="stylesheet" href="src/css/owl.theme.default.min.css">
     <!-- MAIN CSS -->
     <link rel="stylesheet" href="src/css/tooplate-style.css">
     <!-- Datepicker libreria jqueryui para calendario -->
     <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
     <link rel="stylesheet" href="/resources/demos/style.css">
     <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
     <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
     <script src="./src/js/Datepicker.js"></script>

     <!-- Pequeños estilos para “Mi Panel” (no tocamos tu theme) -->
     <style>
       .badge-cap { text-transform: capitalize; }
       .table-sm td, .table-sm th { padding: .5rem .6rem; }
       .card-soft { border: 1px solid rgba(0,0,0,.06); box-shadow: 0 6px 16px rgba(15,23,42,.06); border-radius: 12px; }
     </style>
</head>

<body id="top" data-spy="scroll" data-target=".navbar-collapse" data-offset="50">

     <!-- PRE LOADER -->
     <section class="preloader">
          <div class="spinner">
               <span class="spinner-rotate"></span>
          </div>
     </section>

     <!-- HEADER -->
     <header>
          <div class="container">
               <div class="row">
                    <div class="col-md-4 col-sm-10">
                         <p>
                          <?php 
                            if ($row['sexo'] == 'Masculino') {
                              echo "Bienvenido " . $row['nombre'] . ' ' . $row['apellido'];
                            } else {
                              echo "Bienvenida " . $row['nombre'] . ' ' . $row['apellido'];
                            } 
                          ?>
                         </p>
                    </div>
                    <div class="col-md-8 col-sm-10 ">
                         <span class="phone-icon"><i class="fa fa-phone"></i> +1 (849) 856 4014</span>
                         <span class="date-icon"><i class="fa fa-calendar-plus-o"></i> 8:00 AM - 7:00 PM (Lunes-Viernes)</span>
                         <span class="email-icon"><i class="fa fa-envelope-o"></i> <a href="#">perfect-teeth00@hotmail.com</a></span>
                         <span><i class="fa fa-sign-out"></i><a href="./php/cerrar.php">Cerrar Sesíon </a></span>
                    </div>
               </div>
          </div>
     </header>

     <!-- MENU -->
     <section class="navbar navbar-default navbar-static-top" role="navigation">
          <div class="container">
               <div class="navbar-header">
                    <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                         <span class="icon icon-bar"></span>
                    </button>
                    <!-- lOGO TEXT HERE -->
                    <a href=" principal.php" class="navbar-brand"><img src="src/img/logo.png" width="20px" height="20px" alt="Logo"></a>
                    <a href=" principal.php" class="navbar-brand">Perfect Teeth </a>
               </div>
               <!-- MENU LINKS -->
               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                         <li><a href="#top" class="smoothScroll">Inicio</a></li>
                         <li><a href="#about" class="smoothScroll">Nosotros</a></li>
                         <li><a href="#team" class="smoothScroll">Dentistas</a></li>
                         <li><a href="#perfil" class="smoothScroll">Perfil</a></li>
                         <!-- NUEVO acceso directo -->
                         <li><a href="#panel" class="smoothScroll">Mi Panel</a></li>
                         <li><a href="#google-map" class="smoothScroll">Conctato</a></li>
                         <li class="appointment-btn"><a href="#appointment">Realizar una Cita</a></li>
                    </ul>
               </div>
          </div>
     </section>

     <!-- Mensaje de alerta -->
     <div class="row">
          <div class="col-md-3 col-md-offset-5">
               <?php if (isset($_SESSION['MensajeTexto'])) { ?>
                    <div class="alert <?php echo $_SESSION['MensajeTipo'] ?>" role="alert">
                         <?php echo $_SESSION['MensajeTexto'] ?>
                         <button class="delete"><i class="fa fa-times"></i></button>
                    </div>
               <?php $_SESSION['MensajeTexto'] = null; $_SESSION['MensajeTipo'] = null; } ?>
          </div>
     </div>

     <!-- HOME -->
     <section id="home" class="slider" data-stellar-background-ratio="0.5">
          <div class="container">
               <div class="row">
                    <div class="owl-carousel owl-theme">
                         <div class="item item-first">
                              <div class="caption">
                                   <div class="col-md-offset-1 col-md-10">
                                        <h3>Soy un dentista. Yo creo sonrisas. ¿Cuál es tu súper poder? </h3>
                                        <h1>Vida saludable</h1>
                                        <a href="#team" class="section-btn btn btn-default smoothScroll">Conoce a nuestros dentista</a>
                                   </div>
                              </div>
                         </div>
                         <div class="item item-second">
                              <div class="caption">
                                   <div class="col-md-offset-1 col-md-10">
                                        <h3>vamos a hacer tu vida más feliz</h3>
                                        <h1>Nuevo estilo de vida</h1>
                                        <a href="#about" class="section-btn btn btn-default btn-gray smoothScroll">Más Sobre nosotros</a>
                                   </div>
                              </div>
                         </div>
                         <div class="item item-third">
                              <div class="caption">
                                   <div class="col-md-offset-1 col-md-10">
                                        <h3>La odontología no es cara, lo que es caro es el descuido.</h3>
                                        <h1>Información personal</h1>
                                        <a href="#perfil" class="section-btn btn btn-default btn-blue smoothScroll">Perfil</a>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- ABOUT -->
     <section id="about">
          <div class="container">
               <div class="row">
                    <div class="col-md-6 col-sm-6">
                         <div class="about-info">
                              <h2 class="wow fadeInUp" data-wow-delay="0.6s">Bienvenido a Perfect Teeth </h2>
                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <p>En esta Clínica se ofrecen Servicios Odontológicos a niños y adultos...</p>
                                   <h5>Visión</h5>
                                   <p>Ser la institución líder en servicios odontológicos...</p>
                                   <h5>Misión</h5>
                                   <p>Brindar un servicio de excelencia...</p>
                              </div>
                              <figure class="profile wow fadeInUp" data-wow-delay="1s">
                                   <img src="src/img/author-image.jpg" class="img-responsive" alt="">
                                   <figcaption>
                                        <h3>Dr. Jiminian Cruz </h3>
                                        <p> Odontólogo general</p>
                                   </figcaption>
                              </figure>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- TEAM -->
     <section id="team" data-stellar-background-ratio="1">
          <div class="container">
               <div class="row">
                    <div class="col-md-6 col-sm-6">
                         <div class="about-info">
                              <h2 class="wow fadeInUp" data-wow-delay="0.1s">Nuestros dentistas</h2>
                         </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.2s">
                              <img src="src/img/team-image1.jpg" class="img-responsive" alt="">
                              <div class="team-info">
                                   <h3>Francisco Rosario </h3>
                                   <p>Odontopediatra</p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i>+1 (829) 856 4014</p>
                                        <p><i class="fa fa-envelope-o"></i> <a href="#">francisco@hotmail.com</a></p>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.4s">
                              <img src="src/img/team-image2.jpg" class="img-responsive" alt="">
                              <div class="team-info">
                                   <h3>Stewart Diaz</h3>
                                   <p>Ortodoncista/Endodoncista</p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i> 010-070-0170</p>
                                        <p><i class="fa fa-envelope-o"></i> <a href="#">Stewart@company.com</a></p>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                         <div class="team-thumb wow fadeInUp" data-wow-delay="0.6s">
                              <img src="src/img/team-image3.jpg" class="img-responsive" alt="">
                              <div class="team-info">
                                   <h3>Arlenis Hernández </h3>
                                   <p>Patólogo oral </p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i> +1 (829) 462-9992</p>
                                        <p><i class="fa fa-envelope-o"></i> <a href="#">arlenis@company.com</a></p>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- MAKE AN APPOINTMENT -->
    <section id="appointment" data-stellar-background-ratio="3">
  <div class="container">
    <div class="row">
      <div class="col-md-6 col-sm-6">
        <img src="src/img/appointment-image.jpg" class="img-responsive" alt="">
      </div>

      <div class="col-md-6 col-sm-6">
        <form action="./crud/cita_INSERT.php?opciones=INS" method="POST" enctype="multipart/form-data" autocomplete="off" id="appointment-form">
          <div class="section-title wow fadeInUp" data-wow-delay="0.4s">
            <h2>Realizar una Cita</h2>
          </div>

          <div class="wow fadeInUp" data-wow-delay="0.8s">
            <div class="col-md-6 col-sm-6">
              <label for="name">Nombre</label>
              <input type="text" class="form-control" id="name" name="name" readonly value="<?php echo $row['nombre']; ?>">
            </div>
            <div class="col-md-6 col-sm-6">
              <label for="lastname">Apellido</label>
              <input type="text" class="form-control" id="lastname" name="lastname" readonly value="<?php echo $row['apellido']; ?>">
            </div>
               <div class="row">
               <div class="col-sm-3"><h5 class="mb-0">Cédula</h5></div>
               <div class="col-sm-9 text-secondary"><?php echo $row['cedula']; ?></div>
               </div>
               <hr>
            <div class="col-md-12 col-sm-12">
              <label for="email">Correo Electrónico</label>
              <input type="email" class="form-control" id="email" name="email" readonly value="<?php echo $row['correo_electronico']; ?>">
            </div>

            <div class="col-md-6 col-sm-6">
              <label for="consultas">Consultas</label><br>
              <select name="consultas" id="consultas" class="form-control" required>
                <?php while ($row1 = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
                  echo "<option value='{$row1['id_consultas']}'>{$row1['tipo']}</option>";
                } ?>
              </select>
            </div>

            <div class="col-md-6 col-sm-6">
              <label for="dentistas">Dentistas</label><br>
              <select name="dentistas" id="dentistas" class="form-control" required>
                <option value="">Seleccione un doctor</option>
                <?php while ($row2 = mysqli_fetch_array($resultadoDentistas, MYSQLI_ASSOC)) {
                  echo "<option value='{$row2['id_doctor']}'>{$row2['nombreD']} {$row2['apellido']}</option>";
                } ?>
              </select>
            </div>

            <div class="col-md-6 col-sm-6">
              <label for="fecha_cita">Fecha de la cita</label>
              <input type="date" class="form-control" name="fecha_cita" id="fecha_cita" required>
            </div>

            <div class="col-md-12 col-sm-12 mt-3">
              <label>Selecciona un horario disponible</label>
              <div id="horarios-container" class="d-flex flex-wrap gap-2"></div>
              <input type="hidden" name="hora" id="hora" required>
            </div>

            <div class="col-md-12 col-sm-12">
              <br><label for="phone">Teléfono</label>
              <input type="tel" class="form-control" id="phone" name="phone" readonly value="<?php echo $row['telefono']; ?>">
            </div>

            <div class="col-md-12 col-sm-12">
              <br><button type="submit" name="enviar" value="enviar" class="form-control btn btn-primary" id="cf-submit">Confirmar cita</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<style>
.time-slot {
  display: inline-block;
  margin: .3rem;
  padding: .6rem 1rem;
  border-radius: .4rem;
  font-weight: 500;
  border: 1px solid #ccc;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}
.time-slot.available {
  background: #e7f1ff;
  color: #0d6efd;
  border-color: #0d6efd;
}
.time-slot.available:hover {
  background: #0d6efd;
  color: white;
}
.time-slot.occupied {
  background: #f8d7da;
  color: #842029;
  border-color: #dc3545;
  cursor: not-allowed;
  opacity: 0.7;
}
.time-slot.selected {
  background: #198754 !important;
  color: white !important;
  border-color: #198754 !important;
}
</style>

<script>
$(document).ready(function() {
  // Bloquear fines de semana
  $('#fecha_cita').on('change', function() {
    const day = new Date(this.value).getUTCDay();
    if (day === 0 || day === 6) {
      alert('No se permiten citas los fines de semana.');
      $(this).val('');
    }
  });

  function cargarHorarios() {
    const doctor = $("#dentistas").val();
    const fecha = $("#fecha_cita").val();
    const contenedor = $("#horarios-container");
    contenedor.html('<p class="text-muted">Cargando horarios...</p>');
    $("#hora").val('');

    if (!doctor || !fecha) {
      contenedor.html('<p class="text-danger">Selecciona primero un doctor y una fecha.</p>');
      return;
    }

    $.get("ajax/horarios_disponibles.php", { doctor: doctor, fecha: fecha }, function(data) {
      contenedor.empty();
      if (data.length === 0) {
        contenedor.html('<p class="text-danger">No hay horarios disponibles.</p>');
        return;
      }

      data.forEach(h => {
        const div = $("<div>")
          .addClass("time-slot")
          .addClass(h.disponible ? "available" : "occupied")
          .text(h.hora)
          .data("hora", h.hora);

        if (h.disponible) {
          div.on("click", function() {
            $(".time-slot.available").removeClass("selected");
            $(this).addClass("selected");
            $("#hora").val($(this).data("hora"));
          });
        }
        contenedor.append(div);
      });
    }, "json");
  }

  $("#fecha_cita, #dentistas").on("change", cargarHorarios);
});
</script>


     <!-- perfil -->
     <section id="perfil" style="margin-top: 10%;">
          <div class="container">
               <div class="main-body">
                    <div class="row gutters-sm">
                         <div class="col-md-4 mb-3">
                              <div class="card">
                                   <div class="card-body">
                                        <div class="d-flex flex-column align-items-center text-center">
                                             <?php if ($row['sexo'] == 'Masculino') { ?>
                                                  <img src="./src/img/iconoH.jpg" class="rounded-circle" width="150">
                                             <?php } elseif ($row['sexo'] == 'Femenino') { ?>
                                                  <img src="./src/img/iconoM.jpg" class="rounded-circle" width="150">
                                             <?php } ?>
                                             <div class="mt-3">
                                                  <h3 class="name"><?php echo $row['nombre'] . ' ' . $row['apellido']; ?> </h3>
                                                  <p class="text-secondary mb-1">Perfect Teeth</p>
                                                  <p class="text-muted font-size-sm"><?php echo $row['correo_electronico']; ?></p>
                                                  <div class="card  bg-light" style="margin-top: 20%;">
                                                       <div class="card-header">
                                                            <h4> <strong>Acciones</strong></h4>
                                                       </div>
                                                       <div class="card-body">
                                                            <label>Editar perfil</label>
                                                            <a class="btn btn-primary " href="./editar_paciente.php"> <i class="fa fa-edit"></i> </a> <br>
                                                            <label>Vizualizar consultas pendientes</label>
                                                            <a class="btn btn-success " target="__blank" href="/sistema_de_cita_odontologica-main/Reportes/reporte.php"><i class="fa fa-eye"></i> </a> <br>
                                                            <label>Vizualizar historial</label>
                                                            <a class="btn btn-success text-dark " target="__blank" href="./Reportes/reporteH.php"><i class="fa fa-history"></i></a> <br>
                                                       </div>
                                                       <div class="card-footer text-muted"></div>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                         </div>

                         <div class="col-md-8">
                              <div class="card mb-3">
                                   <div class="card-body">
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0"> Nombre </h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo $row['nombre'] ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0"> Apellido</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo $row['apellido']; ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
    <div class="col-sm-3"><h5 class="mb-0">Cédula</h5></div>
    <div class="col-sm-9 text-secondary"><?php echo $row['cedula']; ?></div>
</div>
<hr>
<hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0"> Sexo</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo $row['sexo']; ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Correo electrónico</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo $row['correo_electronico']; ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Télefono</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo $row['telefono']; ?></div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                             <div class="col-sm-3"><h5 class="mb-0">Fecha de nacimiento</h5></div>
                                             <div class="col-sm-9 text-secondary"><?php echo $row['fecha_nacimiento']; ?></div>
                                        </div>
                                        <hr>
                                   </div>
                                   <br>
                              </div>
                              <br>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <!-- =========================================================
          NUEVO: MI PANEL (entre perfil y google-map)
          ========================================================= -->
     <section id="panel" style="margin-top: 10%;">
       <div class="container">
         <div class="row">
           <!-- Mis Presupuestos -->
           <div class="col-md-12">
             <div class="card-soft" style="margin-bottom:20px;">
               <div class="card-header" style="padding:12px 16px;"><h4 class="mb-0">Mis Presupuestos</h4></div>
               <div class="card-body" style="padding:16px;">
                 <div class="table-responsive">
                   <table class="table table-sm table-hover">
                     <thead class="thead-light">
                       <tr>
                         <th>Folio</th>
                         <th>Fecha</th>
                         <th class="text-right">Total ($)</th>
                         <th class="text-right">Pagado ($)</th>
                         <th class="text-right">Saldo ($)</th>
                         <th>Estado</th>
                         <th>Acciones</th>
                       </tr>
                     </thead>
                     <tbody>
                       <?php if(empty($presupuestos)): ?>
                         <tr><td colspan="7" class="text-center text-muted">No tienes presupuestos registrados.</td></tr>
                       <?php else: foreach($presupuestos as $p): 
                         $badge = 'secondary';
                         if ($p['estado']==='enviado') $badge='success';
                         elseif ($p['estado']==='aprobado') $badge='primary';
                         elseif ($p['estado']==='rechazado') $badge='danger';
                         $pdf = !empty($p['pdf_path']) ? htmlspecialchars($p['pdf_path']) : '';
                       ?>
                         <tr>
                           <td><?php echo htmlspecialchars($p['folio']); ?></td>
                           <td><?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?></td>
                           <td class="text-right"><?php echo number_format((float)$p['total'],2); ?></td>
                           <td class="text-right"><?php echo number_format((float)$p['pagado'],2); ?></td>
                           <td class="text-right"><?php echo number_format(max(0,(float)$p['saldo_calc']),2); ?></td>
                           <td><span class="badge badge-cap badge-<?php echo $badge; ?>"><?php echo htmlspecialchars($p['estado']); ?></span></td>
                           <td>
                             <?php if($pdf): ?>
                               <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?php echo $pdf; ?>">
                                 <i class="fa fa-file-pdf-o"></i> Ver PDF
                               </a>
                             <?php else: ?>
                               <button class="btn btn-sm btn-outline-secondary" disabled>Sin PDF</button>
                             <?php endif; ?>
                           </td>
                         </tr>
                       <?php endforeach; endif; ?>
                     </tbody>
                   </table>
                 </div>
               </div>
             </div>
           </div>

           

           <!-- Mis Citas -->
           <div class="col-md-12">
             <div class="card-soft" style="margin-bottom:20px;">
               <div class="card-header" style="padding:12px 16px;"><h4 class="mb-0">Mis Citas</h4></div>
               <div class="card-body" style="padding:16px;">
                 <div class="table-responsive">
                   <table class="table table-sm table-hover">
                     <thead class="thead-light">
                       <tr>
                         <th>Fecha</th>
                         <th>Hora</th>
                         <th>Doctor</th>
                         <th>Estado</th>
                       </tr>
                     </thead>
                     <tbody>
                       <?php if(empty($misCitas)): ?>
                         <tr><td colspan="4" class="text-center text-muted">No tienes citas registradas.</td></tr>
                       <?php else: foreach($misCitas as $c): 
                         $b = 'secondary';
                         if ($c['estado']==='A') $b='success';
                         elseif ($c['estado']==='I') $b='warning';
                       ?>
                         <tr>
                           <td><?php echo htmlspecialchars($c['fecha_cita']); ?></td>
                           <td><?php echo htmlspecialchars(substr($c['hora_cita'],0,5)); ?></td>
                           <td><?php echo htmlspecialchars($c['doctor']); ?></td>
                           <td><span class="badge badge-<?php echo $b; ?>"><?php echo htmlspecialchars($c['estado']); ?></span></td>
                         </tr>
                       <?php endforeach; endif; ?>
                     </tbody>
                   </table>
                 </div>
               </div>
             </div>
           </div>

         </div>
       </div>
     </section>
     <!-- /MI PANEL -->

     <!-- GOOGLE MAP -->
     <section id="google-map">
          <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d901.4595953705438!2d-57.51119473043212!3d-25.34320378656389!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjXCsDIwJzM1LjUiUyA1N8KwMzAnMzguMCJX!5e0!3m2!1ses!2spy!4v1743518779941!5m2!1ses!2spy" width="100%" height="350" frameborder="0" style="border:0" allowfullscreen></iframe>
     </section>

     <!-- FOOTER -->
     <footer data-stellar-background-ratio="5">
          <div class="container">
               <div class="row">
                    <div class="col-md-4 col-sm-4">
                         <div class="footer-thumb">
                              <h4 class="wow fadeInUp" data-wow-delay="0.4s"> Datos de contacto</h4>
                              <p>Todos los derechos reservados 2021-2022</p>
                              <div class="contact-info">
                                   <p><i class="fa fa-phone"></i> +1 (849) 856 4014</p>
                                   <p><i class="fa fa-envelope-o"></i> <a href="#">perfect-teeth00@hotmail.com</a></p>
                              </div>
                         </div>
                    </div>
                    <div class="col-md-4 col-sm-4">
                         <div class="footer-thumb">
                              <h4 class="wow fadeInUp" data-wow-delay="0.4s">Últimas noticias</h4>
                              <div class="latest-stories">
                                   <div class="stories-image">
                                        <a href="https://www.odontologos.mx/odontologos/noticias/3508/manejo-del-blanqueamiento-dental-no-vital" target="_blank"><img src="src/img/blanqueamiento.jpg" class="img-responsive" alt=""></a>
                                   </div>
                                   <div class="stories-info">
                                        <a href="https://www.odontologos.mx/odontologos/noticias/3508/manejo-del-blanqueamiento-dental-no-vital" target="_blank">
                                             <h5>Blanqueamiento dental no vital</h5>
                                        </a>
                                        <span>Mayo 24, 2021</span>
                                   </div>
                              </div>
                              <div class="latest-stories">
                                   <div class="stories-image">
                                        <a href="https://www.odontologos.mx/odontologos/noticias/3407/como-ha-evolucionado-la-odontologia-moderna" target="_blank"></a><img src="src/img/evolucion.jpg" class="img-responsive" alt=""></a>
                                   </div>
                                   <div class="stories-info">
                                        <a href="https://www.odontologos.mx/odontologos/noticias/3407/como-ha-evolucionado-la-odontologia-moderna" target="_blank">
                                             <h5> Evolución de la odontología moderna</h5>
                                        </a>
                                        <span>Diciembre 07, 2020</span>
                                   </div>
                              </div>
                         </div>
                    </div>
                    <div class=" col-md-4 col-sm-4">
                         <div class="footer-thumb">
                              <div class="opening-hours">
                                   <h4 class="wow fadeInUp" data-wow-delay="0.4s">Horario de apertura</h4>
                                   <p>Lunes - Viernes <span>08:00 AM - 7:00 PM</span></p>
                                   <p>Sábado <span>Cerrado</span></p>
                                   <p>Domingo <span>Cerrado</span></p>
                              </div>
                              <ul class="social-icon">
                                   <li><a href="https://www.facebook.com/Perfect-Teeth-111123294536780" target=" _blank" class="fa fa-facebook-square" attr="facebook icon"></a></li>
                              </ul>
                         </div>
                    </div>
                    <div class="col-md-12 col-sm-12 border-top">
                         <div class="col-md-4 col-sm-6">
                              <div class="copyright-text">
                                   <p>Copyright &copy; 2021 Perfect Teeth | Design: Edward</p>
                              </div>
                         </div>
                         <div class="col-md-6 col-sm-6">
                              <div class="footer-link">
                                   <a>Politica de privacidad </a>
                                   <a>Cookies</a>
                                   <a>Avisos legales </a>
                                   <a href="https://www.facebook.com/Perfect-Teeth-111123294536780" target="_blank">Facebook</a>
                              </div>
                         </div>
                         <div class="col-md-2 col-sm-2 text-align-center">
                              <div class="angle-up-btn">
                                   <a href="#top" class="smoothScroll wow fadeInUp" data-wow-delay="1.2s"><i class="fa fa-angle-up"></i></a>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </footer>

     <script>
          document.addEventListener('DOMContentLoaded', () => {
               (document.querySelectorAll('.alert .delete') || []).forEach(($delete) => {
                    const $notification = $delete.parentNode;
                    $delete.addEventListener('click', () => {
                         $notification.parentNode.removeChild($notification);
                    });
               });
          });
     </script>

     <!-- SCRIPTS -->
     <script src="src/js/bootstrap.min.js"></script>
     <script src="src/js/jquery.sticky.js"></script>
     <script src="src/js/jquery.stellar.min.js"></script>
     <script src="src/js/wow.min.js"></script>
     <script src="src/js/smoothscroll.js"></script>
     <script src="src/js/owl.carousel.min.js"></script>
     <script src="src/js/custom.js"></script>

     <script>
          document.addEventListener('DOMContentLoaded', () => {
               (document.querySelectorAll('.alert .delete') || []).forEach(($delete) => {
                    const $notification = $delete.parentNode;
                    $delete.addEventListener('click', () => {
                         $notification.parentNode.removeChild($notification);
                    });
               });
          });
     </script>
</body>
</html>
