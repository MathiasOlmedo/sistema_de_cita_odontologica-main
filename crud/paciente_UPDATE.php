<?php
include_once('../php/conexionDB.php');
session_start();

if (isset($_POST['actualizar'])) {
    $id = intval($_POST['id_paciente']);
    $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $cedula = mysqli_real_escape_string($link, $_POST['cedula']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    $sexo = mysqli_real_escape_string($link, $_POST['sexo']);
    $fecha_nacimiento = mysqli_real_escape_string($link, $_POST['fecha_nacimiento']);
    $correo_electronico = mysqli_real_escape_string($link, $_POST['correo_electronico']);

    $sql = "UPDATE pacientes 
            SET nombre='$nombre',
                apellido='$apellido',
                cedula='$cedula',
                telefono='$telefono',
                sexo='$sexo',
                fecha_nacimiento='$fecha_nacimiento',
                correo_electronico='$correo_electronico'
            WHERE id_paciente=$id";

    if (mysqli_query($link, $sql)) {
        $_SESSION['MensajeTexto'] = "Paciente actualizado correctamente.";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-success text-white";
    } else {
        $_SESSION['MensajeTexto'] = "Error al actualizar paciente: " . mysqli_error($link);
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
    }

    header("Location: ../secretaria/gestionar_pacientes.php");
    exit;
}
?>
