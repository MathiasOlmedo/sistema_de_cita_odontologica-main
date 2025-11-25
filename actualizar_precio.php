<?php
include_once('../php/conexionDB.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_odontograma = $_POST['id_odontograma'];  // El ID del procedimiento
    $precio = $_POST['precio'];  // El nuevo precio
    $observacion = $_POST['observacion'];  // La nueva observación

    // Actualizar el procedimiento en la tabla odontograma
    $sql = "UPDATE `odontograma` SET `precio` = '$precio', `observacion` = '$observacion' WHERE `id_odontograma` = '$id_odontograma'";

    if (mysqli_query($link, $sql)) {
        echo "Procedimiento actualizado exitosamente";
    } else {
        echo "Error al actualizar el procedimiento: " . mysqli_error($link);
    }
}
?>
