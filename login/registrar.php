
<?php
require "../bd/conexion.php";
session_start();

if($_SESSION['rol'] != 'admin'){
    header("Location: ../index.php");
    exit;
}
$roles = $conn->query("SELECT id, nombre FROM roles");
?>

<!DOCTYPE html>
<html>
<head>

<title>Registrar usuario</title>
<link rel="stylesheet" href="../css/login.css">

</head>

<body>

<div class="login-container">

<h2>Registrar usuario</h2>

<form method="POST" action="guardar_usuario.php">

<input type="text" name="nombre" placeholder="Nombre completo" required>

<input type="text" name="rut" placeholder="RUT" required>

<input type="password" name="password" placeholder="Contraseña" required>

<select name="rol_id" required>

<option value="">Seleccione rol</option>

<?php while($r = $roles->fetch_assoc()){ ?>

<option value="<?php echo $r['id']; ?>">
<?php echo $r['nombre']; ?>
</option>

<?php } ?>

</select>

<button type="submit">Registrar</button>

</form>

</div>

</body>
</html>