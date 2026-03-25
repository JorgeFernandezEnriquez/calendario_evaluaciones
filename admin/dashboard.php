<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";
require "../includes/header.php";

/* estadísticas */

$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];

$total_cursos = $conn->query("SELECT COUNT(*) as total FROM cursos")->fetch_assoc()['total'];

$total_asignaturas = $conn->query("SELECT COUNT(*) as total FROM asignaturas")->fetch_assoc()['total'];

$total_evaluaciones = $conn->query("SELECT COUNT(*) as total FROM evaluaciones")->fetch_assoc()['total'];
?>

<?php require "../includes/sidebar_admin.php"; ?>

<div class="main">

<?php require "../includes/topbar.php"; ?>

<h2>Dashboard</h2>

<div class="cards">

<div class="card">
<h3>Usuarios</h3>
<p><?php echo $total_usuarios; ?></p>
</div>

<div class="card">
<h3>Cursos</h3>
<p><?php echo $total_cursos; ?></p>
</div>

<div class="card">
<h3>Asignaturas</h3>
<p><?php echo $total_asignaturas; ?></p>
</div>

<div class="card">
<h3>Evaluaciones</h3>
<p><?php echo $total_evaluaciones; ?></p>
</div>

</div>

</div>

</body>
</html>