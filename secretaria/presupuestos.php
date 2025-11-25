<?php
// secretaria/presupuestos.php
include_once __DIR__ . '/../php/conexionDB.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/*
 * Ajusta este chequeo a tu sistema de roles.
 * Por ahora permitimos acceso si hay sesión de cualquier usuario (doctor/admin/secretaria).
 */
if (!isset($_SESSION['id_doctor']) && !isset($_SESSION['id_secretaria']) && !isset($_SESSION['id_admin'])) {
    header('Location: ../login.php'); exit;
}

$pres = $link->query("
  SELECT p.id_presupuesto, p.folio, p.fecha, p.paciente_nombre, p.paciente_correo, p.total, p.estado, p.pdf_path,
         CONCAT(d.nombreD,' ',d.apellido) AS doctor
  FROM presupuesto p
  LEFT JOIN doctor d ON d.id_doctor = p.id_doctor
  ORDER BY p.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Presupuestos · Secretaría</title>
<link rel="stylesheet" href="../src/css/lib/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../src/css/lib/fontawesome/css/all.min.css">
<script src="../src/js/jquery.js"></script>
<script src="../src/css/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<style>
  body{ background:#f8f9fa; }
  .wrap{ max-width:1100px; margin:24px auto; }
</style>
</head>
<body>
<div class="wrap">
  <h3 class="mb-3"><i class="fa-solid fa-file-invoice-dollar"></i> Presupuestos (Secretaría)</h3>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Folio</th>
              <th>Fecha</th>
              <th>Paciente</th>
              <th>Correo</th>
              <th>Doctor</th>
              <th class="text-end">Total</th>
              <th>Estado</th>
              <th>PDF</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $pres->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['folio']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                <td><?= htmlspecialchars($row['paciente_nombre']) ?></td>
                <td><?= htmlspecialchars($row['paciente_correo']) ?></td>
                <td><?= htmlspecialchars($row['doctor']) ?></td>
                <td class="text-end">$ <?= number_format((float)$row['total'],2) ?></td>
                <td>
                  <span class="badge bg-<?= $row['estado']==='aprobado'?'success':($row['estado']==='rechazado'?'danger':'secondary') ?>">
                    <?= htmlspecialchars($row['estado']) ?>
                  </span>
                </td>
                <td>
                  <?php if(!empty($row['pdf_path'])): ?>
                    <a class="btn btn-sm btn-outline-primary" href="../<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank">
                      <i class="fa-regular fa-file-pdf"></i> Abrir
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Sin PDF</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; $pres->free(); ?>
          </tbody>
        </table>
      </div>
      <a class="btn btn-light" href="../index.php">← Volver</a>
    </div>
  </div>
</div>
</body>
</html>
