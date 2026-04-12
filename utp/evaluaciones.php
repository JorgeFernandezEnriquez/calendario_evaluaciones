<?php
require "../includes/auth_utp.php";
require "../bd/conexion.php";
require "../includes/registro_evaluaciones.php";

$mensaje = "";
$error = "";
$curso_filtro = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;

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
        $error = "La duración debe ser mayor a 0 minutos.";
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
            $error = "No se puede crear la evaluación. Ese curso ya tiene 2 evaluaciones en esa fecha.";
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
                $error = "Ocurrió un error al crear la evaluación.";
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
        $error = "La duración debe ser mayor a 0 minutos.";
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
            $error = "No se puede editar la evaluación. Ese curso ya tiene 2 evaluaciones en esa fecha.";
        } else {
            $anterior = obtenerEvaluacionCompletaParaRegistro($conn, $evaluacion_id);

            $stmtUpdate = $conn->prepare("
                UPDATE evaluaciones
                SET curso_asignatura_id = ?, fecha = ?, hora_inicio = ?, duracion_minutos = ?, tipo = ?, descripcion = ?
                WHERE id = ?
            ");
            $stmtUpdate->bind_param(
                "ississi",
                $curso_asignatura_id,
                $fecha,
                $hora_inicio,
                $duracion_minutos,
                $tipo,
                $descripcion,
                $evaluacion_id
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
                $error = "No se pudo actualizar la evaluación.";
            }
        }
    }
}

/* ELIMINAR EVALUACION */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_evaluacion'])) {
    $evaluacion_id = (int)$_POST['evaluacion_id'];
    $curso_filtro = (int)$_POST['curso_id'];

    $anterior = obtenerEvaluacionCompletaParaRegistro($conn, $evaluacion_id);

    $stmtDelete = $conn->prepare("DELETE FROM evaluaciones WHERE id = ?");
    $stmtDelete->bind_param("i", $evaluacion_id);

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
        $error = "No se pudo eliminar la evaluación.";
    }
}

/* CURSOS */
$cursos = [];
$resCursos = $conn->query("SELECT id, nombre FROM cursos ORDER BY id");
while ($row = $resCursos->fetch_assoc()) {
    $cursos[] = $row;
}

require "../includes/header.php";
?>

<div class="sidebar">
    <h2>Panel UTP</h2>
    <ul>
        <li><a href="evaluaciones.php">📝 Evaluaciones</a></li>
        <li><a href="../login/logout.php">🚪 Cerrar sesión</a></li>
    </ul>
</div>

<div class="main">

    <?php require "../includes/topbar.php"; ?>

    <div class="page-header">
        <h2>Evaluaciones</h2>

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
        <form method="GET" action="exportar_calendarios.php" target="_blank" class="form-inline">
            <label for="tipo_exporte"><strong>Exportar:</strong></label>
            <select name="tipo" id="tipo_exporte">
                <option value="semana">Esta semana</option>
                <option value="proxima_semana">Próxima semana</option>
                <option value="mes">Este mes</option>
            </select>
            <button type="submit" class="btn btn-secondary">PDF</button>
        </form>
        <button type="button" class="btn btn-primary" onclick="abrirModalCrear()">
            + Crear evaluación
        </button>
        </div>
    </div>

    <?php if ($mensaje != "") { ?>
        <div class="alert-success"><?php echo $mensaje; ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php } ?>

    <div class="section">
        <?php if ($curso_filtro > 0) { ?>
            <div id="calendar"></div>
        <?php } else { ?>
            <p>Selecciona un curso para ver su calendario semanal de evaluaciones.</p>
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
            <div class="form-row">
                <label>Curso</label>
                <select name="curso_id" id="curso_id_crear" required>
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $curso) { ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo ($curso_filtro == $curso['id']) ? 'selected' : ''; ?>>
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
                <input type="date" name="fecha" id="fecha_crear" required>
            </div>

            <div class="form-row two-cols">
                <div>
                    <label>Hora inicio</label>
                    <input type="time" name="hora_inicio" id="hora_inicio_crear" required>
                </div>
                <div>
                    <label>Duración (minutos)</label>
                    <input type="number" name="duracion_minutos" id="duracion_crear" min="1" step="1" required>
                </div>
            </div>

            <div class="form-row">
                <label>Tipo</label>
                <select name="tipo" required>
                    <option value="Prueba">Prueba</option>
                    <option value="Control">Control</option>
                    <option value="Trabajo">Trabajo</option>
                    <option value="Exposición">Exposición</option>
                    <option value="Rúbrica">Rúbrica</option>
                    <option value="Pauta de cotejo">Pauta de cotejo</option>
                    <option value="Escala de apreciación">Escala de apreciación</option>
                    <option value="Trabajo grupal">Trabajo grupal</option>
                    <option value="Bitácora">Bitácora</option>
                    <option value="Revisión cuaderno">Revisión cuaderno</option>
                </select>
            </div>

            <div class="form-row">
                <label>Descripción</label>
                <textarea name="descripcion" rows="4" placeholder="Detalle de la evaluación"></textarea>
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
            <input type="hidden" name="evaluacion_id" id="editar_evaluacion_id">
            <input type="hidden" name="curso_id" id="editar_curso_id_hidden">

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
                <input type="date" name="fecha" id="fecha_editar" required>
            </div>

            <div class="form-row two-cols">
                <div>
                    <label>Hora inicio</label>
                    <input type="time" name="hora_inicio" id="hora_inicio_editar" required>
                </div>
                <div>
                    <label>Duración (minutos)</label>
                    <input type="number" name="duracion_minutos" id="duracion_editar" min="1" step="1" required>
                </div>
            </div>

            <div class="form-row">
                <label>Tipo</label>
                <select name="tipo" id="tipo_editar" required>
                    <option value="Prueba">Prueba</option>
                    <option value="Control">Control</option>
                    <option value="Trabajo">Trabajo</option>
                    <option value="Exposición">Exposición</option>
                    <option value="Rúbrica">Rúbrica</option>
                    <option value="Pauta de cotejo">Pauta de cotejo</option>
                    <option value="Escala de apreciación">Escala de apreciación</option>
                    <option value="Trabajo grupal">Trabajo grupal</option>
                    <option value="Bitácora">Bitácora</option>
                    <option value="Revisión cuaderno">Revisión cuaderno</option>
                </select>
            </div>

            <div class="form-row">
                <label>Descripción</label>
                <textarea name="descripcion" rows="4" id="descripcion_editar"></textarea>
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
function abrirModalCrear(){
    document.getElementById("modalCrear").classList.add("show");
}
function cerrarModalCrear(){
    document.getElementById("modalCrear").classList.remove("show");
}
function abrirModalEditar(){
    document.getElementById("modalEditar").classList.add("show");
}
function cerrarModalEditar(){
    document.getElementById("modalEditar").classList.remove("show");
}

function cargarAsignaturasCurso(cursoId, selectId, selectedValue = ""){
    const select = document.getElementById(selectId);
    select.innerHTML = '<option value="">Cargando...</option>';

    if(!cursoId){
        select.innerHTML = '<option value="">Seleccionar asignatura</option>';
        return;
    }

    fetch("obtener_asignaturas_curso.php?curso_id=" + cursoId)
    .then(r => r.json())
    .then(data => {
        select.innerHTML = '<option value="">Seleccionar asignatura</option>';

        data.forEach(a => {
            const option = document.createElement("option");
            option.value = a.id;
            option.textContent = a.nombre;

            if(selectedValue && selectedValue == a.id){
                option.selected = true;
            }

            select.appendChild(option);
        });
    });
}

document.getElementById("curso_id_crear").addEventListener("change", function(){
    cargarAsignaturasCurso(this.value,"curso_asignatura_id_crear");
});

document.addEventListener("DOMContentLoaded", function(){
    const cursoActual = document.getElementById("curso_id_crear").value;
    if(cursoActual){
        cargarAsignaturasCurso(cursoActual,"curso_asignatura_id_crear");
    }
});
</script>

<?php if ($curso_filtro > 0) { ?>
<script>
document.addEventListener('DOMContentLoaded', function(){

    const calendarEl = document.getElementById('calendar');

    function colorPorProfesor(profesorId) {
        const mapaColores = {
            // ejemplo:
            // 2: '#dc2626',
            // 3: '#2563eb',
            // 5: '#16a34a'
        };

        const coloresFallback = [
            '#dc2626',
            '#2563eb',
            '#16a34a',
            '#ca8a04',
            '#9333ea',
            '#0891b2',
            '#ea580c',
            '#4f46e5',
            '#be123c',
            '#0f766e'
        ];

        if (mapaColores[profesorId]) {
            return mapaColores[profesorId];
        }

        if (!profesorId) {
            return '#475569';
        }

        return coloresFallback[profesorId % coloresFallback.length];
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        firstDay: 1,
        hiddenDays: [0,6],
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        allDaySlot: false,
        height: 780,
        nowIndicator: true,
        expandRows: true,
        selectable: true,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },

        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana'
        },

        noEventsContent: 'No hay evaluaciones',

        events:{
            url:'eventos_evaluaciones.php',
            extraParams:{
                curso_id:'<?php echo $curso_filtro; ?>'
            }
        },

        eventContent: function(info) {
            const profesor = info.event.extendedProps.profesor_nombre || '';
            const asignatura = info.event.title;
            const hora = info.timeText;

            return {
                html: `
                    <div style="font-size:12px;">
                        <b>${hora}</b><br>
                        ${asignatura}<br>
                        <span style="font-size:11px; opacity:0.9;">
                            ${profesor}
                        </span>
                    </div>
                `
            };
        },

        select:function(info){

            if (calendar.view.type !== 'timeGridWeek') {
                return;
            }

            abrirModalCrear();

            document.getElementById('curso_id_crear').value = '<?php echo $curso_filtro; ?>';

            cargarAsignaturasCurso(
                '<?php echo $curso_filtro; ?>',
                'curso_asignatura_id_crear'
            );

            document.getElementById('fecha_crear').value = info.startStr.substring(0,10);
            document.getElementById('hora_inicio_crear').value = info.startStr.substring(11,16);

            const inicio = info.startStr.substring(11,16);
            const fin = info.endStr.substring(11,16);

            const ini = inicio.split(':');
            const fi = fin.split(':');

            const minInicio = (parseInt(ini[0])*60)+parseInt(ini[1]);
            const minFin = (parseInt(fi[0])*60)+parseInt(fi[1]);

            document.getElementById('duracion_crear').value = minFin - minInicio;
        },

        dateClick:function(info){
            if (calendar.view.type !== 'dayGridMonth') {
                return;
            }

            abrirModalCrear();

            document.getElementById('curso_id_crear').value = '<?php echo $curso_filtro; ?>';

            cargarAsignaturasCurso(
                '<?php echo $curso_filtro; ?>',
                'curso_asignatura_id_crear'
            );

            document.getElementById('fecha_crear').value = info.dateStr;
            document.getElementById('hora_inicio_crear').value = '08:00';
            document.getElementById('duracion_crear').value = 60;
        },

        eventDidMount:function(info){
            const profesorId = info.event.extendedProps.creado_por || 0;
            const profesorNombre = info.event.extendedProps.profesor_nombre || 'Sin nombre';
            const color = colorPorProfesor(profesorId);

            info.el.style.backgroundColor = color;
            info.el.style.borderColor = color;
            info.el.style.color = '#ffffff';
            info.el.title = info.event.title + ' - ' + profesorNombre;
        },

        eventClick:function(info){
            const props = info.event.extendedProps;

            document.getElementById('editar_evaluacion_id').value = info.event.id;
            document.getElementById('editar_curso_id_hidden').value = props.curso_id;
            document.getElementById('curso_id_editar').value = props.curso_id;
            document.getElementById('fecha_editar').value = props.fecha;
            document.getElementById('hora_inicio_editar').value = props.hora_inicio;
            document.getElementById('duracion_editar').value = props.duracion_minutos;
            document.getElementById('tipo_editar').value = props.tipo;
            document.getElementById('descripcion_editar').value = props.descripcion || '';

            cargarAsignaturasCurso(
                props.curso_id,
                'curso_asignatura_id_editar',
                props.curso_asignatura_id
            );

            abrirModalEditar();
        }
    });

    calendar.render();
});
</script>
<?php } ?>

</body>
</html>