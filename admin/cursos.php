<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";


/* CREAR CURSO */

if(isset($_POST['crear'])){

    $nombre = $_POST['nombre'];

    $stmt = $conn->prepare("INSERT INTO cursos (nombre) VALUES (?)");
    $stmt->bind_param("s",$nombre);
    $stmt->execute();
}


/* ELIMINAR CURSO */

if(isset($_GET['eliminar'])){

    $id = $_GET['eliminar'];

    $stmt = $conn->prepare("DELETE FROM cursos WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}


/* LISTAR CURSOS */

$cursos = $conn->query("SELECT * FROM cursos");

require "../includes/header.php";
?>

<?php require "../includes/sidebar_admin.php"; ?>

<div class="main">

<?php require "../includes/topbar.php"; ?>

<h2>Cursos</h2>


<div class="section">

<h3>Crear curso</h3>

<form method="POST" class="form-inline">

<input 
type="text" 
name="nombre" 
placeholder="Ej: 1A, 2B, 3C"
required>

<button type="submit" name="crear" class="btn btn-primary">
Crear curso
</button>

</form>

</div>


<div class="section">

<h3>Lista de cursos</h3>

<table class="table">

<tr>
<th>ID</th>
<th>Curso</th>
<th>Acciones</th>
</tr>

<?php while($c=$cursos->fetch_assoc()){ ?>

<tr>

<td><?php echo $c['id']; ?></td>

<td><?php echo $c['nombre']; ?></td>

<td>

<a 
href="?eliminar=<?php echo $c['id']; ?>"
class="btn btn-delete"
onclick="return confirm('Eliminar curso?')">

Eliminar

</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>