<?php

declare(strict_types=1);

/* ETAPA 4: ESTADO OPERATIVO */
require_once __DIR__ . '/../estado-operativo.php';

$estadoOperativo = cargarEstadoOperativoDesarrollo();
$estadoOperativoPorProyecto = indexarEstadoOperativoDesarrollo(
    $estadoOperativo
);

$estadoBackups = cargarBackupsProyectosDesarrollo();
$backupsPorProyecto = indexarBackupsProyectosDesarrollo(
    $estadoBackups
);
?>

<section class="seccion seccion-proyectos" id="proyectos" aria-labelledby="titulo-proyectos">
    <div class="encabezado-seccion">
        <div>
            <span class="seccion-etiqueta">Explorador</span>
            <h2 id="titulo-proyectos">Proyectos del servidor</h2>
        </div>

        <span class="contador-resultados" id="contador-resultados">
            <?= count($proyectos) ?> proyectos
        </span>
    </div>

    <div class="barra-herramientas">
        <label class="buscador" for="buscar-proyecto">
            <span aria-hidden="true">⌕</span>

            <input
                id="buscar-proyecto"
                type="search"
                placeholder="Buscar por nombre o tecnología..."
                autocomplete="off"
            >
        </label>

        <div class="filtros" aria-label="Filtros de proyectos">
            <button class="filtro activo" type="button" data-filtro="todos">
                Todos
            </button>

            <button class="filtro" type="button" data-filtro="git">
                Git
            </button>

            <button class="filtro" type="button" data-filtro="bd">
                Base de datos
            </button>
        </div>
    </div>

    <div class="cuadricula-proyectos" id="cuadricula-proyectos">
        <?php foreach ($proyectos as $indice => $proyecto): ?>
            <?php
            $tecnologiasTexto = implode(', ', $proyecto['tecnologias']);
            $gitTexto = $proyecto['git']['activo']
                ? ($proyecto['git']['rama'] ?? 'Repositorio activo')
                : 'Sin repositorio';

            $claseDestacada = $proyecto['nombre_carpeta'] === 'centro-desarrollo'
                ? ' proyecto-destacado'
                : '';
            ?>

            <article
                class="tarjeta-proyecto<?= $claseDestacada ?>"
                data-nombre="<?= escapar(mb_strtolower($proyecto['nombre_visible'])) ?>"
                data-tecnologias="<?= escapar(mb_strtolower($tecnologiasTexto)) ?>"
                data-git="<?= $proyecto['git']['activo'] ? 'si' : 'no' ?>"
                data-bd="<?= $proyecto['usa_base_datos'] ? 'si' : 'no' ?>"
                style="--retraso: <?= $indice * 55 ?>ms"
            >
                <div class="proyecto-cabecera">
                    <div class="proyecto-icono">
                        <?= $proyecto['nombre_carpeta'] === 'centro-desarrollo'
                            ? '&lt;/&gt;'
                            : strtoupper(
                                escapar(
                                    mb_substr($proyecto['nombre_visible'], 0, 2)
                                )
                            )
                        ?>
                    </div>

                    <div class="proyecto-estado">
                        <span class="<?= $proyecto['disponible']
                            ? 'estado-disponible'
                            : 'estado-no-disponible' ?>"></span>

                        <?= $proyecto['disponible']
                            ? 'Disponible'
                            : 'Sin inicio' ?>
                    </div>
                </div>

                <div class="proyecto-contenido">
                    <div class="proyecto-titulo-linea">
                        <h3><?= escapar($proyecto['nombre_visible']) ?></h3>

                        <?php if ($proyecto['nombre_carpeta'] === 'centro-desarrollo'): ?>
                            <span class="insignia-actual">Actual</span>
                        <?php endif; ?>
                    </div>

                    <p><?= escapar($proyecto['descripcion']) ?></p>

                    <div class="tecnologias">
                        <?php if ($proyecto['tecnologias'] === []): ?>
                            <span class="tecnologia">Sin detectar</span>
                        <?php else: ?>
                            <?php foreach ($proyecto['tecnologias'] as $tecnologia): ?>
                                <span class="tecnologia">
                                    <?= escapar($tecnologia) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <dl class="proyecto-datos">
                    <div>
                        <dt>Archivos</dt>
                        <dd><?= number_format($proyecto['cantidad_archivos'], 0, ',', '.') ?></dd>
                    </div>

                    <div>
                        <dt>Tamaño</dt>
                        <dd><?= escapar(
                            formatearBytesDesarrollo($proyecto['tamano_bytes'])
                        ) ?></dd>
                    </div>

                    <div>
                        <dt>Git</dt>
                        <dd><?= escapar($gitTexto) ?></dd>
                    </div>
                </dl>

                <?php
                /* ETAPA 4: DATOS DE ESTA TARJETA */
                $estadoProyecto = $estadoOperativoPorProyecto[
                    $proyecto['nombre_carpeta']
                ] ?? [
                    'git' => ['repositorio' => false],
                    'logs' => [],
                    'contenedores' => [],
                ];

                $gitProyecto = $estadoProyecto['git'] ?? [
                    'repositorio' => false,
                ];
                $logsProyecto = $estadoProyecto['logs'] ?? [];
                $contenedoresProyecto =
                    $estadoProyecto['contenedores'] ?? [];

                $backupProyecto = $backupsPorProyecto[
                    $proyecto['nombre_carpeta']
                ] ?? null;

                $backupCorrecto = is_array($backupProyecto)
                    && ($backupProyecto['estado'] ?? '') === 'correcto'
                    && ($backupProyecto['checksum'] ?? '') === 'correcto';
                ?>
                <div class="proyecto-pie">
                    <?php if ($proyecto['disponible']): ?>
                        <a
                            class="boton boton-primario boton-abrir"
                            href="<?= escapar($proyecto['url']) ?>"
                        >
                            Abrir proyecto
                            <span aria-hidden="true">↗</span>
                        </a>
                    <?php else: ?>
                        <span class="boton boton-deshabilitado">
                            Sin archivo de inicio
                        </span>
                    <?php endif; ?>

                    <div
                        class="proyecto-acciones"
                        aria-label="Acciones de <?= escapar(
                            $proyecto['nombre_visible']
                        ) ?>"
                    >
                        <a
                            class="boton boton-secundario boton-accion boton-editar"
                            href="<?= escapar(
                                '//'
                                . preg_replace(
                                    '/:\d+$/',
                                    '',
                                    $_SERVER['HTTP_HOST']
                                        ?? (getenv('HOMELAB_SERVER_HOST') ?: '192.0.2.10')
                                )
                                . ':8082/files/proyectos/'
                                . rawurlencode(
                                    $proyecto['nombre_carpeta']
                                )
                                . '/'
                            ) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            title="Editar archivos en File Browser"
                        >
                            <span aria-hidden="true">&lt;/&gt;</span>
                            Editar
                        </a>

                        <?php if ($proyecto['usa_base_datos']): ?>
                            <a
                                class="boton boton-accion boton-base-datos"
                                href="<?= escapar(
                                    '//'
                                    . preg_replace(
                                        '/:\d+$/',
                                        '',
                                        $_SERVER['HTTP_HOST']
                                            ?? (getenv('HOMELAB_SERVER_HOST') ?: '192.0.2.10')
                                    )
                                    . ':8084/index.php'
                                    . '?route=/database/structure'
                                    . '&db=laboratorio'
                                ) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Abrir la base laboratorio en phpMyAdmin"
                            >
                                <span aria-hidden="true">▤</span>
                                BD
                            </a>
                        <?php else: ?>
                            <span
                                class="boton boton-accion boton-bd-deshabilitado"
                                aria-disabled="true"
                                title="No se detectó uso de base de datos"
                            >
                                <span aria-hidden="true">▤</span>
                                Sin BD
                            </span>
                        <?php endif; ?>

                        <a
                            class="boton boton-secundario boton-accion boton-ficha"
                            href="proyecto.php?id=<?= rawurlencode($proyecto["nombre_carpeta"]) ?>"
                            title="Abrir la ficha completa del proyecto"
                        >
                            <span aria-hidden="true">▣</span>
                            Ficha
                        </a>

                        <button
                            class="boton boton-secundario boton-accion boton-detalles"
                            type="button"
                            data-nombre="<?= escapar($proyecto['nombre_visible']) ?>"
                            data-descripcion="<?= escapar($proyecto['descripcion']) ?>"
                            data-carpeta="<?= escapar($proyecto['nombre_carpeta']) ?>"
                            data-archivos="<?= $proyecto['cantidad_archivos'] ?>"
                            data-tamano="<?= escapar(
                                formatearBytesDesarrollo(
                                    $proyecto['tamano_bytes']
                                )
                            ) ?>"
                            data-tecnologias="<?= escapar(
                                $tecnologiasTexto !== ''
                                    ? $tecnologiasTexto
                                    : 'Sin tecnologías detectadas'
                            ) ?>"
                            data-git="<?= escapar($gitTexto) ?>"
                            data-base-datos="<?= $proyecto['usa_base_datos']
                                ? 'Sí'
                                : 'No' ?>"
                            data-fecha="<?= escapar(
                                formatearFechaProyecto(
                                    $proyecto['ultima_modificacion']
                                )
                            ) ?>"
                        >
                            <span aria-hidden="true">•••</span>
                            Detalles
                        </button>
                    </div>
                        <div
                            class="proyecto-herramientas"
                            aria-label="Estado operativo de <?= escapar(
                                $proyecto['nombre_visible']
                            ) ?>"
                        >
                            <?php if (
                                !empty($gitProyecto['repositorio'])
                            ): ?>
                                <button
                                    class="boton boton-operativo boton-git"
                                    type="button"
                                    data-panel-operativo="git"
                                    data-proyecto="<?= escapar(
                                        $proyecto['nombre_carpeta']
                                    ) ?>"
                                    data-nombre="<?= escapar(
                                        $proyecto['nombre_visible']
                                    ) ?>"
                                >
                                    <span aria-hidden="true">⑂</span>
                                    Git
                                </button>
                            <?php else: ?>
                                <span
                                    class="boton boton-operativo-deshabilitado"
                                    title="Este proyecto todavía no es un repositorio"
                                >
                                    <span aria-hidden="true">⑂</span>
                                    Sin Git
                                </span>
                            <?php endif; ?>

                            <?php if (count($logsProyecto) > 0): ?>
                                <button
                                    class="boton boton-operativo boton-logs"
                                    type="button"
                                    data-panel-operativo="logs"
                                    data-proyecto="<?= escapar(
                                        $proyecto['nombre_carpeta']
                                    ) ?>"
                                    data-nombre="<?= escapar(
                                        $proyecto['nombre_visible']
                                    ) ?>"
                                >
                                    <span aria-hidden="true">≡</span>
                                    Logs <?= count($logsProyecto) ?>
                                </button>
                            <?php else: ?>
                                <span
                                    class="boton boton-operativo-deshabilitado"
                                    title="No se detectaron archivos .log"
                                >
                                    <span aria-hidden="true">≡</span>
                                    Sin logs
                                </span>
                            <?php endif; ?>

                            <?php if (
                                count($contenedoresProyecto) > 0
                            ): ?>
                                <button
                                    class="boton boton-operativo boton-docker"
                                    type="button"
                                    data-panel-operativo="docker"
                                    data-proyecto="<?= escapar(
                                        $proyecto['nombre_carpeta']
                                    ) ?>"
                                    data-nombre="<?= escapar(
                                        $proyecto['nombre_visible']
                                    ) ?>"
                                >
                                    <span aria-hidden="true">⬡</span>
                                    Docker <?= count(
                                        $contenedoresProyecto
                                    ) ?>
                                </button>
                            <?php else: ?>
                                <span
                                    class="boton boton-operativo-deshabilitado"
                                    title="No se detectaron contenedores relacionados"
                                >
                                    <span aria-hidden="true">⬡</span>
                                    Sin Docker
                                </span>
                            <?php endif; ?>

                            <?php if (is_array($backupProyecto)): ?>
                                <button
                                    class="boton boton-operativo boton-backup<?= $backupCorrecto
                                        ? ' boton-backup-correcto'
                                        : ' boton-backup-error' ?>"
                                    type="button"
                                    data-panel-operativo="backup"
                                    data-proyecto="<?= escapar(
                                        $proyecto['nombre_carpeta']
                                    ) ?>"
                                    data-nombre="<?= escapar(
                                        $proyecto['nombre_visible']
                                    ) ?>"
                                    title="Ver el último backup individual"
                                >
                                    <span aria-hidden="true">◆</span>
                                    Backup
                                </button>
                            <?php else: ?>
                                <span
                                    class="boton boton-operativo-deshabilitado"
                                    title="Todavía no existe un backup individual"
                                >
                                    <span aria-hidden="true">◇</span>
                                    Sin backup
                                </span>
                            <?php endif; ?>
                        </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="sin-resultados" id="sin-resultados" hidden>
        <span>&lt;/&gt;</span>
        <h3>No encontramos proyectos</h3>
        <p>Probá con otra búsqueda o cambiá el filtro seleccionado.</p>
    </div>
</section>

<dialog class="modal" id="modal-detalles">
    <div class="modal-contenido">
        <div class="modal-cabecera">
            <div>
                <span class="seccion-etiqueta">Información del proyecto</span>
                <h2 id="detalle-nombre">Detalles</h2>
            </div>

            <button
                class="modal-cerrar"
                id="cerrar-modal"
                type="button"
                aria-label="Cerrar detalles"
            >
                ×
            </button>
        </div>

        <p class="modal-descripcion" id="detalle-descripcion"></p>

        <dl class="lista-detalles">
            <div>
                <dt>Carpeta</dt>
                <dd id="detalle-carpeta"></dd>
            </div>

            <div>
                <dt>Archivos</dt>
                <dd id="detalle-archivos"></dd>
            </div>

            <div>
                <dt>Tamaño</dt>
                <dd id="detalle-tamano"></dd>
            </div>

            <div>
                <dt>Tecnologías</dt>
                <dd id="detalle-tecnologias"></dd>
            </div>

            <div>
                <dt>Repositorio Git</dt>
                <dd id="detalle-git"></dd>
            </div>

            <div>
                <dt>Base de datos</dt>
                <dd id="detalle-base-datos"></dd>
            </div>

            <div>
                <dt>Última modificación</dt>
                <dd id="detalle-fecha"></dd>
            </div>
        </dl>

        <div class="modal-nota">
            Los detalles son informativos; la creación se valida en el servidor.
        </div>
    </div>
</dialog>

<!-- ETAPA 4: MODAL DE ESTADO OPERATIVO -->
<dialog class="modal modal-estado-operativo" id="modal-estado-operativo">
    <div class="estado-operativo-contenido">
        <header class="estado-operativo-cabecera">
            <div>
                <span class="estado-operativo-etiqueta" id="estado-operativo-tipo">
                    Estado
                </span>
                <h2 id="estado-operativo-titulo">Estado operativo</h2>
            </div>

            <form method="dialog">
                <button
                    class="estado-operativo-cerrar"
                    type="submit"
                    aria-label="Cerrar"
                >
                    ×
                </button>
            </form>
        </header>

        <div
            class="estado-operativo-cuerpo"
            id="estado-operativo-cuerpo"
        ></div>

        <footer class="estado-operativo-pie">
            <span>Información segura de solo lectura</span>
            <time id="estado-operativo-fecha"></time>
        </footer>
    </div>
</dialog>

<?php
$estadoCliente = $estadoOperativo;
$estadoCliente['backups'] = $estadoBackups;
?>
<script id="estado-operativo-datos" type="application/json"><?=
    json_encode(
        $estadoCliente,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    )
?></script>
<script src="recursos/js/estado-operativo.js?v=5-backups"></script>
