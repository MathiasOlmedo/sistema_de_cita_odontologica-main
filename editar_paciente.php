<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_usuario']) && $_SESSION['tipo'] == 'Paciente') {
    $vUsuario = $_SESSION['id_usuario'];
    $row = consultarPaciente($link, $vUsuario);
} else {
    $_SESSION['MensajeTexto'] = "Acceso no autorizado. Por favor, inicie sesión.";
    $_SESSION['MensajeTipo'] = "alert alert-danger";
    header("Location: ./index.php");
    exit();
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Perfect Teeth - Editar Perfil</title>

    <!-- ICONO -->
    <link rel="icon" href="./src/img/logo.png" type="image/png" />

    <!-- Bootstrap 4 (asumiendo que está en la ruta) y estilos personalizados -->
    <link rel="stylesheet" href="src/css/bootstrap.min.css">
    <link rel="stylesheet" href="src/css/tooplate-style.css">
    <link rel="stylesheet" href="src/css/lib/fontawesome/css/all.css">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .card-container {
            margin-top: 50px;
            margin-bottom: 50px;
        }
    </style>
</head>

<body>
    <!-- MENU -->
    <section class="navbar navbar-default navbar-static-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon icon-bar"></span>
                    <span class="icon icon-bar"></span>
                    <span class="icon icon-bar"></span>
                </button>
                <a href="./principal.php" class="navbar-brand"><img src="src/img/logo.png" width="20px" height="20px" alt="Logo" style="display: inline-block; margin-right: 5px;">Perfect Teeth</a>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="./principal.php#top" class="smoothScroll">Inicio</a></li>
                    <li><a href="./principal.php#about" class="smoothScroll">Nosotros</a></li>
                    <li><a href="./principal.php#team" class="smoothScroll">Dentistas</a></li>
                    <li class="active"><a href="./editar_paciente.php">Perfil</a></li>
                    <li><a href="./principal.php#google-map" class="smoothScroll">Contacto</a></li>
                    <li class="appointment-btn"><a href="./principal.php#appointment">Realizar una Cita</a></li>
                </ul>
            </div>
        </div>
    </section>

    <div class="container card-container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-edit"></i> Editar Información Personal</h4>
            </div>
            <div class="card-body">
                <form action="./crud/actualizar_paciente.php?accion=UDT" method="POST" autocomplete="off">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id_paciente']); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first-name">Nombre</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim($row['nombre'])); ?>" name="name" placeholder="Nombre" id="first-name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last-name">Apellido</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(trim($row['apellido'])); ?>" name="apellido" placeholder="Apellido" id="last-name">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="number">Teléfono</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['telefono']); ?>" name="cell" placeholder="Teléfono" id="number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="age">Fecha de nacimiento</label>
                                <input type="date" class="form-control" value="<?php echo htmlspecialchars($row['fecha_nacimiento']); ?>" name="nacimiento" placeholder="Fecha de nacimiento" id="age">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($row['correo_electronico']); ?>" name="correo" placeholder="Correo Electrónico" id="email" readonly>
                        <small class="form-text text-muted">El correo electrónico no se puede modificar.</small>
                    </div>

                    <div class="form-group">
                        <label for="sexo">Sexo</label>
                        <select class="form-control" name="sexo" required>
                            <option value="Masculino" <?php echo ($row['sexo'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?php echo ($row['sexo'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>

                    <hr>
                    
                    <div class="text-center">
                        <a href="principal.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                        <button type="submit" class="btn btn-success" name="actualizar" id="search">
                            <i class="fas fa-check"></i> Actualizar Datos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="src/js/jquery.js"></script>
    <script src="src/js/bootstrap.min.js"></script>
</body>
</html>