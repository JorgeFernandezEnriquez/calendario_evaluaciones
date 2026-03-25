<?php
session_start();
require "../bd/conexion.php";

$rut = $_POST['rut'];
$password = $_POST['password'];

$sql = "SELECT id, nombre, password, rol_id 
        FROM usuarios 
        WHERE rut = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $rut);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows == 1){

    $user = $result->fetch_assoc();

    if(password_verify($password, $user['password'])){

        $_SESSION['usuario'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol_id'] = $user['rol_id'];

        if($user['rol_id'] == 1){
            header("Location: ../admin/dashboard.php");
            exit;
        }
        elseif($user['rol_id'] == 2){
            header("Location: ../profesor/dashboard.php");
            exit;
        }
        elseif($user['rol_id'] == 3){
            header("Location: ../utp/dashboard.php");
            exit;
        }

    }else{

        $_SESSION['error_login'] = "Contraseña incorrecta";
        header("Location: login.php");
        exit;

    }

}else{

    $_SESSION['error_login'] = "Usuario no encontrado";
    header("Location: login.php");
    exit;

}