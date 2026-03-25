<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$eventos = [];

if ($curso_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.fecha,
            e.hora_inicio,
            e.duracion_minutos,
            e.tipo,
            e.descripcion,
            e.curso_asignatura_id,
            e.creado_por,
            c.id AS curso_id,
            c.nombre AS curso,
            a.nombre AS asignatura,
            u.nombre AS profesor_nombre
        FROM evaluaciones e
        INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
        INNER JOIN cursos c ON c.id = ca.curso_id
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
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
                'curso' => $row['curso'],
                'curso_id' => $row['curso_id'],
                'fecha' => $row['fecha'],
                'hora_inicio' => substr($row['hora_inicio'], 0, 5),
                'duracion_minutos' => (int)$row['duracion_minutos'],
                'curso_asignatura_id' => $row['curso_asignatura_id'],
                'creado_por' => (int)$row['creado_por'],
                'profesor_nombre' => $row['profesor_nombre'] ?: 'Sin nombre'
            ]
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($eventos);