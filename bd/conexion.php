<?php

$host = "localhost";
$user = "liceotpg_jorge";
$pass = "Nini20254@";
$db = "calendario";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

?>