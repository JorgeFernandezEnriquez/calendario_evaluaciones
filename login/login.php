<?php
session_start();

$error = "";

if(isset($_SESSION['error_login'])){
    $error = $_SESSION['error_login'];
    unset($_SESSION['error_login']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<title>Login</title>

<link rel="stylesheet" href="../css/login.css">

</head>

<body>

<div class="login-container">

<h2>Iniciar sesión</h2>

<?php if($error != ""){ ?>
<div class="error">
    <?php echo $error; ?>
</div>
<?php } ?>

<form method="POST" action="validar_login.php">

<input 
type="text"
name="rut"
placeholder="RUT"
required
pattern="[0-9]+"
title="Ingrese solo números"
oninput="this.value=this.value.replace(/[^0-9]/g,'')"
>

<input 
type="password"
name="password"
placeholder="Contraseña"
required
>

<button type="submit">
Ingresar
</button>

</form>

</div>

</body>
</html>