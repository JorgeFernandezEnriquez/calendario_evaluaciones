<?php
require "../bd/conexion.php";

$nombre = $_POST['nombre'];
$rut = $_POST['rut'];
$password = $_POST['password'];
$rol_id = $_POST['rol_id'];

$password_hash = password_hash($password, PASSWORD_DEFAULT);

/* verificar si el rut ya existe */

$sql = "SELECT id FROM usuarios WHERE rut = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$rut);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){

    echo "El usuario ya existe";
    exit;

}

/* insertar usuario */

$sql = "INSERT INTO usuarios (nombre,rut,password,rol_id)
        VALUES (?,?,?,?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi",$nombre,$rut,$password_hash,$rol_id);

if($stmt->execute()){

    echo "Usuario registrado correctamente";

}else{

    echo "Error al registrar usuario";

}