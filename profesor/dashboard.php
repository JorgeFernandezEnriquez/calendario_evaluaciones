<?php
date_default_timezone_set('America/Santiago');

require "../includes/auth.php";
require "../bd/conexion.php";
require "../includes/registro_evaluaciones.php";

if ($_SESSION['rol_id'] != 2) {
    header("Location: ../index.php");
    exit;
}

$mensaje = "";
$error_crear = "";
$error_editar = "";
$abrir_modal = "";
$profesor_id = $_SESSION['usuario'];
$curso_filtro = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;
$hoy = date('Y-m-d');

/* CREAR EVALUACION */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_evaluacion'])) {

    $curso_id = (int)$_POST['curso_id'];
    $curso_asignatura_id = (int)$_POST['curso_asignatura_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $duracion_minutos = (int)$_POST['duracion_minutos'];
    $tipo = $_POST['tipo'];
    $descripcion = trim($_POST['descripcion']);
    $creado_por = $_SESSION['usuario'];

    if ($duracion_minutos <= 0) {
        $error_crear = "La duración debe ser mayor a 0 minutos.";
        $abrir_modal = "crear";
    } elseif ($fecha < $hoy) {
        $error_crear = "No puedes seleccionar una fecha anterior a hoy.";
        $abrir_modal = "crear";
    } elseif ($hora_inicio < "08:30") {
        $error_crear = "La hora debe ser desde las 08:30.";
        $abrir_modal = "crear";
    } elseif ($duracion_minutos % 45 != 0) {
        $error_crear = "La duración debe ser múltiplo de 45 minutos.";
        $abrir_modal = "crear";
    } else {

        $hora_fin = date("H:i", strtotime($hora_inicio) + ($duracion_minutos * 60));

        if ($hora_fin > "18:00") {
            $error_crear = "La evaluación no puede terminar después de las 18:00.";
            $abrir_modal = "crear";
        } else {

            $stmtCheck = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM curso_asignatura_profesor cap
                INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
                WHERE cap.usuario_id = ?
                AND cap.curso_asignatura_id = ?
                AND ca.curso_id = ?
            ");
            $stmtCheck->bind_param("iii", $profesor_id, $curso_asignatura_id, $curso_id);
            $stmtCheck->execute();
            $check = $stmtCheck->get_result()->fetch_assoc();

            if ($check['total'] == 0) {
                $error_crear = "No tienes permisos para crear una evaluación en esa asignatura.";
                $abrir_modal = "crear";
            } else {

                $stmtValidar = $conn->prepare("
                    SELECT COUNT(*) AS total
                    FROM evaluaciones e
                    INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
                    WHERE ca.curso_id = ?
                    AND e.fecha = ?
                ");
                $stmtValidar->bind_param("is", $curso_id, $fecha);
                $stmtValidar->execute();
                $resValidar = $stmtValidar->get_result()->fetch_assoc();

                if ($resValidar['total'] >= 2) {
                    $error_crear = "No se puede crear la evaluación. Ese curso ya tiene 2 evaluaciones en esa fecha.";
                    $abrir_modal = "crear";
                } else {

                    $stmtConflicto = $conn->prepare("
                        SELECT 
                            a.nombre AS asignatura,
                            e.hora_inicio,
                            e.duracion_minutos,
                            ADDTIME(e.hora_inicio, SEC_TO_TIME(e.duracion_minutos * 60)) AS hora_fin
                        FROM evaluaciones e
                        INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
                        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
                        WHERE ca.curso_id = ?
                        AND e.fecha = ?
                        AND e.hora_inicio < ADDTIME(?, SEC_TO_TIME(? * 60))
                        AND ADDTIME(e.hora_inicio, SEC_TO_TIME(e.duracion_minutos * 60)) > ?
                        LIMIT 1
                    ");
                    $stmtConflicto->bind_param("issis", $curso_id, $fecha, $hora_inicio, $duracion_minutos, $hora_inicio);
                    $stmtConflicto->execute();
                    $resConflicto = $stmtConflicto->get_result();

                    if ($resConflicto->num_rows > 0) {
                        $conflicto = $resConflicto->fetch_assoc();
                        $hora_fin_conflicto = substr($conflicto['hora_fin'], 0, 5);
                        $error_crear = "No se puede crear la evaluación porque topa con otra ya agendada ({$conflicto['asignatura']} de {$conflicto['hora_inicio']} a {$hora_fin_conflicto}).";
                        $abrir_modal = "crear";
                    } else {

                        $stmtInsert = $conn->prepare("
                            INSERT INTO evaluaciones (
                                curso_asignatura_id,
                                fecha,
                                hora_inicio,
                                duracion_minutos,
                                tipo,
                                descripcion,
                                creado_por
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtInsert->bind_param(
                            "ississi",
                            $curso_asignatura_id,
                            $fecha,
                            $hora_inicio,
                            $duracion_minutos,
                            $tipo,
                            $descripcion,
                            $creado_por
                        );

                        if ($stmtInsert->execute()) {
                            $nuevaEvaluacionId = $stmtInsert->insert_id;

                            $actual = obtenerDatosEvaluacionParaRegistro(
                                $conn,
                                $curso_asignatura_id,
                                $fecha,
                                $hora_inicio,
                                $duracion_minutos,
                                $tipo,
                                $descripcion
                            );

                            if ($actual) {
                                registrarAccionEvaluacion(
                                    $conn,
                                    'crear',
                                    $nuevaEvaluacionId,
                                    $actual,
                                    null,
                                    'Creó una evaluación'
                                );
                            }

                            $mensaje = "Evaluación creada correctamente.";
                            $curso_filtro = $curso_id;
                        } else {
                            $error_crear = "Ocurrió un error al crear la evaluación.";
                            $abrir_modal = "crear";
                        }
                    }
                }
            }
        }
    }
}

/* EDITAR EVALUACION */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_evaluacion'])) {

    $evaluacion_id = (int)$_POST['evaluacion_id'];
    $curso_id = (int)$_POST['curso_id'];
    $curso_asignatura_id = (int)$_POST['curso_asignatura_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $duracion_minutos = (int)$_POST['duracion_minutos'];
    $tipo = $_POST['tipo'];
    $descripcion = trim($_POST['descripcion']);

    if ($duracion_minutos <= 0) {
        $error_editar = "La duración debe ser mayor a 0 minutos.";
        $abrir_modal = "editar";
    } elseif ($fecha < $hoy) {
        $error_editar = "No puedes seleccionar una fecha anterior a hoy.";
        $abrir_modal = "editar";
    } elseif ($hora_inicio < "08:30") {
        $error_editar = "La hora debe ser desde las 08:30.";
        $abrir_modal = "editar";
    } elseif ($duracion_minutos % 45 != 0) {
        $error_editar = "La duración debe ser múltiplo de 45 minutos.";
        $abrir_modal = "editar";
    } else {

        $hora_fin = date("H:i", strtotime($hora_inicio) + ($duracion_minutos * 60));

        if ($hora_fin > "18:00") {
            $error_editar = "La evaluación no puede terminar después de las 18:00.";
            $abrir_modal = "editar";
        } else {

            $stmtPermiso = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM evaluaciones e
                INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
                INNER JOIN curso_asignatura_profesor cap ON cap.curso_asignatura_id = ca.id
                WHERE e.id = ?
                AND cap.usuario_id = ?
                AND e.creado_por = ?
            ");
            $stmtPermiso->bind_param("iii", $evaluacion_id, $profesor_id, $profesor_id);
            $stmtPermiso->execute();
            $permiso = $stmtPermiso->get_result()->fetch_assoc();

            if ($permiso['total'] == 0) {
                $error_editar = "No tienes permisos para editar esta evaluación.";
                $abrir_modal = "editar";
            } else {

                $stmtCheck = $conn->prepare("
                    SELECT COUNT(*) AS total
                    FROM curso_asignatura_profesor cap
                    INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
                    WHERE cap.usuario_id = ?
                    AND cap.curso_asignatura_id = ?
                    AND ca.curso_id = ?
                ");
                $stmtCheck->bind_param("iii", $profesor_id, $curso_asignatura_id, $curso_id);
                $stmtCheck->execute();
                $check = $stmtCheck->get_result()->fetch_assoc();

                if ($check['total'] == 0) {
                    $error_editar = "No puedes asignar esta evaluación a una asignatura que no impartes.";
                    $abrir_modal = "editar";
                } else {

                    $stmtValidar = $conn->prepare("
                        SELECT COUNT(*) AS total
                        FROM evaluaciones e
                        INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
                        WHERE ca.curso_id = ?
                        AND e.fecha = ?
                        AND e.id <> ?
                    ");
                    $stmtValidar->bind_param("isi", $curso_id, $fecha, $evaluacion_id);
                    $stmtValidar->execute();
                    $resValidar = $stmtValidar->get_result()->fetch_assoc();

                    if ($resValidar['total'] >= 2) {
                        $error_editar = "No se puede editar la evaluación. Ese curso ya tiene 2 evaluaciones en esa fecha.";
                        $abrir_modal = "editar";
                    } else {

                        $stmtConflicto = $conn->prepare("
                            SELECT 
                                a.nombre AS asignatura,
                                e.hora_inicio,
                                e.duracion_minutos,
                                ADDTIME(e.hora_inicio, SEC_TO_TIME(e.duracion_minutos * 60)) AS hora_fin
                            FROM evaluaciones e
                            INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
                            INNER JOIN asignaturas a ON a.id = ca.asignatura_id
                            WHERE ca.curso_id = ?
                            AND e.fecha = ?
                            AND e.id <> ?
                            AND e.hora_inicio < ADDTIME(?, SEC_TO_TIME(? * 60))
                            AND ADDTIME(e.hora_inicio, SEC_TO_TIME(e.duracion_minutos * 60)) > ?
                            LIMIT 1
                        ");
                        $stmtConflicto->bind_param("isisis", $curso_id, $fecha, $evaluacion_id, $hora_inicio, $duracion_minutos, $hora_inicio);
                        $stmtConflicto->execute();
                        $resConflicto = $stmtConflicto->get_result();

                        if ($resConflicto->num_rows > 0) {
                            $conflicto = $resConflicto->fetch_assoc();
                            $hora_fin_conflicto = substr($conflicto['hora_fin'], 0, 5);
                            $error_editar = "No se puede editar la evaluación porque topa con otra ya agendada ({$conflicto['asignatura']} de {$conflicto['hora_inicio']} a {$hora_fin_conflicto}).";
                            $abrir_modal = "editar";
                        } else {

                            $anterior = obtenerEvaluacionCompletaParaRegistro($conn, $evaluacion_id);

                            $stmtUpdate = $conn->prepare("
                                UPDATE evaluaciones
                                SET curso_asignatura_id = ?, fecha = ?, hora_inicio = ?, duracion_minutos = ?, tipo = ?, descripcion = ?
                                WHERE id = ? AND creado_por = ?
                            ");
                            $stmtUpdate->bind_param(
                                "ississii",
                                $curso_asignatura_id,
                                $fecha,
                                $hora_inicio,
                                $duracion_minutos,
                                $tipo,
                                $descripcion,
                                $evaluacion_id,
                                $profesor_id
                            );

                            if ($stmtUpdate->execute()) {
                                $actual = obtenerDatosEvaluacionParaRegistro(
                                    $conn,
                                    $curso_asignatura_id,
                                    $fecha,
                                    $hora_inicio,
                                    $duracion_minutos,
                                    $tipo,
                                    $descripcion
                                );

                                if ($actual) {
                                    registrarAccionEvaluacion(
                                        $conn,
                                        'editar',
                                        $evaluacion_id,
                                        $actual,
                                        $anterior,
                                        'Editó una evaluación'
                                    );
                                }

                                $mensaje = "Evaluación actualizada correctamente.";
                                $curso_filtro = $curso_id;
                            } else {
                                $error_editar = "No se pudo actualizar la evaluación.";
                                $abrir_modal = "editar";
                            }
                        }
                    }
                }
            }
        }
    }
}

/* ELIMINAR EVALUACION */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_evaluacion'])) {

    $evaluacion_id = (int)$_POST['evaluacion_id'];
    $curso_filtro = (int)$_POST['curso_id'];

    $stmtPermiso = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM evaluaciones e
        INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
        INNER JOIN curso_asignatura_profesor cap ON cap.curso_asignatura_id = ca.id
        WHERE e.id = ?
        AND cap.usuario_id = ?
        AND e.creado_por = ?
    ");
    $stmtPermiso->bind_param("iii", $evaluacion_id, $profesor_id, $profesor_id);
    $stmtPermiso->execute();
    $permiso = $stmtPermiso->get_result()->fetch_assoc();

    if ($permiso['total'] == 0) {
        $error_editar = "No tienes permisos para eliminar esta evaluación.";
        $abrir_modal = "editar";
    } else {
        $anterior = obtenerEvaluacionCompletaParaRegistro($conn, $evaluacion_id);

        $stmtDelete = $conn->prepare("DELETE FROM evaluaciones WHERE id = ? AND creado_por = ?");
        $stmtDelete->bind_param("ii", $evaluacion_id, $profesor_id);

        if ($stmtDelete->execute()) {
            if ($anterior) {
                registrarAccionEvaluacion(
                    $conn,
                    'eliminar',
                    $evaluacion_id,
                    $anterior,
                    $anterior,
                    'Eliminó una evaluación'
                );
            }

            $mensaje = "Evaluación eliminada correctamente.";
        } else {
            $error_editar = "No se pudo eliminar la evaluación.";
            $abrir_modal = "editar";
        }
    }
}

/* CURSOS DEL PROFESOR */
$cursos = [];
$stmtCursos = $conn->prepare("
    SELECT DISTINCT c.id, c.nombre
    FROM curso_asignatura_profesor cap
    INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
    INNER JOIN cursos c ON c.id = ca.curso_id
    WHERE cap.usuario_id = ?
    ORDER BY c.nombre
");
$stmtCursos->bind_param("i", $profesor_id);
$stmtCursos->execute();
$resCursos = $stmtCursos->get_result();

while ($row = $resCursos->fetch_assoc()) {
    $cursos[] = $row;
}

require "../includes/header.php";
?>

<div class="sidebar">
    <h2><?php echo $_SESSION['nombre']; ?></h2>
    <ul>
        <li><a href="dashboard.php">🏠 Inicio</a></li>
        <li><a href="../login/logout.php">🚪 Cerrar sesión</a></li>
    </ul>
</div>

<div class="main">

    <div class="page-header">
        <h2>Mis evaluaciones</h2>

        <div class="page-header-actions">
            <form method="GET" class="form-inline">
                <label for="curso_filtro"><strong>Curso:</strong></label>
                <select name="curso" id="curso_filtro" onchange="this.form.submit()">
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso) { ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo ($curso_filtro == $curso['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($curso['nombre']); ?>
                        </option>
                    <?php } ?>
                </select>
            </form>

            <button type="button" class="btn btn-primary" onclick="abrirModalCrear()">
                + Crear evaluación
            </button>
        </div>
    </div>

    <?php if ($mensaje != "") { ?>
        <div class="alert-success"><?php echo $mensaje; ?></div>
    <?php } ?>

    <div class="section">
        <?php if ($curso_filtro > 0) { ?>
            <div id="calendar"></div>
        <?php } else { ?>
            <p>Selecciona uno de tus cursos para ver el calendario.</p>
        <?php } ?>
    </div>
</div>

<!-- MODAL CREAR -->
<div id="modalCrear" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Crear evaluación</h3>
            <button type="button" class="modal-close" onclick="cerrarModalCrear()">×</button>
        </div>

        <form method="POST" class="eval-form">
            <?php if ($error_crear != "") { ?>
                <div class="alert-error" style="margin-bottom: 15px;">
                    <?php echo $error_crear; ?>
                </div>
            <?php } ?>

            <div class="form-row">
                <label>Curso</label>
                <select name="curso_id" id="curso_id_crear" required>
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso) { ?>
                        <option value="<?php echo $curso['id']; ?>"
                            <?php
                            if (isset($_POST['crear_evaluacion']) && isset($_POST['curso_id']) && (int)$_POST['curso_id'] === (int)$curso['id']) {
                                echo 'selected';
                            } elseif ($curso_filtro == $curso['id']) {
                                echo 'selected';
                            }
                            ?>>
                            <?php echo htmlspecialchars($curso['nombre']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-row">
                <label>Asignatura</label>
                <select name="curso_asignatura_id" id="curso_asignatura_id_crear" required>
                    <option value="">Seleccionar asignatura</option>
                </select>
            </div>

            <div class="form-row">
                <label>Fecha</label>
                <input
                    type="date"
                    name="fecha"
                    id="fecha_crear"
                    required
                    min="<?php echo $hoy; ?>"
                    value="<?php echo isset($_POST['fecha']) && isset($_POST['crear_evaluacion']) ? htmlspecialchars($_POST['fecha']) : $hoy; ?>"
                >
            </div>

            <div class="form-row two-cols">
                <div>
                    <label>Hora inicio</label>
                    <input
                        type="time"
                        name="hora_inicio"
                        id="hora_inicio_crear"
                        required
                        min="08:30"
                        value="<?php echo isset($_POST['hora_inicio']) && isset($_POST['crear_evaluacion']) ? htmlspecialchars($_POST['hora_inicio']) : '08:30'; ?>"
                    >
                </div>
                <div>
                    <label>Duración (minutos)</label>
                    <input
                        type="number"
                        name="duracion_minutos"
                        id="duracion_crear"
                        min="45"
                        step="45"
                        required
                        value="<?php echo isset($_POST['duracion_minutos']) && isset($_POST['crear_evaluacion']) ? (int)$_POST['duracion_minutos'] : 90; ?>"
                    >
                </div>
            </div>

            <div class="form-row">
                <label>Tipo</label>
                <select name="tipo" required>
                    <option value="prueba" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'prueba' && isset($_POST['crear_evaluacion'])) ? 'selected' : ''; ?>>Prueba</option>
                    <option value="control" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'control' && isset($_POST['crear_evaluacion'])) ? 'selected' : ''; ?>>Control</option>
                    <option value="trabajo" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'trabajo' && isset($_POST['crear_evaluacion'])) ? 'selected' : ''; ?>>Trabajo</option>
                    <option value="disertacion" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'disertacion' && isset($_POST['crear_evaluacion'])) ? 'selected' : ''; ?>>Disertación</option>
                </select>
            </div>

            <div class="form-row">
                <label>Descripción</label>
                <textarea name="descripcion" rows="4" placeholder="Detalle de la evaluación"><?php echo isset($_POST['descripcion']) && isset($_POST['crear_evaluacion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCrear()">Cancelar</button>
                <button type="submit" name="crear_evaluacion" class="btn btn-primary">Guardar evaluación</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Editar evaluación</h3>
            <button type="button" class="modal-close" onclick="cerrarModalEditar()">×</button>
        </div>

        <form method="POST" class="eval-form">
            <?php if ($error_editar != "") { ?>
                <div class="alert-error" style="margin-bottom: 15px;">
                    <?php echo $error_editar; ?>
                </div>
            <?php } ?>

            <input type="hidden" name="evaluacion_id" id="editar_evaluacion_id" value="<?php echo isset($_POST['evaluacion_id']) ? (int)$_POST['evaluacion_id'] : ''; ?>">
            <input type="hidden" name="curso_id" id="editar_curso_id_hidden" value="<?php echo isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : ''; ?>">

            <div class="form-row">
                <label>Curso</label>
                <select id="curso_id_editar" disabled>
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso) { ?>
                        <option value="<?php echo $curso['id']; ?>">
                            <?php echo htmlspecialchars($curso['nombre']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-row">
                <label>Asignatura</label>
                <select name="curso_asignatura_id" id="curso_asignatura_id_editar" required>
                    <option value="">Seleccionar asignatura</option>
                </select>
            </div>

            <div class="form-row">
                <label>Fecha</label>
                <input
                    type="date"
                    name="fecha"
                    id="fecha_editar"
                    required
                    min="<?php echo $hoy; ?>"
                    value="<?php echo isset($_POST['fecha']) && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion'])) ? htmlspecialchars($_POST['fecha']) : ''; ?>"
                >
            </div>

            <div class="form-row two-cols">
                <div>
                    <label>Hora inicio</label>
                    <input
                        type="time"
                        name="hora_inicio"
                        id="hora_inicio_editar"
                        required
                        min="08:30"
                        value="<?php echo isset($_POST['hora_inicio']) && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion'])) ? htmlspecialchars($_POST['hora_inicio']) : ''; ?>"
                    >
                </div>
                <div>
                    <label>Duración (minutos)</label>
                    <input
                        type="number"
                        name="duracion_minutos"
                        id="duracion_editar"
                        min="45"
                        step="45"
                        required
                        value="<?php echo isset($_POST['duracion_minutos']) && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion'])) ? (int)$_POST['duracion_minutos'] : ''; ?>"
                    >
                </div>
            </div>

            <div class="form-row">
                <label>Tipo</label>
                <select name="tipo" id="tipo_editar" required>
                    <option value="prueba" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'prueba' && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion']))) ? 'selected' : ''; ?>>Prueba</option>
                    <option value="control" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'control' && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion']))) ? 'selected' : ''; ?>>Control</option>
                    <option value="trabajo" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'trabajo' && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion']))) ? 'selected' : ''; ?>>Trabajo</option>
                    <option value="disertacion" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'disertacion' && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion']))) ? 'selected' : ''; ?>>Disertación</option>
                </select>
            </div>

            <div class="form-row">
                <label>Descripción</label>
                <textarea name="descripcion" rows="4" id="descripcion_editar"><?php echo isset($_POST['descripcion']) && (isset($_POST['editar_evaluacion']) || isset($_POST['eliminar_evaluacion'])) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
            </div>

            <div class="modal-actions between">
                <button type="submit" name="eliminar_evaluacion" class="btn btn-delete" onclick="return confirm('¿Eliminar esta evaluación?')">
                    Eliminar
                </button>

                <div class="right-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
                    <button type="submit" name="editar_evaluacion" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VER -->
<div id="modalVer" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Detalle de evaluación</h3>
            <button type="button" class="modal-close" onclick="cerrarModalVer()">×</button>
        </div>

        <div class="eval-form">
            <div class="form-row">
                <label><strong>Asignatura</strong></label>
                <p id="ver_asignatura"></p>
            </div>

            <div class="form-row">
                <label><strong>Profesor</strong></label>
                <p id="ver_profesor"></p>
            </div>

            <div class="form-row two-cols">
                <div>
                    <label><strong>Fecha</strong></label>
                    <p id="ver_fecha"></p>
                </div>
                <div>
                    <label><strong>Hora</strong></label>
                    <p id="ver_hora"></p>
                </div>
            </div>

            <div class="form-row">
                <label><strong>Tipo</strong></label>
                <p id="ver_tipo"></p>
            </div>

            <div class="form-row">
                <label><strong>Descripción</strong></label>
                <p id="ver_descripcion"></p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalVer()">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
function abrirModalCrear() {
    document.getElementById("modalCrear").classList.add("show");
}

function cerrarModalCrear() {
    document.getElementById("modalCrear").classList.remove("show");
}

function abrirModalEditar() {
    document.getElementById("modalEditar").classList.add("show");
}

function cerrarModalEditar() {
    document.getElementById("modalEditar").classList.remove("show");
}

function abrirModalVer() {
    document.getElementById("modalVer").classList.add("show");
}

function cerrarModalVer() {
    document.getElementById("modalVer").classList.remove("show");
}

function formatearTipo(tipo) {
    switch (tipo) {
        case 'prueba': return 'Prueba';
        case 'control': return 'Control';
        case 'trabajo': return 'Trabajo';
        case 'disertacion': return 'Disertación';
        default: return tipo;
    }
}

function cargarAsignaturasProfesor(cursoId, selectId, selectedValue = "") {
    const selectAsignatura = document.getElementById(selectId);
    selectAsignatura.innerHTML = '<option value="">Cargando...</option>';

    if (!cursoId) {
        selectAsignatura.innerHTML = '<option value="">Seleccionar asignatura</option>';
        return;
    }

    fetch("obtener_asignaturas_profesor.php?curso_id=" + cursoId)
        .then(response => response.json())
        .then(data => {
            selectAsignatura.innerHTML = '<option value="">Seleccionar asignatura</option>';

            data.forEach(item => {
                const option = document.createElement("option");
                option.value = item.id;
                option.textContent = item.nombre;

                if (selectedValue && selectedValue == item.id) {
                    option.selected = true;
                }

                selectAsignatura.appendChild(option);
            });
        });
}

document.addEventListener("DOMContentLoaded", function () {
    const cursoCrear = document.getElementById("curso_id_crear");

    if (cursoCrear) {
        cursoCrear.addEventListener("change", function () {
            cargarAsignaturasProfesor(this.value, "curso_asignatura_id_crear");
        });

        if (cursoCrear.value) {
            cargarAsignaturasProfesor(
                cursoCrear.value,
                "curso_asignatura_id_crear",
                "<?php echo isset($_POST['curso_asignatura_id']) && isset($_POST['crear_evaluacion']) ? (int)$_POST['curso_asignatura_id'] : ''; ?>"
            );
        }
    }

    <?php if ($abrir_modal == "crear") { ?>
        abrirModalCrear();
    <?php } ?>

    <?php if ($abrir_modal == "editar") { ?>
        abrirModalEditar();

        <?php if (isset($_POST['curso_id'])) { ?>
            document.getElementById('curso_id_editar').value = '<?php echo (int)$_POST['curso_id']; ?>';
            cargarAsignaturasProfesor(
                '<?php echo (int)$_POST['curso_id']; ?>',
                'curso_asignatura_id_editar',
                '<?php echo isset($_POST['curso_asignatura_id']) ? (int)$_POST['curso_asignatura_id'] : ''; ?>'
            );
        <?php } ?>
    <?php } ?>
});
</script>

<?php if ($curso_filtro > 0) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    function colorPorEvento(esPropia, tipo) {
        if (esPropia) {
            switch (tipo) {
                case 'prueba': return '#dc2626';
                case 'control': return '#2563eb';
                case 'trabajo': return '#16a34a';
                case 'disertacion': return '#ca8a04';
                default: return '#475569';
            }
        } else {
            switch (tipo) {
                case 'prueba': return '#f87171';
                case 'control': return '#60a5fa';
                case 'trabajo': return '#4ade80';
                case 'disertacion': return '#facc15';
                default: return '#94a3b8';
            }
        }
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        firstDay: 1,
        weekends: false,
        hiddenDays: [0, 6],
        allDaySlot: false,
        height: 780,
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        nowIndicator: true,
        expandRows: true,
        selectable: true,
        selectConstraint: {
            startTime: '08:30',
            endTime: '18:00',
            daysOfWeek: [1, 2, 3, 4, 5]
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,dayGridMonth'
        },
        buttonText: {
            today: 'Hoy',
            week: 'Semana',
            month: 'Mes'
        },
        noEventsContent: 'No hay evaluaciones',
        events: {
            url: 'eventos_profesor.php',
            extraParams: {
                curso_id: '<?php echo $curso_filtro; ?>'
            }
        },

        select: function(info) {
            if (info.view.type !== 'timeGridWeek') {
                return;
            }

            abrirModalCrear();

            document.getElementById('curso_id_crear').value = '<?php echo $curso_filtro; ?>';
            cargarAsignaturasProfesor('<?php echo $curso_filtro; ?>', 'curso_asignatura_id_crear');

            document.getElementById('fecha_crear').value = info.startStr.substring(0, 10);
            document.getElementById('hora_inicio_crear').value = info.startStr.substring(11, 16);

            const inicio = info.startStr.substring(11, 16);
            const fin = info.endStr.substring(11, 16);

            const iniPartes = inicio.split(':');
            const finPartes = fin.split(':');

            const minutosInicio = (parseInt(iniPartes[0], 10) * 60) + parseInt(iniPartes[1], 10);
            const minutosFin = (parseInt(finPartes[0], 10) * 60) + parseInt(finPartes[1], 10);
            const duracion = minutosFin - minutosInicio;

            document.getElementById('duracion_crear').value = duracion > 0 ? duracion : 90;
        },

        eventDidMount: function(info) {
            const tipo = info.event.extendedProps.tipo || '';
            const esPropia = info.event.extendedProps.es_propia || false;
            const profesorNombre = info.event.extendedProps.profesor_nombre || 'Sin información';
            const color = colorPorEvento(esPropia, tipo);

            info.el.style.backgroundColor = color;
            info.el.style.borderColor = color;
            info.el.title = info.event.title + " - " + profesorNombre;

            if (!esPropia) {
                info.el.style.opacity = "0.6";
                info.el.style.filter = "grayscale(20%)";
            }
        },

        eventClick: function(info) {
            const props = info.event.extendedProps;

            if (!props.es_propia) {
                document.getElementById('ver_asignatura').textContent = info.event.title;
                document.getElementById('ver_profesor').textContent = props.profesor_nombre || 'Sin información';
                document.getElementById('ver_fecha').textContent = props.fecha;
                document.getElementById('ver_hora').textContent = props.hora_inicio;
                document.getElementById('ver_tipo').textContent = formatearTipo(props.tipo);
                document.getElementById('ver_descripcion').textContent = props.descripcion || 'Sin descripción';

                abrirModalVer();
                return;
            }

            document.getElementById('editar_evaluacion_id').value = info.event.id;
            document.getElementById('editar_curso_id_hidden').value = props.curso_id;
            document.getElementById('curso_id_editar').value = props.curso_id;
            document.getElementById('fecha_editar').value = props.fecha;
            document.getElementById('hora_inicio_editar').value = props.hora_inicio;
            document.getElementById('duracion_editar').value = props.duracion_minutos;
            document.getElementById('tipo_editar').value = props.tipo;
            document.getElementById('descripcion_editar').value = props.descripcion || '';

            cargarAsignaturasProfesor(props.curso_id, 'curso_asignatura_id_editar', props.curso_asignatura_id);
            abrirModalEditar();
        }
    });

    calendar.render();
});
</script>
<?php } ?>

</body>
</html>