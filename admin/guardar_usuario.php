<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$nombre = $_POST['nombre'];
$rut = $_POST['rut'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$rol_id = $_POST['rol_id'];

$sql = "INSERT INTO usuarios (nombre,rut,password,rol_id)
        VALUES (?,?,?,?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi",$nombre,$rut,$password,$rol_id);
$stmt->execute();

header("Location: usuarios.php");