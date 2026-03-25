<?php
require "../includes/auth_admin.php";
require "../bd/conexion.php";

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'semana';
if ($tipo !== 'mes') {
    $tipo = 'semana';
}

/* CURSOS */
$cursos = [];
$resCursos = $conn->query("SELECT id, nombre FROM cursos ORDER BY id");
while ($row = $resCursos->fetch_assoc()) {
    $cursos[] = $row;
}

/* EVALUACIONES POR CURSO */
$eventosPorCurso = [];

$sql = "
    SELECT 
        e.id,
        e.fecha,
        e.hora_inicio,
        e.duracion_minutos,
        e.tipo,
        e.descripcion,
        c.id AS curso_id,
        c.nombre AS curso,
        a.nombre AS asignatura
    FROM evaluaciones e
    INNER JOIN curso_asignatura ca ON ca.id = e.curso_asignatura_id
    INNER JOIN cursos c ON c.id = ca.curso_id
    INNER JOIN asignaturas a ON a.id = ca.asignatura_id
    ORDER BY c.id, e.fecha, e.hora_inicio
";

$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $eventosPorCurso[$row['curso_id']][] = $row;
}

function colorTipo($tipo) {
    switch ($tipo) {
        case 'prueba': return '#fca5a5';
        case 'control': return '#93c5fd';
        case 'trabajo': return '#86efac';
        case 'disertacion': return '#fde68a';
        default: return '#d1d5db';
    }
}

function nombreDia($n) {
    $dias = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    return $dias[$n] ?? '';
}

$hoy = new DateTime();

$horaInicio = 8;
$horaFin = 18;
$altoGrid = 520;
$totalMinutos = ($horaFin - $horaInicio) * 60;

/* DATOS PARA SEMANA */
$diaSemana = (int)$hoy->format('N');
$lunes = clone $hoy;
$lunes->modify('-' . ($diaSemana - 1) . ' days');

$diasSemana = [];
for ($i = 0; $i < 5; $i++) {
    $dia = clone $lunes;
    $dia->modify("+{$i} days");
    $diasSemana[] = $dia;
}

/* DATOS PARA MES */
$primerDiaMes = new DateTime($hoy->format('Y-m-01'));
$ultimoDiaMes = new DateTime($hoy->format('Y-m-t'));

$inicioCalendarioMes = clone $primerDiaMes;
while ((int)$inicioCalendarioMes->format('N') !== 1) {
    $inicioCalendarioMes->modify('-1 day');
}

$finCalendarioMes = clone $ultimoDiaMes;
while ((int)$finCalendarioMes->format('N') !== 7) {
    $finCalendarioMes->modify('+1 day');
}

$diasMes = [];
$cursor = clone $inicioCalendarioMes;
while ($cursor <= $finCalendarioMes) {
    $diasMes[] = clone $cursor;
    $cursor->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendarios de evaluaciones por curso</title>
    <style>
        @page {
            size: letter landscape;
            margin: 5mm;
        }

        *{
            box-sizing:border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html, body{
            margin:0;
            padding:0;
            font-family: Arial, Helvetica, sans-serif;
            color:#0f172a;
            background:#fff;
        }

        body{
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .toolbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px 16px;
            border-bottom:1px solid #cbd5e1;
            background:#fff;
            position:sticky;
            top:0;
            z-index:100;
        }

        .toolbar h1{
            margin:0;
            font-size:20px;
        }

        .btn-print{
            border:none;
            background:#2563eb;
            color:#fff;
            padding:9px 14px;
            border-radius:8px;
            cursor:pointer;
            font-size:14px;
        }

        .page{
            page-break-after:always;
            padding:8px;
            width:100%;
        }

        .page:last-child{
            page-break-after:auto;
        }

        .titulo{
            display:flex;
            justify-content:space-between;
            align-items:flex-end;
            margin-bottom:10px;
        }

        .titulo h2{
            margin:0;
            font-size:20px;
        }

        .subtitulo{
            color:#475569;
            font-size:12px;
            margin-top:4px;
        }

        .contenedor-calendario{
            width:100%;
            overflow:hidden;
        }

        .semana{
            display:grid;
            grid-template-columns: 65px repeat(5, 1fr);
            border:1px solid #cbd5e1;
            border-bottom:none;
            width:100%;
        }

        .semana-header{
            background:#f8fafc !important;
            border-bottom:1px solid #cbd5e1;
            border-right:1px solid #cbd5e1;
            padding:8px 6px;
            font-weight:bold;
            text-align:center;
            min-height:50px;
            font-size:11px;
        }

        .semana-header:last-child{
            border-right:none;
        }

        .hora-col{
            border-right:1px solid #cbd5e1;
            border-bottom:1px solid #cbd5e1;
            background:#f8fafc !important;
        }

        .hora-label{
            height:52px;
            line-height:52px;
            text-align:center;
            font-size:11px;
            border-bottom:1px solid #e2e8f0;
        }

        .grid-dia{
            position:relative;
            height:520px;
            border-right:1px solid #cbd5e1;
            border-bottom:1px solid #cbd5e1;
            background:
                repeating-linear-gradient(
                    to bottom,
                    #ffffff 0px,
                    #ffffff 51px,
                    #e2e8f0 52px
                ) !important;
        }

        .grid-dia:last-child{
            border-right:none;
        }

        .evento{
            position:absolute;
            left:4px;
            right:4px;
            border:1px solid #64748b;
            border-radius:6px;
            padding:4px 6px;
            font-size:10px;
            overflow:hidden;
            color:#0f172a;
        }

        .evento strong{
            display:block;
            font-size:11px;
            margin-bottom:2px;
            line-height:1.15;
        }

        .evento small{
            display:block;
            color:#334155;
            margin-bottom:2px;
            line-height:1.1;
        }

        .leyenda{
            display:flex;
            gap:12px;
            margin-top:8px;
            font-size:11px;
            flex-wrap:wrap;
            page-break-inside:avoid;
        }

        .leyenda-item{
            display:flex;
            align-items:center;
            gap:6px;
        }

        .muestra{
            width:16px;
            height:10px;
            border:1px solid #64748b;
            border-radius:3px;
            display:inline-block;
        }

        .mes-grid{
    display:grid;
    grid-template-columns:repeat(7, minmax(0,1fr));
    border:1px solid #cbd5e1;
    border-bottom:none;
    width:100%;
    max-width:100%;
}

        .mes-header{
    background:#f8fafc !important;
    border-right:1px solid #cbd5e1;
    border-bottom:1px solid #cbd5e1;
    padding:6px 4px;
    text-align:center;
    font-weight:bold;
    font-size:11px;
}

        .mes-header:last-child{
            border-right:none;
        }

        .mes-celda{
    min-height:105px;
    border-right:1px solid #cbd5e1;
    border-bottom:1px solid #cbd5e1;
    padding:4px;
    position:relative;
}

        .mes-celda:nth-child(7n){
            border-right:none;
        }

        .mes-numero{
            font-size:12px;
            font-weight:bold;
            margin-bottom:4px;
        }

        .otro-mes{
            background:#f8fafc !important;
            color:#94a3b8;
        }

        .evento-mes{
            border:1px solid #64748b;
            border-radius:4px;
            padding:3px 4px;
            margin-bottom:4px;
            font-size:10px;
            line-height:1.2;
        }

        @media print{
            .toolbar{
                display:none;
            }

            html, body{
                width:100%;
                height:auto;
            }

            .page{
                padding:2mm 1mm;
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <h1>Calendarios de evaluaciones por curso</h1>
    <button class="btn-print" onclick="window.print()">Guardar / Imprimir PDF</button>
</div>

<?php foreach ($cursos as $curso) { ?>
    <div class="page">

        <div class="titulo">
            <div>
                <h2><?php echo htmlspecialchars($curso['nombre']); ?></h2>
                <div class="subtitulo">
                    <?php if ($tipo === 'semana') { ?>
                        Semana del <?php echo $diasSemana[0]->format('d-m-Y'); ?> al <?php echo $diasSemana[4]->format('d-m-Y'); ?>
                    <?php } else { ?>
                        Mes de <?php echo $hoy->format('m-Y'); ?>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php if ($tipo === 'semana') { ?>
            <div class="contenedor-calendario">
                <div class="semana">
                    <div class="semana-header">Hora</div>

                    <?php foreach ($diasSemana as $dia) { ?>
                        <div class="semana-header">
                            <?php echo nombreDia((int)$dia->format('N')); ?><br>
                            <span style="font-weight:normal;"><?php echo $dia->format('d-m-Y'); ?></span>
                        </div>
                    <?php } ?>

                    <div class="hora-col">
                        <?php for ($h = $horaInicio; $h < $horaFin; $h++) { ?>
                            <div class="hora-label"><?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>:00</div>
                        <?php } ?>
                    </div>

                    <?php foreach ($diasSemana as $dia) { ?>
                        <div class="grid-dia">
                            <?php
                            if (!empty($eventosPorCurso[$curso['id']])) {
                                foreach ($eventosPorCurso[$curso['id']] as $ev) {

                                    if ($ev['fecha'] !== $dia->format('Y-m-d')) {
                                        continue;
                                    }

                                    $horaPartes = explode(':', $ev['hora_inicio']);
                                    $hora = (int)$horaPartes[0];
                                    $min = (int)$horaPartes[1];

                                    $minutoInicio = (($hora - $horaInicio) * 60) + $min;

                                    if ($minutoInicio < 0 || $minutoInicio > $totalMinutos) {
                                        continue;
                                    }

                                    $top = ($minutoInicio / $totalMinutos) * $altoGrid;
                                    $alto = ($ev['duracion_minutos'] / $totalMinutos) * $altoGrid;
                                    if ($alto < 26) $alto = 26;

                                    $fondo = colorTipo($ev['tipo']);
                                    ?>
                                    <div class="evento" style="top: <?php echo $top; ?>px; height: <?php echo $alto; ?>px; background: <?php echo $fondo; ?> !important;">
                                        <strong><?php echo htmlspecialchars($ev['asignatura']); ?></strong>
                                        <small><?php echo substr($ev['hora_inicio'], 0, 5); ?> · <?php echo (int)$ev['duracion_minutos']; ?> min</small>
                                        <small><?php echo ucfirst($ev['tipo']); ?></small>
                                    </div>
                                <?php
                                }
                            }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } else { ?>
            <div class="mes-grid">
                <div class="mes-header">Lunes</div>
                <div class="mes-header">Martes</div>
                <div class="mes-header">Miércoles</div>
                <div class="mes-header">Jueves</div>
                <div class="mes-header">Viernes</div>
                <div class="mes-header">Sábado</div>
                <div class="mes-header">Domingo</div>

                <?php foreach ($diasMes as $dia) { ?>
                    <div class="mes-celda <?php echo ($dia->format('m') !== $hoy->format('m')) ? 'otro-mes' : ''; ?>">
                        <div class="mes-numero"><?php echo $dia->format('d'); ?></div>

                        <?php
                        if (!empty($eventosPorCurso[$curso['id']])) {
                            foreach ($eventosPorCurso[$curso['id']] as $ev) {
                                if ($ev['fecha'] !== $dia->format('Y-m-d')) {
                                    continue;
                                }

                                $fondo = colorTipo($ev['tipo']);
                                ?>
                                <div class="evento-mes" style="background: <?php echo $fondo; ?> !important;">
                                    <strong><?php echo htmlspecialchars($ev['asignatura']); ?></strong><br>
                                    <?php echo substr($ev['hora_inicio'], 0, 5); ?> · <?php echo ucfirst($ev['tipo']); ?>
                                </div>
                            <?php
                            }
                        }
                        ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="leyenda">
            <div class="leyenda-item"><span class="muestra" style="background:#fca5a5 !important;"></span> Prueba</div>
            <div class="leyenda-item"><span class="muestra" style="background:#93c5fd !important;"></span> Control</div>
            <div class="leyenda-item"><span class="muestra" style="background:#86efac !important;"></span> Trabajo</div>
            <div class="leyenda-item"><span class="muestra" style="background:#fde68a !important;"></span> Disertación</div>
        </div>

    </div>
<?php } ?>

</body>
</html>