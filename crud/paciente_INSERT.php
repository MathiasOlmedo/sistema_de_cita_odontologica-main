<?php
include_once('../php/conexionDB.php');
session_start();

if (isset($_POST['guardar'])) {
    $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $cedula = mysqli_real_escape_string($link, $_POST['cedula']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    $sexo = mysqli_real_escape_string($link, $_POST['sexo']);
    $fecha_nacimiento = mysqli_real_escape_string($link, $_POST['fecha_nacimiento']);
    $correo_electronico = mysqli_real_escape_string($link, $_POST['correo_electronico']);
    $clave = mysqli_real_escape_string($link, $_POST['clave']);

    $query = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, sexo, fecha_nacimiento, correo_electronico, clave)
              VALUES ('$nombre', '$apellido', '$cedula', '$telefono', '$sexo', '$fecha_nacimiento', '$correo_electronico', '$clave')";
    $resultado = mysqli_query($link, $query);

    if ($resultado) {
        $_SESSION['MensajeTexto'] = "Paciente registrado correctamente.";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-success text-white";
    } else {
        $_SESSION['MensajeTexto'] = "Error al registrar paciente: " . mysqli_error($link);
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
    }

    header("Location: ../secretaria/gestionar_pacientes.php");
    exit();
}
?>
