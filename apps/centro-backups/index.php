<?php

declare(strict_types=1);

date_default_timezone_set('America/Argentina/Buenos_Aires');

define(
        'RUTA_ESTADO',
        getenv('HOMELAB_BACKUPS_STATUS_FILE')
            ?: '/var/lib/homelab/state/backups.json'
    );
define(
        'RUTA_BACKUPS',
        getenv('HOMELAB_BACKUPS_DIR')
            ?: '/var/lib/homelab/backups'
    );

function escapar(mixed $valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function formatearBytes(mixed $bytes): string
{
    $valor = max(0, (float) $bytes);
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $indice = 0;

    while ($valor >= 1024 && $indice < count($unidades) - 1) {
        $valor /= 1024;
        $indice++;
    }

    return number_format(
        $valor,
        $indice === 0 ? 0 : 2,
        ',',
        '.'
    ) . ' ' . $unidades[$indice];
}

function formatearFecha(?string $fecha): string
{
    if ($fecha === null || trim($fecha) === '') {
        return 'Sin datos';
    }

    try {
        return (new DateTimeImmutable($fecha))->format('d/m/Y H:i:s');
    } catch (Throwable) {
        return 'Fecha no válida';
    }
}

function leerEstado(): array
{
    if (!is_readable(RUTA_ESTADO)) {
        return [];
    }

    $contenido = file_get_contents(RUTA_ESTADO);

    if ($contenido === false) {
        return [];
    }

    $datos = json_decode($contenido, true);

    return is_array($datos) ? $datos : [];
}

function verificarIntegridad(string $archivo): array
{
    $checksum = $archivo . '.sha256';

    if (!is_readable($checksum)) {
        return [
            'estado' => 'faltante',
            'texto' => 'Sin checksum',
        ];
    }

    $lineas = file(
        $checksum,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    $primeraLinea = $lineas[0] ?? '';

    if (!preg_match(
        '/^([a-f0-9]{64})\s+\*?.+$/i',
        trim($primeraLinea),
        $coincidencia
    )) {
        return [
            'estado' => 'error',
            'texto' => 'Checksum inválido',
        ];
    }

    $hashReal = hash_file('sha256', $archivo);

    if (
        $hashReal !== false
        && hash_equals(
            strtolower($coincidencia[1]),
            strtolower($hashReal)
        )
    ) {
        return [
            'estado' => 'correcto',
            'texto' => 'SHA-256 correcto',
        ];
    }

    return [
        'estado' => 'error',
        'texto' => 'Integridad fallida',
    ];
}

function obtenerHistorial(): array
{
    $historial = [];

    $categorias = [
        'proyectos' => 'Proyectos',
        'sql' => 'MariaDB',
    ];

    foreach ($categorias as $carpeta => $etiqueta) {
        $directorio = RUTA_BACKUPS . '/' . $carpeta;

        if (!is_dir($directorio) || !is_readable($directorio)) {
            continue;
        }

        $archivos = scandir($directorio);

        if ($archivos === false) {
            continue;
        }

        foreach ($archivos as $nombre) {
            if (
                $nombre === '.'
                || $nombre === '..'
                || str_ends_with($nombre, '.sha256')
                || !preg_match('/\.(tar\.gz|sql\.gz)$/', $nombre)
            ) {
                continue;
            }

            $ruta = $directorio . '/' . $nombre;

            if (!is_file($ruta) || !is_readable($ruta)) {
                continue;
            }

            $fecha = filemtime($ruta);

            $historial[] = [
                'tipo' => $etiqueta,
                'nombre' => $nombre,
                'tamano' => filesize($ruta) ?: 0,
                'fecha' => $fecha === false ? 0 : $fecha,
                'integridad' => verificarIntegridad($ruta),
            ];
        }
    }

    usort(
        $historial,
        static fn(array $a, array $b): int =>
            $b['fecha'] <=> $a['fecha']
    );

    return $historial;
}

$estado = leerEstado();
$historialCompleto = obtenerHistorial();
$historial = array_slice($historialCompleto, 0, 20);
$alertas = [];

if ($estado === []) {
    $alertas[] = [
        'nivel' => 'error',
        'texto' => 'No se pudo leer el estado del Backup V2.',
    ];
} elseif (($estado['estado'] ?? '') !== 'correcto') {
    $alertas[] = [
        'nivel' => 'error',
        'texto' => 'La última ejecución no terminó correctamente.',
    ];
}

$fechaFinal = isset($estado['fecha_final'])
    ? strtotime((string) $estado['fecha_final'])
    : false;

if ($fechaFinal !== false && (time() - $fechaFinal) > 30 * 3600) {
    $alertas[] = [
        'nivel' => 'advertencia',
        'texto' => 'El último backup tiene más de 30 horas.',
    ];
}

$integridadFallida = count(array_filter(
    $historialCompleto,
    static fn(array $archivo): bool =>
        $archivo['integridad']['estado'] === 'error'
));

$checksumFaltante = count(array_filter(
    $historialCompleto,
    static fn(array $archivo): bool =>
        $archivo['integridad']['estado'] === 'faltante'
));

$integridadCorrecta = count(array_filter(
    $historialCompleto,
    static fn(array $archivo): bool =>
        $archivo['integridad']['estado'] === 'correcto'
));

if ($integridadFallida > 0) {
    $alertas[] = [
        'nivel' => 'error',
        'texto' => "$integridadFallida archivo(s) fallaron la verificación SHA-256.",
    ];
}

if ($checksumFaltante > 0) {
    $alertas[] = [
        'nivel' => 'advertencia',
        'texto' => "$checksumFaltante archivo(s) no tienen checksum.",
    ];
}

if ($historialCompleto === []) {
    $alertas[] = [
        'nivel' => 'error',
        'texto' => 'No se encontraron archivos de Backup V2.',
    ];
}

$nivelGeneral = 'correcto';

foreach ($alertas as $alerta) {
    if ($alerta['nivel'] === 'error') {
        $nivelGeneral = 'error';
        break;
    }

    $nivelGeneral = 'advertencia';
}

$textoGeneral = match ($nivelGeneral) {
    'error' => 'Requiere atención',
    'advertencia' => 'Revisar advertencias',
    default => 'Sistema protegido',
};

$ahora = new DateTimeImmutable();
$proximaEjecucion = $ahora->setTime(3, 30);

if ($proximaEjecucion <= $ahora) {
    $proximaEjecucion = $proximaEjecucion->modify('+1 day');
}

$totalBytes = array_sum(array_column($historialCompleto, 'tamano'));

$ultimaEjecucion = formatearFecha(
    isset($estado['fecha_final'])
        ? (string) $estado['fecha_final']
        : null
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Centro de Backups · JGG3360</title>
    <link rel="stylesheet" href="recursos/css/estilos.css">
</head>
<body>
<div class="aplicacion">
    <header class="barra-superior">
        <a class="marca" href="../centro-desarrollo/">
            <span class="marca-icono">J</span>
            <span>
                <strong>JGG3360</strong>
                <small>Infraestructura</small>
            </span>
        </a>

        <nav class="acciones">
            <a href="../centro-servidor/">Centro del Servidor</a>
            <button type="button" data-recargar>Actualizar</button>
        </nav>
    </header>

    <main>
        <section class="cabecera">
            <div>
                <span class="sobrelinea">Protección de datos</span>
                <h1>Centro de Backups</h1>
                <p>
                    Supervisión del respaldo de proyectos y bases de datos
                    del servidor.
                </p>
            </div>

            <div class="reloj">
                <span>Hora del servidor</span>
                <strong data-reloj>--:--:--</strong>
                <small>America/Argentina/Buenos_Aires</small>
            </div>
        </section>

        <section class="estado-principal estado-<?= escapar($nivelGeneral) ?>">
            <div class="estado-senal"><span></span></div>

            <div class="estado-contenido">
                <span>Estado general</span>
                <h2><?= escapar($textoGeneral) ?></h2>
                <p>
                    Última ejecución:
                    <strong><?= escapar($ultimaEjecucion) ?></strong>
                </p>
            </div>

            <div class="estado-version">
                <span>Motor</span>
                <strong>Backup V<?= escapar($estado['version'] ?? 2) ?></strong>
            </div>
        </section>

        <section class="metricas">
            <article class="metrica">
                <span>Último respaldo</span>
                <strong>
                    <?= escapar(formatearBytes(
                        $estado['tamano_proyectos_bytes'] ?? 0
                    )) ?>
                </strong>
                <small>Archivo de proyectos</small>
            </article>

            <article class="metrica">
                <span>Duración</span>
                <strong><?= escapar($estado['duracion_segundos'] ?? 0) ?> s</strong>
                <small>Última ejecución</small>
            </article>

            <article class="metrica">
                <span>Próxima ejecución</span>
                <strong><?= escapar($proximaEjecucion->format('H:i')) ?></strong>
                <small><?= escapar($proximaEjecucion->format('d/m/Y')) ?></small>
            </article>

            <article class="metrica">
                <span>Retención</span>
                <strong><?= escapar($estado['retencion_dias'] ?? 30) ?> días</strong>
                <small>Limpieza automática</small>
            </article>
        </section>

        <section class="rejilla">
            <article class="panel">
                <div class="panel-cabecera">
                    <div>
                        <span class="sobrelinea">Cobertura</span>
                        <h2>Elementos protegidos</h2>
                    </div>
                </div>

                <div class="cobertura">
                    <div class="elemento">
                        <span class="elemento-icono azul">P</span>
                        <div>
                            <strong>Proyectos</strong>
                            <small>
                                <?= escapar(
                                    $estado['archivo_proyectos'] ?? 'Sin datos'
                                ) ?>
                            </small>
                        </div>
                        <span class="insignia correcto">Protegido</span>
                    </div>

                    <div class="elemento">
                        <span class="elemento-icono violeta">DB</span>
                        <div>
                            <strong>MariaDB</strong>
                            <small>
                                Contenedor
                                <?= escapar(
                                    $estado['contenedor_mariadb'] ?? 'sin datos'
                                ) ?>
                            </small>
                        </div>
                        <span class="insignia correcto">
                            <?= escapar($estado['bases_exportadas'] ?? 0) ?>
                            base(s)
                        </span>
                    </div>

                    <div class="elemento">
                        <span class="elemento-icono verde">R</span>
                        <div>
                            <strong>Restauración comprobada</strong>
                            <small>Prueba aislada realizada el 03/08/2026</small>
                        </div>
                        <span class="insignia correcto">Aprobada</span>
                    </div>
                </div>
            </article>

            <article class="panel">
                <div class="panel-cabecera">
                    <div>
                        <span class="sobrelinea">Integridad</span>
                        <h2>Verificación SHA-256</h2>
                    </div>
                </div>

                <div class="integridad">
                    <div class="anillo">
                        <strong><?= escapar($integridadCorrecta) ?></strong>
                        <span>correctos</span>
                    </div>

                    <dl>
                        <div>
                            <dt>Archivos detectados</dt>
                            <dd><?= escapar(count($historialCompleto)) ?></dd>
                        </div>
                        <div>
                            <dt>Espacio almacenado</dt>
                            <dd><?= escapar(formatearBytes($totalBytes)) ?></dd>
                        </div>
                        <div>
                            <dt>Fallos</dt>
                            <dd><?= escapar($integridadFallida) ?></dd>
                        </div>
                    </dl>
                </div>
            </article>
        </section>

        <section class="panel historial">
            <div class="panel-cabecera">
                <div>
                    <span class="sobrelinea">Inventario</span>
                    <h2>Historial de archivos</h2>
                </div>
                <span class="contador">
                    <?= escapar(count($historialCompleto)) ?> archivos
                </span>
            </div>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th>Tamaño</th>
                        <th>Integridad</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($historial === []): ?>
                        <tr>
                            <td colspan="5" class="sin-datos">
                                No hay backups disponibles.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $archivo): ?>
                            <tr>
                                <td>
                                    <span class="tipo">
                                        <?= escapar($archivo['tipo']) ?>
                                    </span>
                                </td>
                                <td class="nombre-archivo">
                                    <?= escapar($archivo['nombre']) ?>
                                </td>
                                <td>
                                    <?= escapar(date(
                                        'd/m/Y H:i',
                                        $archivo['fecha']
                                    )) ?>
                                </td>
                                <td>
                                    <?= escapar(formatearBytes(
                                        $archivo['tamano']
                                    )) ?>
                                </td>
                                <td>
                                    <span class="insignia <?= escapar(
                                        $archivo['integridad']['estado']
                                    ) ?>">
                                        <?= escapar(
                                            $archivo['integridad']['texto']
                                        ) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel alertas">
            <div class="panel-cabecera">
                <div>
                    <span class="sobrelinea">Diagnóstico</span>
                    <h2>Alertas del sistema</h2>
                </div>
            </div>

            <?php if ($alertas === []): ?>
                <div class="mensaje correcto">
                    <span></span>
                    <div>
                        <strong>Todo funciona correctamente</strong>
                        <p>
                            No se detectaron fallos, archivos dañados
                            ni copias atrasadas.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($alertas as $alerta): ?>
                    <div class="mensaje <?= escapar($alerta['nivel']) ?>">
                        <span></span>
                        <div>
                            <strong>
                                <?= $alerta['nivel'] === 'error'
                                    ? 'Atención requerida'
                                    : 'Advertencia' ?>
                            </strong>
                            <p><?= escapar($alerta['texto']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <footer>
            <span>Backups montados en modo de solo lectura.</span>
            <span>Datos actualizados al cargar esta página.</span>
        </footer>
    </main>
</div>

<script src="recursos/js/app.js"></script>
</body>
</html>
