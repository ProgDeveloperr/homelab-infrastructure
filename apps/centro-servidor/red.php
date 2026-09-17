<?php

require_once __DIR__
    . '/includes/inicio.php';

require_once __DIR__
    . '/includes/estado_network_guardian.php';

$estadoNetworkGuardian =
    obtenerEstadoNetworkGuardian();

$paginaActiva = 'red';
$tituloPagina = 'Red y conectividad';
$subtituloPagina =
    'Interfaz, gateway, DNS, Internet y servicios de red.';

require __DIR__
    . '/includes/encabezado.php';

require __DIR__
    . '/includes/navegacion.php';

require __DIR__
    . '/includes/paneles/red.php';

require __DIR__
    . '/includes/pie.php';
