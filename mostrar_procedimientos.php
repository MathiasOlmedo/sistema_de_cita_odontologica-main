<?php
include_once('../php/conexionDB.php');

$id_paciente = $_GET['id_paciente'];  // Recibir el ID del paciente desde la URL

$sql = "SELECT * FROM `odontograma` WHERE `id_paciente` = '$id_paciente'";
$result = mysqli_query($link, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td> Diente " . $row['diente'] . "</td>";
        echo "<td>" . $row['lado'] . "</td>";
        echo "<td>" . $row['procedimiento'] . "</td>";
        echo "<td>" . $row['precio'] . "</td>";
        echo "<td>" . $row['observacion'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "No se han registrado procedimientos para este paciente.";
}
?>
