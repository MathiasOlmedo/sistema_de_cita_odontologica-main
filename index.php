<?php
include_once('./php/conexionDB.php');
include_once('./php/consultas.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya hay una sesión activa, redirigir según el tipo
if (isset($_SESSION['id_usuario']) && isset($_SESSION['tipo'])) {
    // Establecer variables de sesión específicas si faltan
    switch ($_SESSION['tipo']) {
        case 'Paciente':
            if (!isset($_SESSION['id_paciente'])) {
                $_SESSION['id_paciente'] = $_SESSION['id_usuario'];
            }
            header("Location: principal.php");
            exit();
        case 'Doctor':
            if (!isset($_SESSION['id_doctor'])) {
                $_SESSION['id_doctor'] = $_SESSION['id_usuario'];
            }
            header("Location: Admin/inicioAdmin.php");
            exit();
        case 'Secretaria':
            if (!isset($_SESSION['id_secretaria'])) {
                $_SESSION['id_secretaria'] = $_SESSION['id_usuario'];
            }
            header("Location: secretaria/presupuestos_pendientes.php");
            exit();
        case 'SuperAdmin':
            if (!isset($_SESSION['id_admin'])) {
                $_SESSION['id_admin'] = $_SESSION['id_usuario'];
            }
            header("Location: superadmin_dashboard.php");
            exit();
    }
}

// Si el usuario envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vUsuario = filter_input(INPUT_POST, 'username', FILTER_VALIDATE_EMAIL);
    $vClave   = $_POST['password'];

    if ($vUsuario === false) {
        $_SESSION['MensajeTexto'] = "Formato de correo electrónico no válido.";
        $_SESSION['MensajeTipo']  = "alert alert-danger";
    } else {
        $usuario = validarLogin($link, $vUsuario, $vClave);

        if ($usuario) {
            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['tipo']       = $usuario['tipo'];

            switch ($usuario['tipo']) {
                case 'Paciente':
                    $_SESSION['id_paciente'] = $usuario['id'];
                    header("Location: principal.php");
                    exit();
                case 'Doctor':
                    $_SESSION['id_doctor'] = $usuario['id'];
                    header("Location: Admin/inicioAdmin.php");
                    exit();
                case 'Secretaria':
                    $_SESSION['id_secretaria'] = $usuario['id'];
                    header("Location: secretaria/presupuestos_pendientes.php");
                    exit();
                case 'SuperAdmin':
                    $_SESSION['id_admin'] = $usuario['id'];
                    header("Location: superadmin_dashboard.php");
                    exit();
            }
        } else {
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

    <style>
        /* ============================================
           ESTILOS MEJORADOS PARA NOTIFICACIONES
           ============================================ */

        .notification-card {
            margin-top: 1.5rem !important;
            border: none !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            animation: slideIn 0.4s ease-out !important;
        }

        .notification-card:hover {
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-4px) !important;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* NOTIFICACIÓN BASE */
        .notification {
            padding: 1.25rem 1.5rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1rem !important;
            border-radius: 12px !important;
            font-weight: 500 !important;
            letter-spacing: 0.3px !important;
            border: none !important;
            background-color: transparent !important;
        }

        /* PELIGRO / ERROR */
        .notification.alert-danger {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%) !important;
            color: #c92a2a !important;
            border-left: 5px solid #c92a2a !important;
        }

        .notification.alert-danger:hover {
            background: linear-gradient(135deg, #ffe0e0 0%, #ffc9c9 100%) !important;
        }

        /* ÉXITO */
        .notification.alert-success {
            background: linear-gradient(135deg, #f1fdf4 0%, #d3f9d8 100%) !important;
            color: #2f9e44 !important;
            border-left: 5px solid #2f9e44 !important;
        }

        .notification.alert-success:hover {
            background: linear-gradient(135deg, #d3f9d8 0%, #b2f2bb 100%) !important;
        }

        /* ADVERTENCIA */
        .notification.alert-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #ffd666 100%) !important;
            color: #e67700 !important;
            border-left: 5px solid #e67700 !important;
        }

        .notification.alert-warning:hover {
            background: linear-gradient(135deg, #ffd666 0%, #ffc233 100%) !important;
        }

        /* INFORMACIÓN */
        .notification.alert-info {
            background: linear-gradient(135deg, #e7f5ff 0%, #b3e5fc 100%) !important;
            color: #1971c2 !important;
            border-left: 5px solid #1971c2 !important;
        }

        .notification.alert-info:hover {
            background: linear-gradient(135deg, #b3e5fc 0%, #81d4fa 100%) !important;
        }

        /* CONTENEDOR DE TEXTO */
        .notification-text {
            flex: 1 !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        /* ICONO */
        .notification-icon {
            font-size: 1.5rem !important;
            display: flex !important;
            align-items: center !important;
            flex-shrink: 0 !important;
        }

        /* BOTÓN DE CIERRE */
        .notification .delete {
            background: none !important;
            border: none !important;
            font-size: 1.3rem !important;
            cursor: pointer !important;
            opacity: 0.6 !important;
            transition: all 0.2s ease !important;
            padding: 0.5rem !important;
            width: 36px !important;
            height: 36px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 8px !important;
            color: inherit !important;
            flex-shrink: 0 !important;
        }

        .notification .delete:hover {
            opacity: 1 !important;
            background-color: rgba(0, 0, 0, 0.12) !important;
            transform: scale(1.1) !important;
        }

        .notification .delete:active {
            transform: scale(0.95) !important;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .notification {
                padding: 1rem 1.25rem !important;
                flex-direction: row !important;
            }

            .notification-text {
                gap: 0.5rem !important;
            }

            .notification-icon {
                font-size: 1.25rem !important;
            }
        }
    </style>
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
                    <div class="notification-card">
                        <div class="notification <?php echo $_SESSION['MensajeTipo']; ?>">
                            <div class="notification-text">
                                <span class="notification-icon">
                                    <i class="fas <?php echo strpos($_SESSION['MensajeTipo'], 'danger') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                                </span>
                                <span><?php echo $_SESSION['MensajeTexto']; ?></span>
                            </div>
                            <button type="button" class="delete" aria-label="Cerrar notificación">
                                <i class="fas fa-times"></i>
                            </button>
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
                $delete.addEventListener('click', (e) => {
                    e.preventDefault();
                    const $notification = $delete.closest('.notification-card');
                    $notification.style.animation = 'slideIn 0.3s ease-out reverse';
                    setTimeout(() => {
                        $notification.remove();
                    }, 300);
                });
            });
        });
    </script>
</body>

</html>