<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');

// ad✅ Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ CORRECCIÓN: Redirigir SOLO si hay sesión válida y completa
if (isset($_SESSION['id_usuario']) && isset($_SESSION['tipo']) && !empty($_SESSION['tipo'])) {
    switch ($_SESSION['tipo']) {
        case 'Paciente':
            header("Location: principal.php");
            exit();
        case 'Doctor':
            header("Location: admin/inicioAdmin.php"); // ✅ Ruta corregida
            exit();
        case 'Secretaria':
            header("Location: secretaria/presupuestos_pendientes.php");
            exit();
        case 'SuperAdmin':
            header("Location: superadmin_dashboard.php");
            exit();
        default:
            // ✅ Si el tipo no es válido, destruir sesión y continuar al login
            session_destroy();
            session_start();
            break;
    }
}

// ✅ Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $vUsuario = trim(htmlspecialchars($_POST['username']));
    $vClave   = trim(htmlspecialchars($_POST['password']));

    // ✅ Login directo para secretaria (hardcoded)
    if ($vUsuario === 'secretaria@clinic.com' && $vClave === 'secret123') {
        $_SESSION['id_usuario'] = 1;
        $_SESSION['id_secretaria'] = 1; // ✅ AGREGADO: Variable requerida por presupuestos_pendientes.php
        $_SESSION['nombre']     = 'Secretaria';
        $_SESSION['tipo']       = 'Secretaria';
        header("Location: secretaria/presupuestos_pendientes.php");
        exit();
    }

    // ✅ Verificación en base de datos
    $usuario = validarLogin($link, $vUsuario, $vClave);
    
    // ✅ Si validarLogin() ya redirigió, este código no se ejecuta
    // Si llegamos aquí, es porque falló el login
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Perfect Teeth - Iniciar Sesión</title>
    <link rel="icon" href="./src/img/logo.png" type="image/png" />
    <link rel="stylesheet" href="src/css/login.css" />
    <link href="src/css/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="src/css/lib/fontawesome/css/all.css">
</head>
<body>
    <div class="container login-container">
        <div class="row">
            <div class="col-md-6 ads">
                <h1><span id="fl">Perfect</span><span id="sl"> Teeth</span></h1>
            </div>

            <div class="col-md-6 login-form">
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

                <?php if (isset($_SESSION['MensajeTexto']) && !empty($_SESSION['MensajeTexto'])): ?>
                    <div class="card mt-3">
                        <div class="notification <?php echo $_SESSION['MensajeTipo']; ?>">
                            <?php echo htmlspecialchars($_SESSION['MensajeTexto']); ?>
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