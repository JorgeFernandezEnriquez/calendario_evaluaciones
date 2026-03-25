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

$data = [];

if ($curso_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            ca.id,
            a.nombre
        FROM curso_asignatura_profesor cap
        INNER JOIN curso_asignatura ca ON ca.id = cap.curso_asignatura_id
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
        WHERE cap.usuario_id = ?
        AND ca.curso_id = ?
        ORDER BY a.nombre
    ");
    $stmt->bind_param("ii", $profesor_id, $curso_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);