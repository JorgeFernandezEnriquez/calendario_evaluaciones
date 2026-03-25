<?php
require "../includes/auth.php";
require "../bd/conexion.php";

if ($_SESSION['rol_id'] != 2) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$profesor_id = $_SESSION['usuario'];
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$eventos = [];

if ($curso_id > 0) {

    $stmtPermisoCurso = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM curso_asignatura_profesor cap
        INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
        WHERE cap.usuario_id = ?
        AND ca.curso_id = ?
    ");
    $stmtPermisoCurso->bind_param("ii", $profesor_id, $curso_id);
    $stmtPermisoCurso->execute();
    $permisoCurso = $stmtPermisoCurso->get_result()->fetch_assoc();

    if ($permisoCurso['total'] > 0) {

        $stmt = $conn->prepare("
            SELECT DISTINCT
                e.id,
                e.fecha,
                e.hora_inicio,
                e.duracion_minutos,
                e.tipo,
                e.descripcion,
                e.curso_asignatura_id,
                e.creado_por,
                c.id AS curso_id_real,
                a.nombre AS asignatura,
                u.nombre AS profesor_nombre
            FROM evaluaciones e
            INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
            INNER JOIN asignaturas a ON a.id = ca.asignatura_id
            INNER JOIN cursos c ON c.id = ca.curso_id
            LEFT JOIN usuarios u ON u.id = e.creado_por
            WHERE ca.curso_id = ?
            ORDER BY e.fecha, e.hora_inicio
        ");
        $stmt->bind_param("i", $curso_id);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $inicio = new DateTime($row['fecha'] . ' ' . $row['hora_inicio']);
            $fin = clone $inicio;
            $fin->modify('+' . (int)$row['duracion_minutos'] . ' minutes');

            $eventos[] = [
                'id' => $row['id'],
                'title' => $row['asignatura'],
                'start' => $inicio->format('Y-m-d\TH:i:s'),
                'end' => $fin->format('Y-m-d\TH:i:s'),
                'extendedProps' => [
                    'descripcion' => $row['descripcion'],
                    'tipo' => $row['tipo'],
                    'curso_id' => $row['curso_id_real'],
                    'fecha' => $row['fecha'],
                    'hora_inicio' => substr($row['hora_inicio'], 0, 5),
                    'duracion_minutos' => (int)$row['duracion_minutos'],
                    'curso_asignatura_id' => $row['curso_asignatura_id'],
                    'creado_por' => (int)$row['creado_por'],
                    'es_propia' => ((int)$row['creado_por'] === (int)$profesor_id),
                    'profesor_nombre' => $row['profesor_nombre']
                ]
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($eventos);