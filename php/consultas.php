<?php
// Función para validar login de Paciente o Doctor
function validarLogin($link, $user, $pass)
{
    // Primero intentamos con Superadmin
    $querySuperadmin = "SELECT * FROM `superadmin` WHERE `correo_electronico` = '$user' AND `clave` = '$pass'";
    $resultadoSuperadmin = mysqli_query($link, $querySuperadmin);

    if (mysqli_num_rows($resultadoSuperadmin) == 1) {
        $row = mysqli_fetch_assoc($resultadoSuperadmin);
        $_SESSION['id_superadmin'] = $row['id_superadmin']; // Guardamos el id del superadmin en la sesión

        $_SESSION['MensajeTexto'] = null;
        $_SESSION['MensajeTipo'] = null;

        // Redirigir al superadmin
        header("Location: /sistema_de_cita_odontologica-main/superadmin_dashboard.php");
        exit();
    } else {
        // Intentamos con Paciente
        $queryPaciente = "SELECT * FROM `pacientes` WHERE `correo_electronico` = '$user' AND `clave` = '$pass'";
        $resultadoPaciente = mysqli_query($link, $queryPaciente);

        if (mysqli_num_rows($resultadoPaciente) == 1) {
            $row = mysqli_fetch_assoc($resultadoPaciente);
            $_SESSION['id_paciente'] = $row['id_paciente'];

            $_SESSION['MensajeTexto'] = null;
            $_SESSION['MensajeTipo'] = null;

            header("Location: /sistema_de_cita_odontologica-main/principal.php");
            exit();
        } else {
            // Intentamos con Doctor
            $queryDoctor = "SELECT * FROM `doctor` WHERE `correo_eletronico` = '$user' AND `clave` = '$pass'";
            $resultadoDoctor = mysqli_query($link, $queryDoctor);

            if (mysqli_num_rows($resultadoDoctor) == 1) {
                $row = mysqli_fetch_assoc($resultadoDoctor);
                $_SESSION['id_doctor'] = $row['id_doctor'];

                $_SESSION['MensajeTexto'] = null;
                $_SESSION['MensajeTipo'] = null;

                header("Location: /sistema_de_cita_odontologica-main/admin/inicioAdmin.php");
                exit();
            } else {
                // Si no es paciente ni doctor ni superadmin
                $_SESSION['MensajeTexto'] = "Usuario o contraseña incorrectos";
                $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
            }
        }
    }
}

// Función para consultar datos de un paciente
function consultarPaciente($link, $id)
{
    $query = "SELECT * FROM `pacientes` WHERE `id_paciente` = '$id'";
    $resultado = mysqli_query($link, $query);

    if (mysqli_num_rows($resultado) == 1) {
        $row = mysqli_fetch_assoc($resultado);
        return $row;
    } else {
        $_SESSION['MensajeTexto'] = "Error validando datos del paciente";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        header("Location: /sistema_de_cita_odontologica-main/index.php");
        exit();
    }
}

// Función para consultar datos de un doctor
function consultarDoctor($link, $id)
{
    $query = "SELECT * FROM `doctor` WHERE `id_doctor` = '$id'";
    $resultado = mysqli_query($link, $query);

    if (mysqli_num_rows($resultado) == 1) {
        $row = mysqli_fetch_assoc($resultado);
        return $row;
    } else {
        $_SESSION['MensajeTexto'] = "Error validando datos del doctor";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        header("Location: /sistema_de_cita_odontologica-main/index.php");
        exit();
    }
}

// Funciones para mostrar datos de la base
function MostrarConsultas($link)
{
    $query = "SELECT * FROM `consultas`";
    return mysqli_query($link, $query);
}

function MostrarEspecialidad($link)
{
    $query = "SELECT * FROM `especialidad`";
    return mysqli_query($link, $query);
}

function MostrarDentistas($link)
{
    $query = "SELECT * FROM `doctor`";
    return mysqli_query($link, $query);
}

function MostrarPacientes($link)
{
    $query = "SELECT * FROM `pacientes`";
    return mysqli_query($link, $query);
}

function MostrarCitas($link, $id)
{
    $query = "
        SELECT  
            c.id_cita,
            p.nombre,
            p.apellido,
            d.nombreD,
            p.fecha_nacimiento,
            c.fecha_cita,
            c.hora_cita,
            con.tipo, 
            c.estado,
            YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años,
            pd.descripcion
        FROM 
            `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE d.id_doctor = $id;
    ";
    return mysqli_query($link, $query);
}

function ConsultarCitas($link, $id)
{
    $query = "SELECT * FROM `citas` WHERE `id_cita` = '$id'";
    $resultado = mysqli_query($link, $query);

    if (mysqli_num_rows($resultado) == 1) {
        return mysqli_fetch_assoc($resultado);
    } else {
        $_SESSION['MensajeTexto'] = "Error consultando cita";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        return false;
    }
}

function CitasPendientesFPDF($link, $id)
{
    $query = "
        SELECT  
            c.id_cita,
            c.estado,
            p.nombre,
            p.apellido,
            d.nombreD,
            p.fecha_nacimiento,
            c.fecha_cita,
            c.hora_cita,
            con.tipo, 
            pd.descripcion,
            YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años
        FROM 
            `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE c.estado = 'I' AND p.id_paciente = $id;
    ";
    return mysqli_query($link, $query);
}

function CitasRealizadasFPDF($link, $id)
{
    $query = "
        SELECT  
            c.id_cita,
            c.estado,
            p.nombre,
            p.apellido,
            d.nombreD,
            p.fecha_nacimiento,
            c.fecha_cita,
            c.hora_cita,
            con.tipo, 
            pd.descripcion,
            pd.medicina,
            YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años
        FROM 
            `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE c.estado = 'A' AND p.id_paciente = $id;
    ";
    return mysqli_query($link, $query);
}
?>
