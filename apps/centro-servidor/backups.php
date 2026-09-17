<?php

require_once __DIR__
    . '/includes/inicio.php';

$paginaActiva = 'backups';
$tituloPagina = 'Backups del servidor';
$subtituloPagina = 'Copias automáticas, integridad y recuperación.';

require __DIR__
    . '/includes/encabezado.php';

require __DIR__
    . '/includes/navegacion.php';

require __DIR__
    . '/includes/paneles/backups.php';

require __DIR__
    . '/includes/pie.php';
