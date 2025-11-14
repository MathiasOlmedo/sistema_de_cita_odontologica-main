<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Perfect Teeth</title>
    <link href="src/css/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="src/css/lib/fontawesome/css/all.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .error-container h1 {
            font-size: 3rem;
            color: #dc3545;
        }
        .error-container p {
            font-size: 1.2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
        <h1>¡Oops! Algo salió mal.</h1>
        <p>Lo sentimos, pero hemos encontrado un error inesperado.</p>
        <p>Por favor, intente de nuevo más tarde o contacte al soporte si el problema persiste.</p>
        <a href="index.php" class="btn btn-primary mt-3">
            <i class="fas fa-home"></i> Volver al inicio
        </a>
    </div>
</body>
</html>
