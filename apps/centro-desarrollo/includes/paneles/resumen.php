<?php

declare(strict_types=1);

$cantidadProyectos = count($proyectos);
$proyectosDisponibles = 0;
$proyectosGit = 0;
$proyectosBaseDatos = 0;
$tamanoTotal = 0;

foreach ($proyectos as $proyecto) {
    $tamanoTotal += $proyecto['tamano_bytes'];

    if ($proyecto['disponible']) {
        $proyectosDisponibles++;
    }

    if ($proyecto['git']['activo']) {
        $proyectosGit++;
    }

    if ($proyecto['usa_base_datos']) {
        $proyectosBaseDatos++;
    }
}
?>

<section class="seccion" id="resumen" aria-labelledby="titulo-resumen">
    <div class="encabezado-seccion">
        <div>
            <span class="seccion-etiqueta">Estado general</span>
            <h2 id="titulo-resumen">Resumen del laboratorio</h2>
        </div>

        <span class="actualizacion">
            Actualizado <?= date('d/m/Y H:i') ?>
        </span>
    </div>

    <div class="cuadricula-resumen">
        <article class="tarjeta-resumen resumen-proyectos">
            <div class="resumen-icono">&lt;/&gt;</div>

            <div>
                <span class="resumen-valor"><?= $cantidadProyectos ?></span>
                <span class="resumen-titulo">Proyectos detectados</span>
                <small><?= $proyectosDisponibles ?> disponibles para abrir</small>
            </div>
        </article>

        <article class="tarjeta-resumen resumen-git">
            <div class="resumen-icono">Git</div>

            <div>
                <span class="resumen-valor"><?= $proyectosGit ?></span>
                <span class="resumen-titulo">Repositorios Git</span>
                <small>Con control de versiones</small>
            </div>
        </article>

        <article class="tarjeta-resumen resumen-base">
            <div class="resumen-icono">DB</div>

            <div>
                <span class="resumen-valor"><?= $proyectosBaseDatos ?></span>
                <span class="resumen-titulo">Con base de datos</span>
                <small>SQL o conexión detectada</small>
            </div>
        </article>

        <article class="tarjeta-resumen resumen-espacio">
            <div class="resumen-icono">MB</div>

            <div>
                <span class="resumen-valor">
                    <?= escapar(formatearBytesDesarrollo($tamanoTotal)) ?>
                </span>

                <span class="resumen-titulo">Espacio utilizado</span>
                <small>Contenido analizado</small>
            </div>
        </article>
    </div>
</section>
