<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/inicio.php';
require_once __DIR__ . '/includes/estado-operativo.php';

function filasDetalleProyecto(array $datos): array
{
    $filas = [];

    foreach ($datos as $clave => $valor) {
        if (
            is_array($valor)
            || is_object($valor)
            || $valor === null
            || $valor === ''
        ) {
            continue;
        }

        if (is_bool($valor)) {
            $valor = $valor ? 'Sí' : 'No';
        }

        $etiqueta = str_replace(['_', '-'], ' ', (string) $clave);
        $etiqueta = mb_convert_case($etiqueta, MB_CASE_TITLE, 'UTF-8');

        $filas[] = [
            'etiqueta' => $etiqueta,
            'valor' => (string) $valor,
        ];
    }

    return $filas;
}

function nombreElementoProyecto(mixed $elemento, string $predeterminado): string
{
    if (is_string($elemento) && $elemento !== '') {
        return basename($elemento);
    }

    if (!is_array($elemento)) {
        return $predeterminado;
    }

    foreach (['nombre', 'name', 'contenedor', 'archivo', 'ruta'] as $clave) {
        if (isset($elemento[$clave]) && is_scalar($elemento[$clave])) {
            return basename((string) $elemento[$clave]);
        }
    }

    return $predeterminado;
}

$idProyecto = $_GET['id'] ?? '';

if (
    !is_string($idProyecto)
    || preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_-]{0,79}\z/D', $idProyecto) !== 1
) {
    $idProyecto = '';
}

$proyectoSeleccionado = null;

foreach ($proyectos as $proyectoDetectado) {
    if (
        $idProyecto !== ''
        && ($proyectoDetectado['nombre_carpeta'] ?? '') === $idProyecto
    ) {
        $proyectoSeleccionado = $proyectoDetectado;
        break;
    }
}

$tituloPagina = $proyectoSeleccionado !== null
    ? $proyectoSeleccionado['nombre_visible']
    : 'Proyecto no encontrado';

$hojaEstilosAdicional = 'recursos/css/proyecto.css?v=1';
$cargarAppPrincipal = false;

if ($proyectoSeleccionado === null) {
    http_response_code(404);
}

require __DIR__ . '/includes/encabezado.php';

if ($proyectoSeleccionado === null) {
    ?>
    <main class="pagina-proyecto pagina-proyecto-error">
        <section class="detalle-vacio" aria-labelledby="titulo-principal">
            <span class="detalle-vacio-icono">&lt;/&gt;</span>
            <h1 id="titulo-principal">Proyecto no encontrado</h1>
            <p>El identificador solicitado no existe o no es válido.</p>
            <a class="boton boton-primario" href="index.php#proyectos">
                Volver al Centro de Desarrollo
            </a>
        </section>
    </main>
    <?php
    require __DIR__ . '/includes/pie.php';
    exit;
}

$estadoOperativo = cargarEstadoOperativoDesarrollo();
$estadoPorProyecto = indexarEstadoOperativoDesarrollo($estadoOperativo);

$estadoBackups = cargarBackupsProyectosDesarrollo();
$backupsPorProyecto = indexarBackupsProyectosDesarrollo($estadoBackups);

$carpeta = $proyectoSeleccionado['nombre_carpeta'];

$estadoProyecto = $estadoPorProyecto[$carpeta] ?? [
    'git' => ['repositorio' => false],
    'logs' => [],
    'contenedores' => [],
];

$gitOperativo = is_array($estadoProyecto['git'] ?? null)
    ? $estadoProyecto['git']
    : [];

$logsProyecto = is_array($estadoProyecto['logs'] ?? null)
    ? $estadoProyecto['logs']
    : [];

$contenedoresProyecto = is_array($estadoProyecto['contenedores'] ?? null)
    ? $estadoProyecto['contenedores']
    : [];

$backupProyecto = is_array($backupsPorProyecto[$carpeta] ?? null)
    ? $backupsPorProyecto[$carpeta]
    : null;

$gitDetectado = is_array($proyectoSeleccionado['git'] ?? null)
    ? $proyectoSeleccionado['git']
    : [];

$datosGit = array_merge($gitDetectado, $gitOperativo);

$hostServidor = preg_replace(
    '/:\d+$/',
    '',
    $_SERVER['HTTP_HOST'] ?? (getenv('HOMELAB_SERVER_HOST') ?: '192.0.2.10')
);

if (!is_string($hostServidor) || $hostServidor === '') {
    $hostServidor = getenv('HOMELAB_SERVER_HOST') ?: '192.0.2.10';
}

$urlEdicion = '//'
    . $hostServidor
    . ':8082/files/proyectos/'
    . rawurlencode($carpeta)
    . '/';

$urlBaseDatos = '//'
    . $hostServidor
    . ':8084/';

$rutaProyecto = $proyectoSeleccionado['ruta']
    ?? RUTA_PROYECTOS . '/' . $carpeta;
?>

<main class="pagina-proyecto">
    <nav class="detalle-migas" aria-label="Ruta de navegación">
        <a href="index.php">Centro de Desarrollo</a>
        <span aria-hidden="true">/</span>
        <span><?= escapar($proyectoSeleccionado['nombre_visible']) ?></span>
    </nav>

    <section class="detalle-hero" aria-labelledby="titulo-principal">
        <div class="detalle-identidad">
            <div class="detalle-icono" aria-hidden="true">
                <?= $carpeta === 'centro-desarrollo'
                    ? '&lt;/&gt;'
                    : strtoupper(
                        escapar(
                            mb_substr(
                                $proyectoSeleccionado['nombre_visible'],
                                0,
                                2
                            )
                        )
                    ) ?>
            </div>

            <div>
                <div class="detalle-estado">
                    <span class="<?= $proyectoSeleccionado['disponible']
                        ? 'estado-disponible'
                        : 'estado-no-disponible' ?>"></span>

                    <?= $proyectoSeleccionado['disponible']
                        ? 'Proyecto disponible'
                        : 'Sin archivo de inicio' ?>
                </div>

                <h1 id="titulo-principal">
                    <?= escapar($proyectoSeleccionado['nombre_visible']) ?>
                </h1>

                <p><?= escapar($proyectoSeleccionado['descripcion']) ?></p>

                <div class="tecnologias">
                    <?php foreach ($proyectoSeleccionado['tecnologias'] as $tecnologia): ?>
                        <span class="tecnologia"><?= escapar($tecnologia) ?></span>
                    <?php endforeach; ?>

                    <?php if ($proyectoSeleccionado['tecnologias'] === []): ?>
                        <span class="tecnologia">Sin detectar</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="detalle-acciones">
            <?php if ($proyectoSeleccionado['disponible']): ?>
                <a
                    class="boton boton-primario"
                    href="<?= escapar($proyectoSeleccionado['url']) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Abrir proyecto ↗
                </a>
            <?php endif; ?>

            <a
                class="boton boton-secundario"
                href="<?= escapar($urlEdicion) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                Editar archivos
            </a>

            <a class="boton boton-secundario" href="index.php#proyectos">
                Volver
            </a>
        </div>
    </section>

    <section class="detalle-metricas" aria-label="Resumen del proyecto">
        <article>
            <span>Archivos</span>
            <strong><?= number_format(
                $proyectoSeleccionado['cantidad_archivos'],
                0,
                ',',
                '.'
            ) ?></strong>
        </article>

        <article>
            <span>Tamaño</span>
            <strong><?= escapar(
                formatearBytesDesarrollo(
                    $proyectoSeleccionado['tamano_bytes']
                )
            ) ?></strong>
        </article>

        <article>
            <span>Última modificación</span>
            <strong><?= escapar(
                formatearFechaProyecto(
                    $proyectoSeleccionado['ultima_modificacion']
                )
            ) ?></strong>
        </article>

        <article>
            <span>Base de datos</span>
            <strong><?= $proyectoSeleccionado['usa_base_datos']
                ? 'Detectada'
                : 'No detectada' ?></strong>
        </article>
    </section>

    <?php
    $proyectoDisponible = !empty($proyectoSeleccionado['disponible']);
    $usaBaseDatos = !empty($proyectoSeleccionado['usa_base_datos']);
    $cantidadContenedores = count($contenedoresProyecto);
    $cantidadLogs = count($logsProyecto);

    $accesosProyecto = [
        [
            'titulo' => 'Abrir proyecto',
            'descripcion' => $proyectoDisponible
                ? 'Aplicación web'
                : 'Sin archivo de inicio',
            'estado' => $proyectoDisponible
                ? 'Disponible'
                : 'No disponible',
            'icono' => '↗',
            'destino' => $proyectoSeleccionado['url'] ?? '#',
            'habilitado' => $proyectoDisponible,
            'nueva_pestana' => true,
        ],
        [
            'titulo' => 'Editar archivos',
            'descripcion' => 'File Browser',
            'estado' => 'Disponible',
            'icono' => '</>',
            'destino' => $urlEdicion,
            'habilitado' => true,
            'nueva_pestana' => true,
        ],
        [
            'titulo' => 'Base de datos',
            'descripcion' => $usaBaseDatos
                ? 'phpMyAdmin'
                : 'Sin conexión detectada',
            'estado' => $usaBaseDatos
                ? 'Detectada'
                : 'No detectada',
            'icono' => '▤',
            'destino' => $urlBaseDatos,
            'habilitado' => $usaBaseDatos,
            'nueva_pestana' => true,
        ],
        [
            'titulo' => 'Git',
            'descripcion' => !empty($datosGit['repositorio'])
                ? 'Control de versiones'
                : 'Sin repositorio',
            'estado' => !empty($datosGit['repositorio'])
                ? 'Detectado'
                : 'No detectado',
            'icono' => '⑂',
            'destino' => '#panel-git',
            'habilitado' => !empty($datosGit['repositorio']),
            'nueva_pestana' => false,
        ],
        [
            'titulo' => 'Docker',
            'descripcion' => $cantidadContenedores > 0
                ? $cantidadContenedores
                    . ' contenedor'
                    . ($cantidadContenedores === 1 ? '' : 'es')
                : 'Sin contenedores relacionados',
            'estado' => $cantidadContenedores > 0
                ? 'Detectado'
                : 'No detectado',
            'icono' => '⬡',
            'destino' => '#panel-docker',
            'habilitado' => $cantidadContenedores > 0,
            'nueva_pestana' => false,
        ],
        [
            'titulo' => 'Logs',
            'descripcion' => $cantidadLogs > 0
                ? $cantidadLogs
                    . ' archivo'
                    . ($cantidadLogs === 1 ? '' : 's')
                : 'Sin registros detectados',
            'estado' => $cantidadLogs > 0
                ? 'Disponible'
                : 'No disponible',
            'icono' => '≡',
            'destino' => '#panel-logs',
            'habilitado' => $cantidadLogs > 0,
            'nueva_pestana' => false,
        ],
        [
            'titulo' => 'Backup',
            'descripcion' => $backupProyecto !== null
                ? 'Última copia individual'
                : 'Sin copia individual',
            'estado' => $backupProyecto !== null
                ? 'Disponible'
                : 'No disponible',
            'icono' => '◆',
            'destino' => '#panel-backup',
            'habilitado' => $backupProyecto !== null,
            'nueva_pestana' => false,
        ],
    ];
    ?>

    <section class="detalle-accesos" aria-labelledby="titulo-accesos">
        <header class="detalle-accesos-encabezado">
            <div>
                <span class="detalle-sobrelinea">
                    Herramientas del proyecto
                </span>
                <h2 id="titulo-accesos">Accesos inteligentes</h2>
            </div>

            <p>
                Solo se habilitan las herramientas detectadas
                para este proyecto.
            </p>
        </header>

        <div class="detalle-accesos-grid">
            <?php foreach ($accesosProyecto as $acceso): ?>
                <?php if ($acceso['habilitado']): ?>
                    <a
                        class="detalle-acceso detalle-acceso-disponible"
                        href="<?= escapar($acceso['destino']) ?>"
                        <?php if ($acceso['nueva_pestana']): ?>
                            target="_blank"
                            rel="noopener noreferrer"
                        <?php endif; ?>
                    >
                        <span
                            class="detalle-acceso-icono"
                            aria-hidden="true"
                        >
                            <?= escapar($acceso['icono']) ?>
                        </span>

                        <span class="detalle-acceso-contenido">
                            <strong>
                                <?= escapar($acceso['titulo']) ?>
                            </strong>
                            <small>
                                <?= escapar($acceso['descripcion']) ?>
                            </small>
                        </span>

                        <span class="detalle-acceso-estado">
                            <?= escapar($acceso['estado']) ?>
                        </span>
                    </a>
                <?php else: ?>
                    <span
                        class="detalle-acceso detalle-acceso-deshabilitado"
                        aria-disabled="true"
                    >
                        <span
                            class="detalle-acceso-icono"
                            aria-hidden="true"
                        >
                            <?= escapar($acceso['icono']) ?>
                        </span>

                        <span class="detalle-acceso-contenido">
                            <strong>
                                <?= escapar($acceso['titulo']) ?>
                            </strong>
                            <small>
                                <?= escapar($acceso['descripcion']) ?>
                            </small>
                        </span>

                        <span class="detalle-acceso-estado">
                            <?= escapar($acceso['estado']) ?>
                        </span>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="detalle-cuadricula">
        <article id="panel-informacion" class="detalle-panel detalle-panel-general">
            <header>
                <span class="detalle-panel-icono">⌘</span>
                <div>
                    <span class="detalle-sobrelinea">Ubicación</span>
                    <h2>Información general</h2>
                </div>
            </header>

            <dl class="detalle-lista">
                <div>
                    <dt>Carpeta</dt>
                    <dd><?= escapar($carpeta) ?></dd>
                </div>

                <div>
                    <dt>Ruta interna</dt>
                    <dd><code><?= escapar($rutaProyecto) ?></code></dd>
                </div>

                <div>
                    <dt>URL</dt>
                    <dd>
                        <?php if ($proyectoSeleccionado['disponible']): ?>
                            <a href="<?= escapar($proyectoSeleccionado['url']) ?>">
                                <?= escapar($proyectoSeleccionado['url']) ?>
                            </a>
                        <?php else: ?>
                            No disponible
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </article>

        <article id="panel-git" class="detalle-panel">
            <header>
                <span class="detalle-panel-icono">⑂</span>
                <div>
                    <span class="detalle-sobrelinea">Control de versiones</span>
                    <h2>Git</h2>
                </div>
            </header>

            <?php $filasGit = filasDetalleProyecto($datosGit); ?>

            <?php if ($filasGit !== []): ?>
                <dl class="detalle-lista">
                    <?php foreach ($filasGit as $fila): ?>
                        <div>
                            <dt><?= escapar($fila['etiqueta']) ?></dt>
                            <dd><?= escapar($fila['valor']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p class="detalle-sin-datos">
                    Este proyecto todavía no posee información Git.
                </p>
            <?php endif; ?>
        </article>

        <article id="panel-docker" class="detalle-panel">
            <header>
                <span class="detalle-panel-icono">⬡</span>
                <div>
                    <span class="detalle-sobrelinea">Infraestructura</span>
                    <h2>Docker</h2>
                </div>

                <span class="detalle-contador">
                    <?= count($contenedoresProyecto) ?>
                </span>
            </header>

            <?php if ($contenedoresProyecto !== []): ?>
                <div class="detalle-elementos">
                    <?php foreach ($contenedoresProyecto as $indice => $contenedor): ?>
                        <section class="detalle-elemento">
                            <h3><?= escapar(
                                nombreElementoProyecto(
                                    $contenedor,
                                    'Contenedor ' . ($indice + 1)
                                )
                            ) ?></h3>

                            <?php if (is_array($contenedor)): ?>
                                <dl class="detalle-lista detalle-lista-compacta">
                                    <?php foreach (filasDetalleProyecto($contenedor) as $fila): ?>
                                        <div>
                                            <dt><?= escapar($fila['etiqueta']) ?></dt>
                                            <dd><?= escapar($fila['valor']) ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="detalle-sin-datos">
                    No se detectaron contenedores relacionados.
                </p>
            <?php endif; ?>
        </article>

        <article id="panel-logs" class="detalle-panel">
            <header>
                <span class="detalle-panel-icono">≡</span>
                <div>
                    <span class="detalle-sobrelinea">Diagnóstico</span>
                    <h2>Logs</h2>
                </div>

                <span class="detalle-contador"><?= count($logsProyecto) ?></span>
            </header>

            <?php if ($logsProyecto !== []): ?>
                <div class="detalle-elementos">
                    <?php foreach ($logsProyecto as $indice => $log): ?>
                        <section class="detalle-elemento">
                            <h3><?= escapar(
                                nombreElementoProyecto(
                                    $log,
                                    'Log ' . ($indice + 1)
                                )
                            ) ?></h3>

                            <?php if (is_array($log)): ?>
                                <dl class="detalle-lista detalle-lista-compacta">
                                    <?php foreach (filasDetalleProyecto($log) as $fila): ?>
                                        <div>
                                            <dt><?= escapar($fila['etiqueta']) ?></dt>
                                            <dd><?= escapar($fila['valor']) ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="detalle-sin-datos">
                    No se detectaron archivos de registro.
                </p>
            <?php endif; ?>
        </article>

        <article id="panel-backup" class="detalle-panel detalle-panel-backup">
            <header>
                <span class="detalle-panel-icono">◆</span>
                <div>
                    <span class="detalle-sobrelinea">Protección</span>
                    <h2>Último backup</h2>
                </div>
            </header>

            <?php if ($backupProyecto !== null): ?>
                <dl class="detalle-lista">
                    <?php foreach (filasDetalleProyecto($backupProyecto) as $fila): ?>
                        <div>
                            <dt><?= escapar($fila['etiqueta']) ?></dt>
                            <dd><?= escapar($fila['valor']) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p class="detalle-sin-datos">
                    Todavía no existe un backup individual detectado.
                </p>
            <?php endif; ?>
        </article>
    </section>
</main>

<?php require __DIR__ . '/includes/pie.php'; ?>
