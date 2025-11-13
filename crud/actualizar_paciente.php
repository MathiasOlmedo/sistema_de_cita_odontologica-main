<?php
try {
    include_once('../php/conexionDB.php');
    include_once('../php/consultas.php');

    if (!empty($_GET['accion'])) {
        $opcion = $_GET['accion'];
    } else {
        session_start();
        $_SESSION['MensajeTexto'] = "Advertencia: Acción no permitida.";
        $_SESSION['MensajeTipo'] = "is-warning";
        header("Location: ../principal.php");
        exit();
    }

    switch ($opcion) {
        case 'UDT':
            $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            $nombre = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
            $apellido = filter_var($_POST['apellido'], FILTER_SANITIZE_STRING);
            $cedula = filter_var($_POST['cedula'], FILTER_SANITIZE_STRING);
            $telefono = filter_var($_POST['cell'], FILTER_SANITIZE_STRING);
            $sexo = filter_var($_POST['sexo'], FILTER_SANITIZE_STRING);
            $fecha = filter_var($_POST['nacimiento'], FILTER_SANITIZE_STRING);
            $correo = filter_var($_POST['correo'], FILTER_SANITIZE_STRING);
            $clave = filter_var($_POST['clave'], FILTER_SANITIZE_STRING);

            $query = "
                UPDATE `pacientes`
                SET 
                    `nombre` = '$nombre',
                    `apellido` = '$apellido',
                    `cedula` = '$cedula',
                    `telefono` = '$telefono',
                    `sexo` = '$sexo',
                    `fecha_nacimiento` = '$fecha',
                    `correo_electronico` = '$correo',
                    `clave` = '$clave'
                WHERE `id_paciente` = '$id'
            ";

            $resultado = mysqli_query($link, $query);
            session_start();

            if (!$resultado) {
                $_SESSION['MensajeTexto'] = "Error actualizando el registro: " . mysqli_error($link);
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
                header("Location: ../principal.php");
                exit();
            } else {
                $_SESSION['MensajeTexto'] = "Registro actualizado con éxito.";
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-info text-white";
                header("Location: ../principal.php");
                exit();
            }

            mysqli_close($link);
            break;

        default:
            session_start();
            $_SESSION['MensajeTexto'] = "Advertencia: No se pudo identificar la acción a realizar.";
            $_SESSION['MensajeTipo'] = "is-warning";
            header("Location: ../principal.php");
            exit();
            break;
    }
} catch (Exception $e) {
    print "Excepción no controlada: " . $e->getMessage();
    print "Estamos trabajando en corregir esta situación.";
} catch (Error $e) {
    print "Error no controlado: " . $e->getMessage();
    print "Estamos trabajando en corregir esta situación.";
}
?>
