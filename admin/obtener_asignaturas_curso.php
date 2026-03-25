<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;

$data = [];

if ($curso_id > 0) {
    $stmt = $conn->prepare("
        SELECT ca.id, a.nombre
        FROM curso_asignatura ca
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
        WHERE ca.curso_id = ?
        ORDER BY a.nombre
    ");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);