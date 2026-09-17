<?php

require_once __DIR__
    . '/includes/inicio.php';

$paginaActiva = 'resumen';
$tituloPagina = 'Centro de Control del Servidor';
$subtituloPagina =
    'Resumen operativo, mantenimiento y accesos principales.';

require __DIR__
    . '/includes/encabezado.php';

require __DIR__
    . '/includes/navegacion.php';

require __DIR__
    . '/includes/paneles/resumen.php';

require __DIR__
    . '/includes/pie.php';
