<?php

require_once __DIR__
    . '/includes/inicio.php';

$paginaActiva = 'servicios';
$tituloPagina = 'Servicios del servidor';
$subtituloPagina = 'Contenedores Docker, aplicaciones y accesos.';

require __DIR__
    . '/includes/encabezado.php';

require __DIR__
    . '/includes/navegacion.php';

require __DIR__
    . '/includes/paneles/servicios.php';

require __DIR__
    . '/includes/pie.php';
