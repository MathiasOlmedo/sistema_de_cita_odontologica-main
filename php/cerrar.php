<?php
session_start();
session_destroy(); //para cerrar session

header("Location: /sistema_de_cita_odontologica-main//index.php");
