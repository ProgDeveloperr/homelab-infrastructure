<?php

declare(strict_types=1);

date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once __DIR__ . '/detector_proyectos.php';

if (!defined('RUTA_PROYECTOS')) {
    define(
        'RUTA_PROYECTOS',
        getenv('HOMELAB_PROJECTS_PATH') ?: '/var/www/html'
    );
}

$proyectos = obtenerProyectos(RUTA_PROYECTOS);

function escapar(string $texto): string
{
    return htmlspecialchars(
        $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatearBytesDesarrollo(int $bytes): string
{
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $valor = (float) $bytes;
    $indice = 0;

    while ($valor >= 1024 && $indice < count($unidades) - 1) {
        $valor /= 1024;
        $indice++;
    }

    $decimales = $indice === 0 ? 0 : 1;

    return number_format(
        $valor,
        $decimales,
        ',',
        '.'
    ) . ' ' . $unidades[$indice];
}

function formatearFechaProyecto(int $marcaTiempo): string
{
    if ($marcaTiempo <= 0) {
        return 'Sin información';
    }

    return date('d/m/Y H:i', $marcaTiempo);
}
