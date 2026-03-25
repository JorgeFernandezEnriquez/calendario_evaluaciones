<?php
require "../bd/conexion.php";

$curso_id = intval($_POST['curso_id'] ?? 0);

// Obtener ids de curso_asignatura del curso
$curso_asigs = [];
$result = $conn->query("SELECT id FROM curso_asignatura WHERE curso_id = $curso_id");
while($row = $result->fetch_assoc()) $curso_asigs[] = $row['id'];

// Borrar asignaciones del curso
if(!empty($curso_asigs)){
    $ids = implode(',', $curso_asigs);
    $conn->query("DELETE FROM curso_asignatura_profesor WHERE curso_asignatura_id IN ($ids)");
}

// Insertar nuevas asignaciones
if(isset($_POST['asignaciones'])){
    foreach($_POST['asignaciones'] as $usuario_id => $asigs){
        foreach($asigs as $curso_asig_id => $val){
            $usuario_id = intval($usuario_id);
            $curso_asig_id = intval($curso_asig_id);
            $conn->query("INSERT INTO curso_asignatura_profesor (usuario_id, curso_asignatura_id) VALUES ($usuario_id, $curso_asig_id)");
        }
    }
}

header("Location: asignar_profesores.php?curso_id=$curso_id");
exit;