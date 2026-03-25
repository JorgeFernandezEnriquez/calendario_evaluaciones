<?php

session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: ../login/login.php");
    exit;
}

if($_SESSION['rol_id'] != 1){
    header("Location: ../index.php");
    exit;
}