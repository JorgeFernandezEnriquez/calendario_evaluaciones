<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$filtro_fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';
$filtro_accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$filtro_rol = isset($_GET['rol']) ? trim($_GET['rol']) : '';
$filtro_usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$filtro_curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$filtro_curso_asignatura_id = isset($_GET['curso_asignatura_id']) ? (int)$_GET['curso_asignatura_id'] : 0;
$filtro_fecha_evaluacion = isset($_GET['fecha_evaluacion']) ? trim($_GET['fecha_evaluacion']) : '';

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

/* USUARIOS */
$usuarios = [];
$resUsuarios = $conn->query("
    SELECT id, nombre, rol_id
    FROM usuarios
    ORDER BY nombre
");
while ($row = $resUsuarios->fetch_assoc()) {
    $usuarios[] = $row;
}

/* CURSOS DEL USUARIO SELECCIONADO */
$cursos_usuario = [];
if ($filtro_usuario_id > 0) {
    $stmtCursos = $conn->prepare("
        SELECT DISTINCT c.id, c.nombre
        FROM curso_asignatura_profesor cap
        INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
        INNER JOIN cursos c ON c.id = ca.curso_id
        WHERE cap.usuario_id = ?
        ORDER BY c.nombre
    ");
    $stmtCursos->bind_param("i", $filtro_usuario_id);
    $stmtCursos->execute();
    $resCursos = $stmtCursos->get_result();

    while ($row = $resCursos->fetch_assoc()) {
        $cursos_usuario[] = $row;
    }
}

/* ASIGNATURAS DEL USUARIO EN ESE CURSO */
$asignaturas_usuario = [];
if ($filtro_usuario_id > 0 && $filtro_curso_id > 0) {
    $stmtAsignaturas = $conn->prepare("
        SELECT 
            ca.id AS curso_asignatura_id,
            a.nombre AS asignatura_nombre
        FROM curso_asignatura_profesor cap
        INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
        WHERE cap.usuario_id = ?
        AND ca.curso_id = ?
        ORDER BY a.nombre
    ");
    $stmtAsignaturas->bind_param("ii", $filtro_usuario_id, $filtro_curso_id);
    $stmtAsignaturas->execute();
    $resAsignaturas = $stmtAsignaturas->get_result();

    while ($row = $resAsignaturas->fetch_assoc()) {
        $asignaturas_usuario[] = $row;
    }
}

/* FILTROS SQL */
$where = [];
$params = [];
$types = '';

if ($filtro_fecha_desde !== '') {
    $where[] = "DATE(re.fecha_registro) >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= 's';
}

if ($filtro_fecha_hasta !== '') {
    $where[] = "DATE(re.fecha_registro) <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= 's';
}

if ($filtro_accion !== '') {
    $where[] = "re.accion = ?";
    $params[] = $filtro_accion;
    $types .= 's';
}

if ($filtro_rol !== '') {
    $where[] = "re.usuario_rol = ?";
    $params[] = $filtro_rol;
    $types .= 's';
}

if ($filtro_usuario_id > 0) {
    $where[] = "re.usuario_id = ?";
    $params[] = $filtro_usuario_id;
    $types .= 'i';
}

if ($filtro_curso_id > 0) {
    $where[] = "re.curso_id = ?";
    $params[] = $filtro_curso_id;
    $types .= 'i';
}

if ($filtro_curso_asignatura_id > 0) {
    $where[] = "re.curso_asignatura_id = ?";
    $params[] = $filtro_curso_asignatura_id;
    $types .= 'i';
}

if ($filtro_fecha_evaluacion !== '') {
    $where[] = "re.fecha_evaluacion = ?";
    $params[] = $filtro_fecha_evaluacion;
    $types .= 's';
}

$sql_where = '';
if (!empty($where)) {
    $sql_where = 'WHERE ' . implode(' AND ', $where);
}

/* TOTAL */
$sqlTotal = "SELECT COUNT(*) AS total FROM registros_evaluaciones re $sql_where";
$stmtTotal = $conn->prepare($sqlTotal);

if (!empty($params)) {
    $stmtTotal->bind_param($types, ...$params);
}

$stmtTotal->execute();
$totalResultado = $stmtTotal->get_result()->fetch_assoc();
$totalRegistros = (int)$totalResultado['total'];
$totalPaginas = max(1, (int)ceil($totalRegistros / $por_pagina));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $por_pagina;
}

/* REGISTROS */
$sqlRegistros = "
    SELECT re.*
    FROM registros_evaluaciones re
    $sql_where
    ORDER BY re.fecha_registro DESC, re.id DESC
    LIMIT ? OFFSET ?
";
$stmtRegistros = $conn->prepare($sqlRegistros);

$paramsRegistros = $params;
$typesRegistros = $types . 'ii';
$paramsRegistros[] = $por_pagina;
$paramsRegistros[] = $offset;

$stmtRegistros->bind_param($typesRegistros, ...$paramsRegistros);
$stmtRegistros->execute();
$resRegistros = $stmtRegistros->get_result();

$registros = [];
while ($row = $resRegistros->fetch_assoc()) {
    $registros[] = $row;
}

require "../includes/header.php";
?>

<?php require "../includes/sidebar_admin.php"; ?>

<div class="main">
    <?php require "../includes/topbar.php"; ?>

    <div class="page-header">
        <h2>Registro de evaluaciones</h2>
    </div>

    <div class="section">
        <form method="GET" id="formFiltrosLog">
            <div class="filtros-grid">

                <div class="form-row">
                    <label>Fecha registro desde</label>
                    <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                </div>

                <div class="form-row">
                    <label>Fecha registro hasta</label>
                    <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                </div>

                <div class="form-row">
                    <label>Acción</label>
                    <select name="accion">
                        <option value="">Todas</option>
                        <option value="crear" <?php echo ($filtro_accion === 'crear') ? 'selected' : ''; ?>>Crear</option>
                        <option value="editar" <?php echo ($filtro_accion === 'editar') ? 'selected' : ''; ?>>Editar</option>
                        <option value="eliminar" <?php echo ($filtro_accion === 'eliminar') ? 'selected' : ''; ?>>Eliminar</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>Rol</label>
                    <select name="rol">
                        <option value="">Todos</option>
                        <option value="admin" <?php echo ($filtro_rol === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="utp" <?php echo ($filtro_rol === 'utp') ? 'selected' : ''; ?>>UTP</option>
                        <option value="profesor" <?php echo ($filtro_rol === 'profesor') ? 'selected' : ''; ?>>Profesor</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>Usuario</label>
                    <select name="usuario_id" id="filtro_usuario">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario) { ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo ($filtro_usuario_id === (int)$usuario['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-row">
                    <label>Curso del usuario</label>
                    <select name="curso_id" id="filtro_curso" <?php echo ($filtro_usuario_id <= 0) ? 'disabled' : ''; ?>>
                        <option value="">Todos</option>
                        <?php foreach ($cursos_usuario as $curso) { ?>
                            <option value="<?php echo $curso['id']; ?>" <?php echo ($filtro_curso_id === (int)$curso['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curso['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-row">
                    <label>Asignatura del usuario en ese curso</label>
                    <select name="curso_asignatura_id" id="filtro_asignatura" <?php echo ($filtro_usuario_id <= 0 || $filtro_curso_id <= 0) ? 'disabled' : ''; ?>>
                        <option value="">Todas</option>
                        <?php foreach ($asignaturas_usuario as $asignatura) { ?>
                            <option value="<?php echo $asignatura['curso_asignatura_id']; ?>" <?php echo ($filtro_curso_asignatura_id === (int)$asignatura['curso_asignatura_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($asignatura['asignatura_nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-row">
                    <label>Fecha evaluación</label>
                    <input type="date" name="fecha_evaluacion" value="<?php echo htmlspecialchars($filtro_fecha_evaluacion); ?>">
                </div>
            </div>

            <div class="filtros-acciones">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="log.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="section">
        <div class="tabla-header-log">
            <p><strong>Total registros:</strong> <?php echo $totalRegistros; ?></p>
        </div>

        <div class="tabla-responsive">
            <table class="tabla-log">
                <thead>
                    <tr>
                        <th>Fecha registro</th>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Curso</th>
                        <th>Asignatura</th>
                        <th>Fecha eval.</th>
                        <th>Hora</th>
                        <th>Duración</th>
                        <th>Tipo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($registros) > 0) { ?>
                        <?php foreach ($registros as $registro) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($registro['fecha_registro']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($registro['accion'])); ?></td>
                                <td><?php echo htmlspecialchars($registro['usuario_nombre']); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($registro['usuario_rol'])); ?></td>
                                <td><?php echo htmlspecialchars($registro['curso_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($registro['asignatura_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($registro['fecha_evaluacion']); ?></td>
                                <td><?php echo htmlspecialchars(substr((string)$registro['hora_inicio'], 0, 5)); ?></td>
                                <td><?php echo htmlspecialchars((string)$registro['duracion_minutos']); ?> min</td>
                                <td><?php echo htmlspecialchars($registro['tipo']); ?></td>
                                <td>
                                    <div class="detalle-log">
                                        <?php if (!empty($registro['detalle'])) { ?>
                                            <div><?php echo htmlspecialchars($registro['detalle']); ?></div>
                                        <?php } ?>

                                        <?php if ($registro['accion'] === 'editar') { ?>
                                            <div class="detalle-cambio">
                                                <strong>Antes:</strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    ($registro['curso_nombre_anterior'] ?? '') . ' | ' .
                                                    ($registro['asignatura_nombre_anterior'] ?? '') . ' | ' .
                                                    ($registro['fecha_evaluacion_anterior'] ?? '') . ' | ' .
                                                    substr((string)($registro['hora_inicio_anterior'] ?? ''), 0, 5) . ' | ' .
                                                    ($registro['duracion_minutos_anterior'] ?? '') . ' min | ' .
                                                    ($registro['tipo_anterior'] ?? '')
                                                );
                                                ?>
                                            </div>
                                            <div class="detalle-cambio">
                                                <strong>Después:</strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    ($registro['curso_nombre'] ?? '') . ' | ' .
                                                    ($registro['asignatura_nombre'] ?? '') . ' | ' .
                                                    ($registro['fecha_evaluacion'] ?? '') . ' | ' .
                                                    substr((string)($registro['hora_inicio'] ?? ''), 0, 5) . ' | ' .
                                                    ($registro['duracion_minutos'] ?? '') . ' min | ' .
                                                    ($registro['tipo'] ?? '')
                                                );
                                                ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="11">No se encontraron registros.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPaginas > 1) { ?>
            <div class="paginacion-log">
                <?php $queryBase = $_GET; ?>

                <?php if ($pagina > 1) { ?>
                    <?php $queryBase['pagina'] = $pagina - 1; ?>
                    <a class="btn btn-secondary" href="log.php?<?php echo http_build_query($queryBase); ?>">Anterior</a>
                <?php } ?>

                <span>Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></span>

                <?php if ($pagina < $totalPaginas) { ?>
                    <?php $queryBase['pagina'] = $pagina + 1; ?>
                    <a class="btn btn-secondary" href="log.php?<?php echo http_build_query($queryBase); ?>">Siguiente</a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.filtros-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:16px;
}

.filtros-acciones{
    margin-top:16px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.tabla-header-log{
    margin-bottom:12px;
}

.tabla-responsive{
    width:100%;
    overflow-x:auto;
}

.tabla-log{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}

.tabla-log th,
.tabla-log td{
    border:1px solid #e5e7eb;
    padding:10px;
    text-align:left;
    vertical-align:top;
    font-size:14px;
}

.tabla-log th{
    background:#f8fafc;
}

.detalle-log{
    min-width:260px;
}

.detalle-cambio{
    margin-top:6px;
    font-size:13px;
    color:#334155;
}

.paginacion-log{
    margin-top:18px;
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const filtroUsuario = document.getElementById("filtro_usuario");
    const filtroCurso = document.getElementById("filtro_curso");
    const filtroAsignatura = document.getElementById("filtro_asignatura");
    const form = document.getElementById("formFiltrosLog");

    if (filtroUsuario) {
        filtroUsuario.addEventListener("change", function () {
            const url = new URL(window.location.href);
            url.searchParams.set("usuario_id", this.value);
            url.searchParams.delete("curso_id");
            url.searchParams.delete("curso_asignatura_id");
            url.searchParams.delete("pagina");
            window.location.href = url.toString();
        });
    }

    if (filtroCurso) {
        filtroCurso.addEventListener("change", function () {
            const url = new URL(window.location.href);
            url.searchParams.set("curso_id", this.value);
            url.searchParams.delete("curso_asignatura_id");
            url.searchParams.delete("pagina");
            window.location.href = url.toString();
        });
    }

    if (filtroAsignatura) {
        filtroAsignatura.addEventListener("change", function () {
            form.submit();
        });
    }
});
</script>