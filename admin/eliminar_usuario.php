<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: usuarios.php");