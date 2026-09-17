<?php

declare(strict_types=1);

use function CentroDesarrollo\extraerMensajePanel;
use function CentroDesarrollo\iniciarSesionPanel;
use function CentroDesarrollo\obtenerTokenCsrf;

require_once __DIR__ . '/includes/seguridad_panel.php';
require_once __DIR__ . '/includes/inicio.php';

iniciarSesionPanel();

$tokenCsrf = obtenerTokenCsrf();
$mensajePanel = extraerMensajePanel();

$tipoMensaje = is_array($mensajePanel)
    && in_array(
        $mensajePanel['tipo'] ?? '',
        ['exito', 'error', 'aviso'],
        true
    )
        ? $mensajePanel['tipo']
        : 'aviso';

$datosMensaje = is_array($mensajePanel['datos'] ?? null)
    ? $mensajePanel['datos']
    : [];

$datosFormulario = is_array($datosMensaje['formulario'] ?? null)
    ? $datosMensaje['formulario']
    : [];

$abrirFormulario = $tipoMensaje === 'error'
    && $datosFormulario !== [];

$tituloPagina = 'Centro de Desarrollo';
$paginaActual = 'inicio';

require __DIR__ . '/includes/encabezado.php';
require __DIR__ . '/includes/navegacion.php';
?>

<main class="contenido-principal">
    <?php if (is_array($mensajePanel)): ?>
        <aside
            class="mensaje-panel mensaje-<?= escapar($tipoMensaje) ?>"
            role="<?= $tipoMensaje === 'error' ? 'alert' : 'status' ?>"
            data-abrir-formulario="<?= $abrirFormulario ? 'si' : 'no' ?>"
        >
            <span class="mensaje-icono" aria-hidden="true">
                <?= $tipoMensaje === 'exito'
                    ? '✓'
                    : ($tipoMensaje === 'error' ? '!' : 'i') ?>
            </span>

            <div class="mensaje-contenido">
                <strong>
                    <?= $tipoMensaje === 'exito'
                        ? 'Operación completada'
                        : ($tipoMensaje === 'error'
                            ? 'Revisá la información'
                            : 'Aviso del sistema') ?>
                </strong>

                <p><?= escapar((string) ($mensajePanel['texto'] ?? '')) ?></p>
            </div>

            <?php if (
                $tipoMensaje === 'exito'
                && is_string($datosMensaje['url'] ?? null)
            ): ?>
                <a
                    class="boton boton-primario mensaje-accion"
                    href="<?= escapar($datosMensaje['url']) ?>"
                >
                    Abrir proyecto
                    <span aria-hidden="true">↗</span>
                </a>
            <?php endif; ?>

            <button
                class="mensaje-cerrar"
                type="button"
                aria-label="Cerrar mensaje"
            >
                ×
            </button>
        </aside>
    <?php endif; ?>

    <section class="hero" aria-labelledby="titulo-principal">
        <div class="hero-contenido">
            <span class="hero-etiqueta">Laboratorio web</span>

            <h1 id="titulo-principal">
                Todos tus proyectos,
                <span>en un solo lugar.</span>
            </h1>

            <p>
                Explorá los proyectos alojados en el servidor, consultá sus
                tecnologías y creá nuevos entornos desde un panel central.
            </p>

            <div class="hero-acciones">
                <a class="boton boton-primario" href="#proyectos">
                    Ver proyectos
                </a>

                <button
                    class="boton boton-secundario boton-nuevo-proyecto"
                    type="button"
                    data-abrir-nuevo-proyecto
                >
                    <span aria-hidden="true">＋</span>
                    Nuevo proyecto
                </button>

                <span class="estado-lectura">
                    <span class="punto-estado"></span>
                    Creación controlada activa
                </span>
            </div>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="ventana-codigo">
                <div class="ventana-barra">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="codigo">
                    <div><span class="codigo-numero">01</span><span class="codigo-azul">&lt;?php</span></div>
                    <div><span class="codigo-numero">02</span><span class="codigo-violeta">$servidor</span> = <span class="codigo-verde">'homelab-server'</span>;</div>
                    <div><span class="codigo-numero">03</span><span class="codigo-violeta">$proyectos</span> = <span class="codigo-azul"><?= count($proyectos) ?></span>;</div>
                    <div><span class="codigo-numero">04</span></div>
                    <div><span class="codigo-numero">05</span><span class="codigo-azul">foreach</span> (<span class="codigo-violeta">$proyectos</span> <span class="codigo-azul">as</span> <span class="codigo-violeta">$proyecto</span>) {</div>
                    <div><span class="codigo-numero">06</span>&nbsp;&nbsp;<span class="codigo-naranja">desarrollar</span>();</div>
                    <div><span class="codigo-numero">07</span>}</div>
                </div>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/includes/paneles/resumen.php'; ?>
    <?php require __DIR__ . '/includes/paneles/proyectos.php'; ?>
</main>

<?php require __DIR__ . '/includes/paneles/nuevo-proyecto.php'; ?>
<?php require __DIR__ . '/includes/pie.php'; ?>
