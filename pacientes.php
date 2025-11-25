<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_superadmin'])) {
    header("Location: index.php");
    exit();
}

// Traer pacientes
$resultadoPacientes = MostrarPacientes($link);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestionar Pacientes - Superadmin</title>

<link rel="stylesheet" href="src/css/bootstrap.min.css">
<link rel="stylesheet" href="src/css/font-awesome.min.css">
<link rel="stylesheet" href="src/css/tooplate-style.css">
<link rel="stylesheet" href="src/css/animate.css">
<link rel="stylesheet" href="src/css/owl.carousel.css">
<link rel="stylesheet" href="src/css/owl.theme.default.min.css">

<style>
body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; }
.navbar { background-color: #004d6e; }
.navbar-brand, .navbar-nav .nav-link { color: white !important; }
.sidebar { height:100%; width:250px; position:fixed; top:0; left:0; background-color:#004d6e; padding-top:20px;}
.sidebar a { color:white; padding:15px 20px; text-decoration:none; display:block;}
.sidebar a:hover { background-color:#006699;}
.content { margin-left:260px; padding:20px; }
.card { margin-bottom:20px; border-radius:8px; border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1);}
.card-header { background-color:#0099ff; color:white; font-weight:bold; font-size:1.1em;}
.btn-primary { background-color:#0099ff; border-color:#0099ff; color:white;}
.btn-primary:hover { background-color:#007acc; border-color:#007acc;}
.btn-success { background-color: #28a745; border-color: #28a745; color:white; }
.btn-success:hover { background-color: #218838; border-color:#1e7e34; }
.btn-danger { background-color: #dc3545; border-color: #dc3545; color:white; }
.btn-danger:hover { background-color: #c82333; border-color:#bd2130; }
table.table { background:white; border-radius:6px; overflow:hidden;}
table.table th, table.table td { vertical-align: middle !important;}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Superadmin - Perfect Teeth</a>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Cerrar sesión</a></li>
        </ul>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar">
    <a href="superadmin_dashboard.php">Dashboard</a>
    <a href="pacientes.php">Gestionar Pacientes</a>
    <a href="doctores.php">Gestionar Dentistas</a>
    <a href="gestionar_citas.php">Gestionar Citas</a>
    <a href="gestionar_usuarios.php">Gestionar Usuarios</a>
    <a href="reportes.php">Ver Reportes</a>
</div>

<!-- Contenido -->
<div class="content container">
    <h2>Listado de Pacientes</h2>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Sexo</th>
                <th>Fecha Nacimiento</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($paciente = mysqli_fetch_assoc($resultadoPacientes)) { ?>
            <tr>
                <td><?php echo $paciente['nombre']; ?></td>
                <td><?php echo $paciente['apellido']; ?></td>
                <td><?php echo $paciente['correo_electronico']; ?></td>
                <td><?php echo $paciente['telefono']; ?></td>
                <td><?php echo $paciente['sexo']; ?></td>
                <td><?php echo $paciente['fecha_nacimiento']; ?></td>
                <td>
                    <a href="editar_paciente.php?id=<?php echo $paciente['id_paciente']; ?>" class="btn btn-primary btn-sm">Editar</a>
                    <a href="crud/eliminar_paciente.php?id=<?php echo $paciente['id_paciente']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este paciente?');">Borrar</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <h3 class="mt-4">Agregar Nuevo Paciente</h3>
    <form action="./crud/registro_INSERT.php?opciones=INSPAC" method="POST">
        <div class="row">
            <div class="col-md-4"><input class="form-control" type="text" name="name" placeholder="Nombre" required></div>
            <div class="col-md-4"><input class="form-control" type="text" name="apellido" placeholder="Apellido" required></div>
            <div class="col-md-4"><input class="form-control" type="date" name="nacimiento" required></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-4"><input class="form-control" type="text" name="cell" placeholder="Teléfono" required></div>
            <div class="col-md-4"><input class="form-control" type="email" name="correo" placeholder="Correo electrónico" required></div>
            <div class="col-md-4"><input class="form-control" type="password" name="clave" placeholder="Contraseña" required></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-4">
                <select class="form-control" name="sexo" required>
                    <option>Masculino</option>
                    <option>Femenino</option>
                </select>
            </div>
            <div class="col-md-8">
                <button class="btn btn-success btn-block">Agregar Paciente</button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <a href="superadmin_dashboard.php" class="btn btn-primary">Atrás</a>
            </div>
        </div>
    </form>
</div>

<script src="src/js/jquery.js"></script>
<script src="src/js/bootstrap.min.js"></script>
</body>
</html>
