<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$roles = $conn->query("SELECT * FROM roles");
?>

<!DOCTYPE html>
<html>
<head>

<title>Crear usuario</title>
<link rel="stylesheet" href="../css/panel.css">

</head>

<body>

<div class="main">

<h1>Crear usuario</h1>

<form method="POST" action="guardar_usuario.php">

<input type="text" name="nombre" placeholder="Nombre" required><br><br>

<input type="text" name="rut" placeholder="RUT" required><br><br>

<input type="password" name="password" placeholder="Contraseña" required><br><br>

<select name="rol_id">

<?php while($r = $roles->fetch_assoc()){ ?>

<option value="<?php echo $r['id']; ?>">
<?php echo $r['nombre']; ?>
</option>

<?php } ?>

</select>

<br><br>

<button type="submit">Guardar</button>

</form>

</div>

</body>
</html>