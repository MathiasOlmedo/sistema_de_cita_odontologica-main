<?php
try {
    include_once('../php/conexionDB.php');

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_GET['opciones'])) {
        $_SESSION['MensajeTexto'] = "Advertencia: Acción no permitida";
        $_SESSION['MensajeTipo'] = "is-warning";
        header("Location: ../index.php");
        exit();
    }

    $opcion = $_GET['opciones'];

    switch ($opcion) {
        case 'INS':
            if (isset($_POST['ingresar'])) {
                $nombre = $_POST['name'];
                $apellido = $_POST['apellido'];
                $cedula = $_POST['cedula'];
                $telefono = $_POST['cell'];
                $sexo = $_POST['sexo'];
                $fecha = $_POST['nacimiento'];
                $correo = $_POST['correo'];
                $clave_hasheada = password_hash($_POST['password'], PASSWORD_BCRYPT);

                $query = "INSERT INTO `pacientes` (`nombre`, `apellido`, `cedula`, `telefono`, `sexo`, `fecha_nacimiento`, `correo_electronico`, `clave`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($link, $query);
                if ($stmt === false) {
                    error_log("Error in mysqli_prepare for registration: " . mysqli_error($link));
                    $_SESSION['MensajeTexto'] = "Error al preparar la consulta. Intente de nuevo.";
                    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                    header("Location: ../index.php");
                    exit();
                }
                mysqli_stmt_bind_param($stmt, "ssssssss", $nombre, $apellido, $cedula, $telefono, $sexo, $fecha, $correo, $clave_hasheada);
                $resultado = mysqli_stmt_execute($stmt);

                if (!$resultado) {
                    error_log("Error en registro INS: " . mysqli_stmt_error($stmt));
                    $_SESSION['MensajeTexto'] = "Error al registrar. Intente de nuevo.";
                    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                } else {
                    $_SESSION['MensajeTexto'] = "Registro almacenado con éxito, por favor inicie sesión.";
                    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-info text-white";
                }
                mysqli_stmt_close($stmt);
                header("Location: ../index.php");
                exit();
            }
            break;

        case 'INSDOCT':
            if (isset($_POST['guardar'])) {
                $nombre = $_POST['name'];
                $apellido = $_POST['apellido'];
                $sexo = $_POST['sexo'];
                $fecha = $_POST['nacimiento'];
                $correo = $_POST['correo'];
                $telefono = $_POST['cell'];
                $clave_hasheada = password_hash($_POST['clave'], PASSWORD_BCRYPT);
                $especialidad = $_POST['especialidad'];

                $query = "INSERT INTO `doctor` (`nombreD`, `apellido`, `sexo`, `fecha_nacimiento`, `telefono`, `correo_eletronico`, `clave`, `id_especialidad`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($link, $query);
                if ($stmt === false) {
                    error_log("Error in mysqli_prepare for doctor registration: " . mysqli_error($link));
                    $_SESSION['MensajeTexto'] = "Error al preparar la consulta para registrar el doctor.";
                    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                    header("Location: ../admin/doctores.php");
                    exit();
                }
                // Asumiendo que id_especialidad es un entero (i)
                mysqli_stmt_bind_param($stmt, "sssssssi", $nombre, $apellido, $sexo, $fecha, $telefono, $correo, $clave_hasheada, $especialidad);
                $resultado = mysqli_stmt_execute($stmt);

                if (!$resultado) {
                    error_log("Error en registro INSDOCT: " . mysqli_stmt_error($stmt));
                    $_SESSION['MensajeTexto'] = "Error al registrar el doctor.";
                    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                } else {
                    $_SESSION['MensajeTexto'] = "Registro de doctor almacenado con éxito.";
                    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-info text-white";
                }
                mysqli_stmt_close($stmt);
                header("Location: ../admin/doctores.php");
                exit();
            }
            break;

        default:
            $_SESSION['MensajeTexto'] = "Advertencia: No se pudo identificar la acción a realizar";
            $_SESSION['MensajeTipo'] = "bg-warning text-dark";
            header("Location: ../index.php");
            exit();
    }

    mysqli_close($link);

} catch (Exception $e) {
    error_log("Exception no controlado 01: " . $e->getMessage());
    // Redirigir a una página de error genérica para no exponer detalles
    header("Location: ../error.php"); 
    exit();
} catch (Error $e) {
    error_log("Error no controlado 01: " . $e->getMessage());
    header("Location: ../error.php");
    exit();
}
