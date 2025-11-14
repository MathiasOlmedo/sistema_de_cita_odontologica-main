<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya hay una sesión activa, redirigir según el tipo
if (isset($_SESSION['id_usuario']) && isset($_SESSION['tipo'])) {
    switch ($_SESSION['tipo']) {
        case 'Paciente':
            header("Location: principal.php");
            exit();
        case 'Doctor':
            header("Location: admin/inicioAdmin.php"); // Corregido para doctores
            exit();
        case 'Secretaria':
            header("Location: secretaria/presupuestos_pendientes.php");
            exit();
        case 'SuperAdmin':
            header("Location: superadmin_dashboard.php");
            exit();
    }
}

// Si el usuario envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usar filter_input para más seguridad al leer variables POST
    $vUsuario = filter_input(INPUT_POST, 'username', FILTER_VALIDATE_EMAIL);
    $vClave   = $_POST['password']; // No se filtra para que password_verify funcione

    if ($vUsuario === false) {
        $_SESSION['MensajeTexto'] = "Formato de correo electrónico no válido.";
        $_SESSION['MensajeTipo']  = "alert alert-danger";
    } else {
        // Verificación en base de datos usando la nueva función segura
        $usuario = validarLogin($link, $vUsuario, $vClave);

        if ($usuario) {
            // Login exitoso, establecer variables de sesión
            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['tipo']       = $usuario['tipo'];

            // Redirección según el tipo de usuario
            switch ($usuario['tipo']) {
                case 'Paciente':
                    header("Location: principal.php");
                    exit();
                case 'Doctor':
                    header("Location: admin/inicioAdmin.php"); // Ruta correcta para doctores
                    exit();
                case 'Secretaria':
                    // Asumiendo que hay una tabla y rol para secretaria
                    header("Location: secretaria/presupuestos_pendientes.php");
                    exit();
                case 'SuperAdmin':
                    header("Location: superadmin_dashboard.php");
                    exit();
            }
        } else {
            // Falla en el login
            $_SESSION['MensajeTexto'] = "Usuario o contraseña incorrectos.";
            $_SESSION['MensajeTipo']  = "alert alert-danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perfect Teeth - Iniciar Sesión</title>

    <!-- ICONO -->
    <link rel="icon" href="./src/img/logo.png" type="image/png" />

    <!-- CSS -->
    <link rel="stylesheet" href="src/css/login.css" />
    <link href="src/css/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="src/css/lib/fontawesome/css/all.css">
</head>

<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-6 ads d-none d-md-block">
                <h1><span id="fl">Perfect</span><span id="sl">Teeth</span></h1>
            </div>

            <div class="col-12 col-md-6 login-form">
                <div class="profile-img">
                    <img src="src/img/logo.png" alt="profile_img" height="120px" width="120px">
                </div>
                <h3>Iniciar sesión</h3>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="username" class="font-weight-bold">Correo Electrónico</label>
                        <input type="email" class="form-control" name="username" id="username"
                               placeholder="Correo electrónico" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="font-weight-bold">Contraseña</label>
                        <input type="password" class="form-control" name="password" id="password"
                               placeholder="Contraseña" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                        </button>
                    </div>

                    <div class="form-group text-center">
                        <p>¿No tienes una cuenta?</p>
                        <a href="registro.php"><i class="fas fa-user-plus"></i> Registrarse</a>
                    </div>

                    <div class="form-group text-left">
                        <a href="pagina.php">Volver</a>
                    </div>
                </form>

                <?php if (isset($_SESSION['MensajeTexto'])): ?>
                    <div class="card mt-3">
                        <div class="notification <?php echo $_SESSION['MensajeTipo']; ?>">
                            <?php echo $_SESSION['MensajeTexto']; ?>
                            <button class="delete"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <?php
                    unset($_SESSION['MensajeTexto']);
                    unset($_SESSION['MensajeTipo']);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
                const $notification = $delete.parentNode;
                $delete.addEventListener('click', () => {
                    $notification.remove();
                });
            });
        });
    </script>
</body>

</html>
