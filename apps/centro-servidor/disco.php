<?php

require_once __DIR__
    . '/includes/inicio.php';

$paginaActiva = 'disco';
$tituloPagina = 'Salud y almacenamiento';
$subtituloPagina = 'Estado SMART, temperatura, errores y prevención.';

require __DIR__
    . '/includes/encabezado.php';

require __DIR__
    . '/includes/navegacion.php';

require __DIR__
    . '/includes/paneles/salud_disco.php';

require __DIR__
    . '/includes/pie.php';
