<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";


/* CREAR ASIGNATURA */

if(isset($_POST['crear'])){

    $nombre = $_POST['nombre'];

    $stmt = $conn->prepare("INSERT INTO asignaturas (nombre) VALUES (?)");
    $stmt->bind_param("s",$nombre);
    $stmt->execute();
}


/* EDITAR ASIGNATURA */

if(isset($_POST['editar'])){

    $id = $_POST['id'];
    $nombre = $_POST['nombre'];

    $stmt = $conn->prepare("UPDATE asignaturas SET nombre=? WHERE id=?");
    $stmt->bind_param("si",$nombre,$id);
    $stmt->execute();
}


/* ELIMINAR */

if(isset($_GET['eliminar'])){

    $id = $_GET['eliminar'];

    $stmt = $conn->prepare("DELETE FROM asignaturas WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}


/* LISTAR ASIGNATURAS */

$asignaturas = $conn->query("SELECT * FROM asignaturas");

require "../includes/header.php";
require "../includes/sidebar_admin.php";
?>

<div class="main">

<?php require "../includes/topbar.php"; ?>

<h2>Asignaturas</h2>


<div class="section">

<h3>Crear asignatura</h3>

<form method="POST" class="form-inline">

<input 
type="text" 
name="nombre" 
placeholder="Nombre de la asignatura"
required>

<button name="crear" class="btn btn-primary">
Crear
</button>

</form>

</div>


<div class="section">

<h3>Lista de asignaturas</h3>

<table class="table">

<tr>
<th>ID</th>
<th>Asignatura</th>
<th>Guardar</th>
<th>Eliminar</th>
</tr>

<?php while($a=$asignaturas->fetch_assoc()){ ?>

<tr>

<form method="POST">

<td><?php echo $a['id']; ?></td>

<td>
<input 
type="text" 
name="nombre"
value="<?php echo $a['nombre']; ?>"
style="width:100%;">
</td>

<td>

<input type="hidden" name="id" value="<?php echo $a['id']; ?>">

<button name="editar" class="btn btn-primary">
Guardar
</button>

</td>

<td>

<a 
href="?eliminar=<?php echo $a['id']; ?>"
class="btn btn-delete"
onclick="return confirm('Eliminar asignatura?')">

Eliminar

</a>

</td>

</form>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>