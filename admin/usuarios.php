<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

/* CREAR USUARIO */

if(isset($_POST['crear'])){

    $nombre = $_POST['nombre'];
    $rut = $_POST['rut'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol_id = $_POST['rol_id'];

    $stmt = $conn->prepare("INSERT INTO usuarios (nombre,rut,password,rol_id) VALUES (?,?,?,?)");
    $stmt->bind_param("sssi",$nombre,$rut,$password,$rol_id);
    $stmt->execute();
}


/* CAMBIAR CONTRASEÑA */

if(isset($_POST['cambiar_password'])){

    $id = $_POST['usuario_id'];
    $password = password_hash($_POST['nueva_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usuarios SET password=? WHERE id=?");
    $stmt->bind_param("si",$password,$id);
    $stmt->execute();
}


/* ELIMINAR USUARIO */

if(isset($_GET['eliminar'])){

    $id = $_GET['eliminar'];

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}


/* OBTENER USUARIOS */

$usuarios = $conn->query("
SELECT u.id,u.nombre,u.rut,r.nombre as rol
FROM usuarios u
JOIN roles r ON u.rol_id=r.id
order by u.nombre
");

$roles = $conn->query("SELECT * FROM roles");

require "../includes/header.php";
?>

<?php require "../includes/sidebar_admin.php"; ?>

<div class="main">

<?php require "../includes/topbar.php"; ?>

<h2>Usuarios</h2>


<div class="section">

<h3>Crear usuario</h3>

<form method="POST" class="form-inline">

<input type="text" name="nombre" placeholder="Nombre" required>

<input type="text" name="rut" placeholder="RUT" required>

<input type="password" name="password" placeholder="Contraseña" required>

<select name="rol_id">

<?php while($r=$roles->fetch_assoc()){ ?>

<option value="<?php echo $r['id']; ?>">
<?php echo $r['nombre']; ?>
</option>

<?php } ?>

</select>

<button type="submit" name="crear" class="btn btn-primary">
Crear
</button>

</form>

</div>


<div class="section">

<h3>Lista de usuarios</h3>

<table class="table">

<tr>
<th>ID</th>
<th>Nombre</th>
<th>RUT</th>
<th>Rol</th>
<th>Acciones</th>
</tr>

<?php while($u=$usuarios->fetch_assoc()){ ?>

<tr>

<td><?php echo $u['id']; ?></td>
<td><?php echo $u['nombre']; ?></td>
<td><?php echo $u['rut']; ?></td>
<td><?php echo $u['rol']; ?></td>

<td>

<button 
class="btn btn-warning"
onclick="mostrarCambio(<?php echo $u['id']; ?>)">
Cambiar contraseña
</button>

<a href="?eliminar=<?php echo $u['id']; ?>"
class="btn btn-delete"
onclick="return confirm('Eliminar usuario?')">
Eliminar
</a>

</td>

</tr>

<tr id="fila-pass-<?php echo $u['id']; ?>" class="fila-password">

<td colspan="5">

<form method="POST" class="form-inline">

<input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">

<input type="password" name="nueva_password" placeholder="Nueva contraseña" required>

<button type="submit" name="cambiar_password" class="btn btn-primary">
Guardar
</button>

</form>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>


<script>

function mostrarCambio(id){

let fila = document.getElementById("fila-pass-"+id);

if(fila.style.display === "table-row"){
    fila.style.display = "none";
}else{
    fila.style.display = "table-row";
}

}

</script>

</body>
</html>