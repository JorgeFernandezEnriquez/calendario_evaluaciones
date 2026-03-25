<?php

function obtenerRolUsuarioParaRegistro(): string
{
    if (!isset($_SESSION['rol_id'])) {
        return 'desconocido';
    }

    switch ((int)$_SESSION['rol_id']) {
        case 1:
            return 'admin';
        case 2:
            return 'profesor';
        case 3:
            return 'utp';
        default:
            return 'desconocido';
    }
}

function obtenerDatosEvaluacionParaRegistro(mysqli $conn, int $curso_asignatura_id, string $fecha, string $hora_inicio, int $duracion_minutos, string $tipo, string $descripcion): ?array
{
    $stmt = $conn->prepare("
        SELECT 
            ca.id AS curso_asignatura_id,
            c.id AS curso_id,
            c.nombre AS curso_nombre,
            a.nombre AS asignatura_nombre
        FROM curso_asignatura ca
        INNER JOIN cursos c ON c.id = ca.curso_id
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
        WHERE ca.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $curso_asignatura_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return null;
    }

    $base = $res->fetch_assoc();

    return [
        'curso_id' => (int)$base['curso_id'],
        'curso_nombre' => $base['curso_nombre'],
        'curso_asignatura_id' => (int)$base['curso_asignatura_id'],
        'asignatura_nombre' => $base['asignatura_nombre'],
        'fecha_evaluacion' => $fecha,
        'hora_inicio' => $hora_inicio,
        'duracion_minutos' => $duracion_minutos,
        'tipo' => $tipo,
        'descripcion' => $descripcion
    ];
}

function obtenerEvaluacionCompletaParaRegistro(mysqli $conn, int $evaluacion_id): ?array
{
    $stmt = $conn->prepare("
        SELECT
            e.id AS evaluacion_id,
            e.curso_asignatura_id,
            e.fecha,
            e.hora_inicio,
            e.duracion_minutos,
            e.tipo,
            e.descripcion,
            ca.id AS curso_asignatura_id_real,
            c.id AS curso_id,
            c.nombre AS curso_nombre,
            a.nombre AS asignatura_nombre
        FROM evaluaciones e
        INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
        INNER JOIN cursos c ON c.id = ca.curso_id
        INNER JOIN asignaturas a ON a.id = ca.asignatura_id
        WHERE e.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $evaluacion_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return null;
    }

    $row = $res->fetch_assoc();

    return [
        'evaluacion_id' => (int)$row['evaluacion_id'],
        'curso_id' => (int)$row['curso_id'],
        'curso_nombre' => $row['curso_nombre'],
        'curso_asignatura_id' => (int)$row['curso_asignatura_id_real'],
        'asignatura_nombre' => $row['asignatura_nombre'],
        'fecha_evaluacion' => $row['fecha'],
        'hora_inicio' => $row['hora_inicio'],
        'duracion_minutos' => (int)$row['duracion_minutos'],
        'tipo' => $row['tipo'],
        'descripcion' => $row['descripcion']
    ];
}

function registrarAccionEvaluacion(
    mysqli $conn,
    string $accion,
    ?int $evaluacion_id,
    array $actual,
    ?array $anterior = null,
    string $detalle = ''
): bool {
    $usuario_id = isset($_SESSION['usuario']) ? (int)$_SESSION['usuario'] : 0;
    $usuario_nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Sin nombre';
    $usuario_rol = obtenerRolUsuarioParaRegistro();

    $stmt = $conn->prepare("
        INSERT INTO registros_evaluaciones (
            evaluacion_id,
            accion,
            usuario_id,
            usuario_nombre,
            usuario_rol,
            curso_id,
            curso_nombre,
            curso_asignatura_id,
            asignatura_nombre,
            fecha_evaluacion,
            hora_inicio,
            duracion_minutos,
            tipo,
            descripcion,
            fecha_evaluacion_anterior,
            hora_inicio_anterior,
            duracion_minutos_anterior,
            tipo_anterior,
            descripcion_anterior,
            curso_id_anterior,
            curso_nombre_anterior,
            curso_asignatura_id_anterior,
            asignatura_nombre_anterior,
            detalle
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $curso_id = $actual['curso_id'] ?? null;
    $curso_nombre = $actual['curso_nombre'] ?? '';
    $curso_asignatura_id = $actual['curso_asignatura_id'] ?? null;
    $asignatura_nombre = $actual['asignatura_nombre'] ?? '';
    $fecha_evaluacion = $actual['fecha_evaluacion'] ?? null;
    $hora_inicio = $actual['hora_inicio'] ?? null;
    $duracion_minutos = $actual['duracion_minutos'] ?? null;
    $tipo = $actual['tipo'] ?? null;
    $descripcion = $actual['descripcion'] ?? null;

    $fecha_evaluacion_anterior = $anterior['fecha_evaluacion'] ?? null;
    $hora_inicio_anterior = $anterior['hora_inicio'] ?? null;
    $duracion_minutos_anterior = $anterior['duracion_minutos'] ?? null;
    $tipo_anterior = $anterior['tipo'] ?? null;
    $descripcion_anterior = $anterior['descripcion'] ?? null;
    $curso_id_anterior = $anterior['curso_id'] ?? null;
    $curso_nombre_anterior = $anterior['curso_nombre'] ?? null;
    $curso_asignatura_id_anterior = $anterior['curso_asignatura_id'] ?? null;
    $asignatura_nombre_anterior = $anterior['asignatura_nombre'] ?? null;

    $stmt->bind_param(
    "isissisisssissssissisiss",
    $evaluacion_id,
    $accion,
    $usuario_id,
    $usuario_nombre,
    $usuario_rol,
    $curso_id,
    $curso_nombre,
    $curso_asignatura_id,
    $asignatura_nombre,
    $fecha_evaluacion,
    $hora_inicio,
    $duracion_minutos,
    $tipo,
    $descripcion,
    $fecha_evaluacion_anterior,
    $hora_inicio_anterior,
    $duracion_minutos_anterior,
    $tipo_anterior,
    $descripcion_anterior,
    $curso_id_anterior,
    $curso_nombre_anterior,
    $curso_asignatura_id_anterior,
    $asignatura_nombre_anterior,
    $detalle
);

    return $stmt->execute();
}