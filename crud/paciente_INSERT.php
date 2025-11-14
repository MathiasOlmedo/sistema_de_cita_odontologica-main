<?php
include_once('../php/conexionDB.php');
session_start();

if (isset($_POST['guardar'])) {
    // Los datos del formulario no necesitan ser escapados con consultas preparadas
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cedula = $_POST['cedula'];
    $telefono = $_POST['telefono'];
    $sexo = $_POST['sexo'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $correo_electronico = $_POST['correo_electronico'];

    // Hashear la contraseña para un almacenamiento seguro
    $clave_hasheada = password_hash($_POST['clave'], PASSWORD_BCRYPT);

    // 1. Preparar la consulta para prevenir inyección SQL
    $query = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, sexo, fecha_nacimiento, correo_electronico, clave)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($link, $query);

    // 2. Vincular los parámetros de forma segura
    // La cadena "ssssssss" indica que los 8 parámetros son strings
    mysqli_stmt_bind_param($stmt, "ssssssss", $nombre, $apellido, $cedula, $telefono, $sexo, $fecha_nacimiento, $correo_electronico, $clave_hasheada);

    // 3. Ejecutar la consulta
    $resultado = mysqli_stmt_execute($stmt);

    if ($resultado) {
        $_SESSION['MensajeTexto'] = "Paciente registrado correctamente.";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-success text-white";
    } else {
        // En un entorno de producción, es mejor registrar el error que mostrarlo al usuario
        error_log("Error al registrar paciente: " . mysqli_stmt_error($stmt));
        $_SESSION['MensajeTexto'] = "Error al registrar el paciente. Por favor, intente de nuevo.";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
    }

    // 4. Cerrar el statement
    mysqli_stmt_close($stmt);

    header("Location: ../secretaria/gestionar_pacientes.php");
    exit();
}
?>
