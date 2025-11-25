<?php
// ✅ VERSIÓN FINAL - ESTANDARIZACIÓN COMPLETA
function validarLogin($link, $user, $pass)
{
    // 1️⃣ Intentar login como Superadmin
    $querySuperadmin = "SELECT * FROM `superadmin` WHERE `correo_electronico` = '$user' AND `clave` = '$pass'";
    $resultadoSuperadmin = mysqli_query($link, $querySuperadmin);

    if (mysqli_num_rows($resultadoSuperadmin) == 1) {
        $row = mysqli_fetch_assoc($resultadoSuperadmin);
        
        // ✅ ESTANDARIZACIÓN: Variables UNIFICADAS
        $_SESSION['id_usuario'] = $row['id_superadmin'];
        $_SESSION['id_superadmin'] = $row['id_superadmin']; // ✅ MANTENER para compatibilidad
        $_SESSION['nombre'] = 'Super Admin';
        $_SESSION['tipo'] = 'SuperAdmin';
        $_SESSION['usuario'] = 'Super Admin'; // ✅ AGREGAR para dashboard
        
        $_SESSION['MensajeTexto'] = null;
        $_SESSION['MensajeTipo'] = null;

        header("Location: /sistema_de_cita_odontologica-main/superadmin_dashboard.php");
        exit();
    }

    // 2️⃣ Intentar login como Paciente
    $queryPaciente = "SELECT * FROM `pacientes` WHERE `correo_electronico` = '$user' AND `clave` = '$pass'";
    $resultadoPaciente = mysqli_query($link, $queryPaciente);

    if (mysqli_num_rows($resultadoPaciente) == 1) {
        $row = mysqli_fetch_assoc($resultadoPaciente);
        
        $_SESSION['id_usuario'] = $row['id_paciente'];
        $_SESSION['id_paciente'] = $row['id_paciente']; // ✅ MANTENER para compatibilidad
        $_SESSION['nombre'] = $row['nombre'];
        $_SESSION['tipo'] = 'Paciente';

        $_SESSION['MensajeTexto'] = null;
        $_SESSION['MensajeTipo'] = null;

        header("Location: /sistema_de_cita_odontologica-main/principal.php");
        exit();
    }

    // 3️⃣ Intentar login como Doctor
    $queryDoctor = "SELECT * FROM `doctor` WHERE `correo_eletronico` = '$user' AND `clave` = '$pass'";
    $resultadoDoctor = mysqli_query($link, $queryDoctor);

    if (mysqli_num_rows($resultadoDoctor) == 1) {
        $row = mysqli_fetch_assoc($resultadoDoctor);
        
        $_SESSION['id_usuario'] = $row['id_doctor'];
        $_SESSION['id_doctor'] = $row['id_doctor']; // ✅ MANTENER para compatibilidad
        $_SESSION['nombre'] = $row['nombreD'];
        $_SESSION['tipo'] = 'Doctor';

        $_SESSION['MensajeTexto'] = null;
        $_SESSION['MensajeTipo'] = null;

        header("Location: /sistema_de_cita_odontologica-main/admin/inicioAdmin.php");
        exit();
    }

    // ❌ Login fallido
    $_SESSION['MensajeTexto'] = "Usuario o contraseña incorrectos";
    $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
    return false;
}

// Resto de funciones sin cambios
function consultarPaciente($link, $id)
{
    $query = "SELECT * FROM `pacientes` WHERE `id_paciente` = '$id'";
    $resultado = mysqli_query($link, $query);

    if (mysqli_num_rows($resultado) == 1) {
        return mysqli_fetch_assoc($resultado);
    } else {
        $_SESSION['MensajeTexto'] = "Error validando datos del paciente";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        header("Location: /sistema_de_cita_odontologica-main/index.php");
        exit();
    }
}

function consultarDoctor($link, $id)
{
    $query = "SELECT * FROM `doctor` WHERE `id_doctor` = '$id'";
    $resultado = mysqli_query($link, $query);

    if (mysqli_num_rows($resultado) == 1) {
        return mysqli_fetch_assoc($resultado);
    } else {
        $_SESSION['MensajeTexto'] = "Error validando datos del doctor";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        header("Location: /sistema_de_cita_odontologica-main/index.php");
        exit();
    }
}

function MostrarConsultas($link) { return mysqli_query($link, "SELECT * FROM `consultas`"); }
function MostrarEspecialidad($link) { return mysqli_query($link, "SELECT * FROM `especialidad`"); }
function MostrarDentistas($link) { return mysqli_query($link, "SELECT * FROM `doctor`"); }
function MostrarPacientes($link) { return mysqli_query($link, "SELECT * FROM `pacientes`"); }

function MostrarCitas($link, $id)
{
    $query = "SELECT c.id_cita, p.nombre, p.apellido, d.nombreD, p.fecha_nacimiento, c.fecha_cita, c.hora_cita, con.tipo, c.estado, YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años, pd.descripcion
        FROM `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE d.id_doctor = $id";
    return mysqli_query($link, $query);
}

function ConsultarCitas($link, $id)
{
    $query = "SELECT * FROM `citas` WHERE `id_cita` = '$id'";
    $resultado = mysqli_query($link, $query);
    return (mysqli_num_rows($resultado) == 1) ? mysqli_fetch_assoc($resultado) : false;
}

function CitasPendientesFPDF($link, $id)
{
    return mysqli_query($link, "SELECT c.id_cita, c.estado, p.nombre, p.apellido, d.nombreD, p.fecha_nacimiento, c.fecha_cita, c.hora_cita, con.tipo, pd.descripcion, YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años FROM `citas` AS c LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita WHERE c.estado = 'I' AND p.id_paciente = $id");
}

function CitasRealizadasFPDF($link, $id)
{
    return mysqli_query($link, "SELECT c.id_cita, c.estado, p.nombre, p.apellido, d.nombreD, p.fecha_nacimiento, c.fecha_cita, c.hora_cita, con.tipo, pd.descripcion, pd.medicina, YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años FROM `citas` AS c LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita WHERE c.estado = 'A' AND p.id_paciente = $id");
}
?>