<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$curso_id = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;

/* CURSOS */
$cursos = [];
$resCursos = $conn->query("SELECT id, nombre FROM cursos ORDER BY id");
while ($row = $resCursos->fetch_assoc()) {
    $cursos[] = $row;
}

/* PROFESORES */
$profesores = [];
$resProfesores = $conn->query("
    SELECT id, nombre
    FROM usuarios
    ORDER BY nombre
");
while ($row = $resProfesores->fetch_assoc()) {
    $profesores[] = $row;
}

/* ASIGNATURAS DEL CURSO */
$asignaciones = [];

if ($curso_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            ca.id AS curso_asignatura_id,
            a.nombre AS asignatura,
            u.id AS profesor_id,
            u.nombre AS profesor
        FROM curso_asignatura ca
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
        LEFT JOIN curso_asignatura_profesor cap ON cap.curso_asignatura_id = ca.id
        LEFT JOIN usuarios u ON u.id = cap.usuario_id
        WHERE ca.curso_id = ?
        ORDER BY a.nombre
    ");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $asignaciones[] = $row;
    }
}

require "../includes/header.php";
?>

<?php require "../includes/sidebar_admin.php"; ?>

<div class="main">

    <?php require "../includes/topbar.php"; ?>

    <div class="page-header">
        <h2>Asignar profesores por curso</h2>

        <form method="GET" class="form-inline">
            <label for="curso"><strong>Curso:</strong></label>
            <select name="curso" id="curso" onchange="this.form.submit()">
                <option value="">Seleccionar curso</option>
                <?php foreach ($cursos as $curso) { ?>
                    <option value="<?php echo $curso['id']; ?>" <?php echo ($curso_id == $curso['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($curso['nombre']); ?>
                    </option>
                <?php } ?>
            </select>
        </form>
    </div>

    <div id="mensaje-asignacion"></div>

    <div class="section">
        <?php if ($curso_id > 0) { ?>

            <?php if (!empty($asignaciones)) { ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Asignatura</th>
                            <th>Profesor asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones as $fila) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fila['asignatura']); ?></td>
                                <td>
                                    <select 
                                        class="select-profesor"
                                        data-curso-asignatura-id="<?php echo $fila['curso_asignatura_id']; ?>"
                                    >
                                        <option value="">Sin asignar</option>

                                        <?php foreach ($profesores as $profesor) { ?>
                                            <option 
                                                value="<?php echo $profesor['id']; ?>"
                                                <?php echo ($fila['profesor_id'] == $profesor['id']) ? 'selected' : ''; ?>
                                            >
                                                <?php echo htmlspecialchars($profesor['nombre']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p>Este curso no tiene asignaturas asignadas todavía.</p>
            <?php } ?>

        <?php } else { ?>
            <p>Selecciona un curso para asignar sus profesores.</p>
        <?php } ?>
    </div>

</div>

<script>
document.querySelectorAll(".select-profesor").forEach(function(select) {
    select.addEventListener("change", function() {
        const cursoAsignaturaId = this.dataset.cursoAsignaturaId;
        const usuarioId = this.value;
        const mensaje = document.getElementById("mensaje-asignacion");

        const formData = new FormData();
        formData.append("curso_asignatura_id", cursoAsignaturaId);
        formData.append("usuario_id", usuarioId);

        fetch("guardar_profesor_asignatura.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                mensaje.innerHTML = '<div class="alert-success">Asignación guardada correctamente.</div>';
            } else {
                mensaje.innerHTML = '<div class="alert-error">' + data.error + '</div>';
            }
        })
        .catch(() => {
            mensaje.innerHTML = '<div class="alert-error">Ocurrió un error al guardar.</div>';
        });
    });
});
</script>

</body>
</html>