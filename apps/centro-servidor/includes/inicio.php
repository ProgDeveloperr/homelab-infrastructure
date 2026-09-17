<?php

declare(strict_types=1);

date_default_timezone_set(
    'America/Argentina/Buenos_Aires'
);

require_once __DIR__
    . '/estado_general.php';

require_once __DIR__
    . '/estado_smart.php';

require_once __DIR__
    . '/estado_backups.php';

require_once __DIR__
    . '/estado_servicios.php';

require_once __DIR__
    . '/estado_eventos.php';

require_once __DIR__
    . '/estado_red.php';

function formatearBytes(int $bytes): string
{
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $valor = (float) $bytes;
    $indice = 0;

    while (
        $valor >= 1024
        && $indice < count($unidades) - 1
    ) {
        $valor /= 1024;
        $indice++;
    }

    return number_format(
        $valor,
        2,
        ',',
        '.'
    ) . ' ' . $unidades[$indice];
}

$estadoGeneralServidor =
    obtenerEstadoGeneralServidor();

$estadoSmart = obtenerEstadoSmart();

$estadoBackups = obtenerEstadoBackups();

$estadoServicios = obtenerEstadoServicios();

$estadoEventos = obtenerEstadoEventos();

$estadoRed = obtenerEstadoRed();
