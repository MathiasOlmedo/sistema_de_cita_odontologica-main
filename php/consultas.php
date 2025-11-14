<?php

/**
 * Valida las credenciales de un usuario (Paciente, Doctor o Superadmin) de forma segura.
 *
 * @param mysqli $link Conexión a la base de datos.
 * @param string $email El correo electrónico del usuario.
 * @param string $pass La contraseña en texto plano.
 * @return array|false Un array con los datos del usuario si es válido, o false si no lo es.
 */
function validarLogin($link, $email, $pass)
{
    // Unificamos la búsqueda en las tres tablas para encontrar al usuario por su email
    // Usamos consultas preparadas para máxima seguridad.
    
    $user_data = null;
    $found_in = '';

    // 1. Buscar en Pacientes
    $stmt = $link->prepare("SELECT id_paciente as id, nombre, clave, 'Paciente' as tipo FROM `pacientes` WHERE `correo_electronico` = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $found_in = 'pacientes';
    }
    $stmt->close();

    // 2. Si no se encontró, buscar en Doctores
    if (!$user_data) {
        $stmt = $link->prepare("SELECT id_doctor as id, nombreD as nombre, clave, 'Doctor' as tipo FROM `doctor` WHERE `correo_eletronico` = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $found_in = 'doctor';
        }
        $stmt->close();
    }
    
    // 3. Si no se encontró, buscar en Superadmin
    if (!$user_data) {
        $stmt = $link->prepare("SELECT id_superadmin as id, nombre, clave, 'SuperAdmin' as tipo FROM `superadmin` WHERE `correo_electronico` = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $found_in = 'superadmin';
        }
        $stmt->close();
    }

    // 4. Verificar la contraseña
    if ($user_data && password_verify($pass, $user_data['clave'])) {
        // La contraseña es correcta, devolvemos los datos del usuario.
        return [
            'id' => $user_data['id'],
            'nombre' => $user_data['nombre'],
            'tipo' => $user_data['tipo']
        ];
    }

    // Si el usuario no existe o la contraseña es incorrecta
    return false;
}


// --- OTRAS FUNCIONES REFACTORIZADAS ---

function consultarPaciente($link, $id)
{
    $stmt = $link->prepare("SELECT * FROM `pacientes` WHERE `id_paciente` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows == 1) {
        return $resultado->fetch_assoc();
    } else {
        $_SESSION['MensajeTexto'] = "Error validando datos del paciente";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        header("Location: /sistema_de_cita_odontologica-main/index.php");
        exit();
    }
}

function consultarDoctor($link, $id)
{
    $stmt = $link->prepare("SELECT * FROM `doctor` WHERE `id_doctor` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows == 1) {
        return $resultado->fetch_assoc();
    } else {
        $_SESSION['MensajeTexto'] = "Error validando datos del doctor";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        header("Location: /sistema_de_cita_odontologica-main/index.php");
        exit();
    }
}

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
        SELECT c.id_cita, p.nombre, p.apellido, d.nombreD, p.fecha_nacimiento, c.fecha_cita, c.hora_cita, con.tipo, c.estado, YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años, pd.descripcion
        FROM `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE d.id_doctor = ?";
    
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
}

function ConsultarCitas($link, $id)
{
    $stmt = $link->prepare("SELECT * FROM `citas` WHERE `id_cita` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows == 1) {
        return $resultado->fetch_assoc();
    } else {
        $_SESSION['MensajeTexto'] = "Error consultando cita";
        $_SESSION['MensajeTipo'] = "p-3 mb-2 bg-danger text-white";
        return false;
    }
}

function CitasPendientesFPDF($link, $id)
{
    $query = "
        SELECT c.id_cita, c.estado, p.nombre, p.apellido, d.nombreD, p.fecha_nacimiento, c.fecha_cita, c.hora_cita, con.tipo, pd.descripcion, YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años
        FROM `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE c.estado = 'I' AND p.id_paciente = ?";
    
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
}

function CitasRealizadasFPDF($link, $id)
{
    $query = "
        SELECT c.id_cita, c.estado, p.nombre, p.apellido, d.nombreD, p.fecha_nacimiento, c.fecha_cita, c.hora_cita, con.tipo, pd.descripcion, pd.medicina, YEAR(CURDATE()) - YEAR(p.fecha_nacimiento) AS años
        FROM `citas` AS c
        LEFT JOIN `pacientes` AS p ON p.id_paciente = c.id_paciente
        LEFT JOIN `doctor` AS d ON d.id_doctor = c.id_doctor
        LEFT JOIN `consultas` AS con ON con.id_consultas = c.id_consultas
        LEFT JOIN `paciente_diagnostico` AS pd ON pd.id_cita = c.id_cita
        WHERE c.estado = 'A' AND p.id_paciente = ?";

    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
}
?>
