<?php
// 1 CONEXION Y LOGICA PHP
require "../includes/auth_admin.php";
require "../bd/conexion.php";

/* AGREGAR ASIGNATURA */

if(isset($_POST['agregar'])){
    $curso = $_POST['curso_id'];
    $asignatura = $_POST['asignatura_id'];

    $stmt = $conn->prepare("
        INSERT IGNORE INTO curso_asignatura (curso_id,asignatura_id)
        VALUES (?,?)
    ");

    $stmt->bind_param("ii",$curso,$asignatura);
    $stmt->execute();
}

/* ELIMINAR */

if(isset($_GET['eliminar'])){
    $id=$_GET['eliminar'];

    $stmt=$conn->prepare("DELETE FROM curso_asignatura WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}

/* CURSOS */

$cursos=$conn->query("SELECT * FROM cursos");

/* ASIGNATURAS */

$asignaturas=$conn->query("SELECT * FROM asignaturas");

/* CURSO SELECCIONADO */

$curso_id=$_GET['curso'] ?? null;

if($curso_id){

$lista=$conn->query("
SELECT ca.id,a.nombre
FROM curso_asignatura ca
JOIN asignaturas a ON ca.asignatura_id=a.id
WHERE ca.curso_id=$curso_id
");

}

require "../includes/header.php";
require "../includes/sidebar_admin.php";
?>

<div class="main">

<?php require "../includes/topbar.php"; ?>

<h2>Asignaturas por Curso</h2>

<!-- 2 SELECTOR DE CURSO -->

<form method="GET">

<select name="curso" onchange="this.form.submit()">

<option value="">Seleccionar curso</option>

<?php while($c=$cursos->fetch_assoc()){ ?>

<option value="<?php echo $c['id']; ?>"
<?php if($curso_id==$c['id']) echo "selected"; ?>>

<?php echo $c['nombre']; ?>

</option>

<?php } ?>

</select>

</form>


<!-- 3 AGREGAR ASIGNATURA -->

<?php if($curso_id){ ?>

<h3>Agregar asignatura</h3>

<form method="POST">

<input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">

<select name="asignatura_id">

<?php while($a=$asignaturas->fetch_assoc()){ ?>

<option value="<?php echo $a['id']; ?>">
<?php echo $a['nombre']; ?>
</option>

<?php } ?>

</select>

<button name="agregar" class="btn btn-primary">
Agregar
</button>

</form>

<?php } ?>


<!-- 4 LISTA DE ASIGNATURAS -->

<?php if($curso_id){ ?>

<table class="table">

<tr>
<th>Asignatura</th>
<th>Acción</th>
</tr>

<?php while($l=$lista->fetch_assoc()){ ?>

<tr>

<td><?php echo $l['nombre']; ?></td>

<td>

<a href="?curso=<?php echo $curso_id; ?>&eliminar=<?php echo $l['id']; ?>"
class="btn btn-delete">

Eliminar

</a>

</td>

</tr>

<?php } ?>

</table>

<?php } ?>

</div>

</body>
</html>