<?php

declare(strict_types=1);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Referrer-Policy: no-referrer'
);

header(
    'X-Frame-Options: SAMEORIGIN'
);

header(
    'Cache-Control: no-store, max-age=0'
);


$vista = $_GET['vista'] ?? 'resumen';

$vistasPermitidas = [
    'resumen',
    'biblioteca',
    'solicitudes',
    'descargas',
    'actividad',
    'almacenamiento',
    'sistema',
];

if (!in_array(
    $vista,
    $vistasPermitidas,
    true
)) {
    http_response_code(404);

    header(
        'Content-Type: text/plain; charset=utf-8'
    );

    echo 'Vista no encontrada.';
    exit;
}

$titulosVista = [
    'resumen' => 'Resumen',
    'biblioteca' => 'Biblioteca',
    'solicitudes' => 'Solicitudes',
    'descargas' => 'Descargas',
    'actividad' => 'Actividad',
    'almacenamiento' => 'Almacenamiento',
    'sistema' => 'Sistema',
];

$tituloVista = $titulosVista[$vista];

?>
<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow"
    >

    <title>
        Centro Multimedia ·
        <?= htmlspecialchars(
            $tituloVista,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

    <link
        rel="stylesheet"
        href="recursos/css/cmm.css?v=5b1-visual"
    >

</head>

<body>

<div class="cmm-shell">

    <header class="cmm-topbar">

        <div>

            <p class="cmm-eyebrow">
                homelab-server · Centro Multimedia
            </p>

            <h1>
                Centro Multimedia
            </h1>

            <p class="cmm-subtitle">
                Observabilidad y administración del
                ecosistema multimedia.
            </p>

        </div>


        <div
            id="cmm-estado-datos"
            class="cmm-data-status cmm-data-status--loading"
            aria-live="polite"
        >

            <span class="cmm-status-dot"></span>

            <span>
                Consultando datos…
            </span>

        </div>

    </header>


    <nav
        class="cmm-nav"
        aria-label="Secciones del Centro Multimedia"
    >

        <a
            href="?vista=resumen"
            class="cmm-nav-item<?= $vista === 'resumen'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'resumen'
                ? 'aria-current="page"'
                : '' ?>
        >
            Resumen
        </a>

        <a
            href="?vista=biblioteca"
            class="cmm-nav-item<?= $vista === 'biblioteca'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'biblioteca'
                ? 'aria-current="page"'
                : '' ?>
        >
            Biblioteca
        </a>

        <a
            href="?vista=solicitudes"
            class="cmm-nav-item<?= $vista === 'solicitudes'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'solicitudes'
                ? 'aria-current="page"'
                : '' ?>
        >
            Solicitudes
        </a>

        <a
            href="?vista=descargas"
            class="cmm-nav-item<?= $vista === 'descargas'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'descargas'
                ? 'aria-current="page"'
                : '' ?>
        >
            Descargas
        </a>

        <a
            href="?vista=actividad"
            class="cmm-nav-item<?= $vista === 'actividad'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'actividad'
                ? 'aria-current="page"'
                : '' ?>
        >
            Actividad
        </a>

        <a
            href="?vista=almacenamiento"
            class="cmm-nav-item<?= $vista === 'almacenamiento'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'almacenamiento'
                ? 'aria-current="page"'
                : '' ?>
        >
            Almacenamiento
        </a>

        <a
            href="?vista=sistema"
            class="cmm-nav-item<?= $vista === 'sistema'
                ? ' cmm-nav-item--active'
                : '' ?>"
            <?= $vista === 'sistema'
                ? 'aria-current="page"'
                : '' ?>
        >
            Sistema
        </a>

    </nav>


    <main class="cmm-main">

        <?php
        require __DIR__
            . '/vistas/'
            . $vista
            . '.php';
        ?>

    </main>


    <footer class="cmm-footer">

        <span>
            Centro Multimedia
        </span>

        <span>
            Datos derivados en modo lectura
        </span>

    </footer>

</div>


<script
    src="recursos/js/cmm.js?v=4d2"
    defer
></script>

</body>

</html>
