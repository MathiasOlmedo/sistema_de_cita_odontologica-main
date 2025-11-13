<?php
try {
    include_once('../php/conexionDB.php');
    include_once('../php/consultas.php');
    if (!empty($_GET['opciones'])) {
        $opcion = $_GET['opciones'];
    } else {
        session_start();
        $_SESSION['MensajeTexto'] = "Advertencia: Acción no permitida";
        $_SESSION['MensajeTipo'] = "is-warning";
        header("Location: ../index.php");
        exit();
    }

    switch ($opcion) {
        case 'INS':
            if (isset($_POST['ingresar'])) {
                $nombre = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
                $apellido = filter_var($_POST['apellido'], FILTER_SANITIZE_STRING);
                $cedula = filter_var($_POST['cedula'], FILTER_SANITIZE_STRING);
                $telefono = filter_var($_POST['cell'], FILTER_SANITIZE_STRING);
                $sexo = filter_var($_POST['sexo'], FILTER_SANITIZE_STRING);
                $fecha = filter_var($_POST['nacimiento'], FILTER_SANITIZE_STRING);
                $correo = filter_var($_POST['correo'], FILTER_SANITIZE_STRING);
                $clave = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

                $query = "
                    INSERT INTO `pacientes`
                    (`nombre`, `apellido`, `cedula`, `telefono`, `sexo`, `fecha_nacimiento`, `correo_electronico`, `clave`)
                    VALUES ('$nombre', '$apellido', '$cedula', '$telefono', '$sexo', '$fecha', '$correo', '$clave')";
            }

            $resultado = mysqli_query($link, $query);
            session_start();
            if (!$resultado) {
                $_SESSION['MensajeTexto'] = "Error insertando el contenido: " . mysqli_error($link);
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                header("Location: ../index.php");
            } else {
                $_SESSION['MensajeTexto'] = "Registro almacenado con éxito, por favor inicie sesión.";
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-info text-white";
                header("Location: ../index.php");
            }
            mysqli_close($link);
            break;

        case 'INSDOCT':
            if (isset($_POST['guardar'])) {
                $nombre = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
                $apellido = filter_var($_POST['apellido'], FILTER_SANITIZE_STRING);
                $sexo = filter_var($_POST['sexo'], FILTER_SANITIZE_STRING);
                $fecha = filter_var($_POST['nacimiento'], FILTER_SANITIZE_STRING);
                $correo = filter_var($_POST['correo'], FILTER_SANITIZE_STRING);
                $telefono = filter_var($_POST['cell'], FILTER_SANITIZE_STRING);
                $clave = filter_var($_POST['clave'], FILTER_SANITIZE_STRING);
                $especialidad = filter_var($_POST['especialidad'], FILTER_SANITIZE_STRING);

                $query = "
                    INSERT INTO `doctor`
                    (`nombreD`, `apellido`, `sexo`, `fecha_nacimiento`, `telefono`, `correo_eletronico`, `clave`, `id_especialidad`)
                    VALUES ('$nombre', '$apellido', '$sexo', '$fecha', '$telefono', '$correo', '$clave', '$especialidad')";
            }

            $resultado = mysqli_query($link, $query);
            session_start();
            if (!$resultado) {
                $_SESSION['MensajeTexto'] = "Error insertando el contenido: " . mysqli_error($link);
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                header("Location: ../admin/doctores.php");
            } else {
                $_SESSION['MensajeTexto'] = "Registro almacenado con éxito.";
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-info text-white";
                header("Location: ../admin/doctores.php");
            }
            mysqli_close($link);
            break;

        default:
            session_start();
            $_SESSION['MensajeTexto'] = "Advertencia: No se pudo identificar la acción a realizar";
            $_SESSION['MensajeTipo'] = "bg-warning text-dark";
            header("Location: ../index.php");
            break;
    }
} catch (Exception $e) {
    print "Exception no controlado 01: " . $e->getMessage();
} catch (Error $e) {
    print "Error no controlado 01: " . $e->getMessage();
}
