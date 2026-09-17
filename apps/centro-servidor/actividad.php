<?php

require_once __DIR__
    . '/includes/inicio.php';

$paginaActiva = 'actividad';
$tituloPagina = 'Actividad del servidor';
$subtituloPagina = 'Timers, ejecuciones, eventos y arranques.';

require __DIR__
    . '/includes/encabezado.php';

require __DIR__
    . '/includes/navegacion.php';

require __DIR__
    . '/includes/paneles/actividad.php';

require __DIR__
    . '/includes/pie.php';
