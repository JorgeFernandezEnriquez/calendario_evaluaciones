<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

header('Content-Type: application/json');

$curso_asignatura_id = isset($_POST['curso_asignatura_id']) ? (int)$_POST['curso_asignatura_id'] : 0;
$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;

if ($curso_asignatura_id <= 0) {
    echo json_encode([
        "ok" => false,
        "error" => "Asignación inválida."
    ]);
    exit;
}

/* verificar que exista curso_asignatura */
$stmtCheckCA = $conn->prepare("SELECT id FROM curso_asignatura WHERE id = ?");
$stmtCheckCA->bind_param("i", $curso_asignatura_id);
$stmtCheckCA->execute();
$resCA = $stmtCheckCA->get_result();

if ($resCA->num_rows == 0) {
    echo json_encode([
        "ok" => false,
        "error" => "La asignatura del curso no existe."
    ]);
    exit;
}

/* borrar asignación anterior */
$stmtDelete = $conn->prepare("DELETE FROM curso_asignatura_profesor WHERE curso_asignatura_id = ?");
$stmtDelete->bind_param("i", $curso_asignatura_id);
$stmtDelete->execute();

/* si viene vacío, se deja sin asignar */
if ($usuario_id <= 0) {
    echo json_encode([
        "ok" => true
    ]);
    exit;
}

/* verificar que el usuario exista */
$stmtCheckUser = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
$stmtCheckUser->bind_param("i", $usuario_id);
$stmtCheckUser->execute();
$resUser = $stmtCheckUser->get_result();

if ($resUser->num_rows == 0) {
    echo json_encode([
        "ok" => false,
        "error" => "El usuario seleccionado no existe."
    ]);
    exit;
}

/* insertar nueva asignación */
$stmtInsert = $conn->prepare("
    INSERT INTO curso_asignatura_profesor (curso_asignatura_id, usuario_id)
    VALUES (?, ?)
");
$stmtInsert->bind_param("ii", $curso_asignatura_id, $usuario_id);

if ($stmtInsert->execute()) {
    echo json_encode([
        "ok" => true
    ]);
} else {
    echo json_encode([
        "ok" => false,
        "error" => "No se pudo guardar la asignación."
    ]);
}