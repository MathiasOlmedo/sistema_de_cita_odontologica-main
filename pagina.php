<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');
$resultado = MostrarConsultas($link); //mostrar las consultas
$resultadoDentistas = MostrarDentistas($link); //mostrar dentistas

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya existe una sesión activa, permitir el acceso al contenido restringido
if (isset($_SESSION['id_paciente'])) {
     $vUsuario = $_SESSION['id_paciente'];
     $row = consultarPaciente($link, $vUsuario);
} else {
     // Si no está logueado, se deja acceder a la página sin redirigir
     // Si es necesario redirigir a una página de login, se hace en páginas restringidas
     // No hacemos redirección en esta página
     // $_SESSION['MensajeTexto'] = "Error acceso al sistema  no registrado.";
     // $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
     // header("Location: ./index.php");
     // exit();
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
                    <div class="col-md-12 col-sm-12 text-right">
                         <span class="phone-icon"><i class="fa fa-phone"></i> +1 (849) 856 4014</span>
                         <span class="date-icon"><i class="fa fa-calendar-plus-o"></i> 8:00 AM - 7:00 PM (Lunes-Viernes)</span>
                         <span class="email-icon"><i class="fa fa-envelope-o"></i> 
                              <a href="#">perfect-teeth00@hotmail.com</a>
                         </span>
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
                    <a href="principal.php" class="navbar-brand"><img src="src/img/logo.png" width="20px" height="20px" alt="Logo"></a>
                    <a href="principal.php" class="navbar-brand">Perfect Teeth </a>
               </div>

               <!-- MENU LINKS -->
               <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                         <li><a href="#top" class="smoothScroll">Inicio</a></li>
                         <li><a href="#about" class="smoothScroll">Nosotros</a></li>
                         <li><a href="#team" class="smoothScroll">Dentistas</a></li>
                         <li><a href="#fotos" class="smoothScroll">Fotos</a></li>
                         <li><a href="#google-map" class="smoothScroll">Contacto</a></li>
                         <li class="appointment-btn"><a href="index.php">Realizar una Cita</a></li>
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
               <?php 
                    $_SESSION['MensajeTexto'] = null;
                    $_SESSION['MensajeTipo'] = null;
               }
               ?>
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
                                        <h3>Soy un dentista. Yo creo sonrisas. ¿Cuál es tu súper poder?</h3>
                                        <h1>Vida saludable</h1>
                                        <a href="#team" class="section-btn btn btn-default smoothScroll">Conoce a nuestros dentistas</a>
                                   </div>
                              </div>
                         </div>

                         <div class="item item-second">
                              <div class="caption">
                                   <div class="col-md-offset-1 col-md-10">
                                        <h3>Vamos a hacer tu vida más feliz</h3>
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
                                        <a href="#fotos" class="section-btn btn btn-default btn-blue smoothScroll">Fotos</a>
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
                              <h2 class="wow fadeInUp" data-wow-delay="0.6s">Bienvenido a Perfect Teeth</h2>
                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <p>En esta Clínica se ofrecen Servicios Odontológicos a niños y adultos en las diferentes ramas de la Odontología. Entre ellas podemos incluir: Diagnóstico, Emergencias, Radiología, Periodoncia, Operatoria Dental, Odontopediatría, Endodoncia, Prótesis (Fija, Parcial Removible y Total), Cirugía y Ortodoncia.</p>
                                   <h5>Visión</h5>
                                   <p>Ser la institución líder en servicios odontológicos a nivel nacional y América Latina; logrando la expansión de las coberturas, la mejora continua de los procesos y garantizando calidad y profesionalidad.</p>
                                   <h5>Misión</h5>
                                   <p>Brindar un servicio de excelencia en el área de salud oral, basado en conocimientos, alta tecnología y calidez humana que cubran las necesidades y expectativas de nuestros pacientes e interesados.</p>
                              </div>
                              <figure class="profile wow fadeInUp" data-wow-delay="1s">
                                   <img src="src/img/author-image.jpg" class="img-responsive" alt="">
                                   <figcaption>
                                        <h3>Dr. Jiminian Cruz</h3>
                                        <p>Odontólogo general</p>
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
                                   <h3>Francisco Rosario</h3>
                                   <p>Odontopediatra</p>
                                   <div class="team-contact-info">
                                        <p><i class="fa fa-phone"></i> +1 (829) 856 4014</p>
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
                                   <h3>Arlenis Hernández</h3>
                                   <p>Patólogo oral</p>
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
                         <!-- CONTACT FORM HERE -->
                         <form action="./crud/cita_INSERT.php?opciones=INS" method="POST" enctype="multipart/form-data" autocomplete="off" id="appointment-form">
                              <div id="fotos" class="section-title wow fadeInUp" data-wow-delay="0.4s">
                                   <h2>Nuestro Lugar</h2>
                              </div>
                              <div class="wow fadeInUp" data-wow-delay="0.8s">
                                   <div class="owl-carousel owl-theme">
                                        <div class="item"><img src="src/img/1.jpg" alt="Foto 1" class="img-responsive"></div>
                                        <div class="item"><img src="src/img/2.jpg" alt="Foto 2" class="img-responsive"></div>
                                        <div class="item"><img src="src/img/3.jpg" alt="Foto 3" class="img-responsive"></div>
                                        <div class="item"><img src="src/img/4.jpg" alt="Foto 4" class="img-responsive"></div>
                                   </div>
                              </div>
                         </form>
                    </div>
               </div>
          </div>
     </section>

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

</body>

</html>
