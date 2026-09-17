<?php

declare(strict_types=1);

function cargarEstadoOperativoDesarrollo(): array
{
    static $estado = null;

    if (is_array($estado)) {
        return $estado;
    }

    $estado = [
        'generado' => null,
        'proyectos' => [],
        'docker' => [
            'disponible' => false,
            'error' => 'Estado operativo todavía no disponible',
            'contenedores' => [],
        ],
    ];

    $ruta = dirname(__DIR__) . '/datos/estado-operativo.json';

    if (!is_file($ruta) || !is_readable($ruta)) {
        return $estado;
    }

    $contenido = file_get_contents($ruta);

    if ($contenido === false) {
        return $estado;
    }

    try {
        $datos = json_decode(
            $contenido,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        return $estado;
    }

    if (!is_array($datos)) {
        return $estado;
    }

    $estado = array_replace_recursive($estado, $datos);

    return $estado;
}

function indexarEstadoOperativoDesarrollo(array $estado): array
{
    $indice = [];

    foreach ($estado['proyectos'] ?? [] as $proyecto) {
        $carpeta = $proyecto['carpeta'] ?? null;

        if (is_string($carpeta) && $carpeta !== '') {
            $indice[$carpeta] = $proyecto;
        }
    }

    return $indice;
}

function cargarBackupsProyectosDesarrollo(): array
{
    static $estado = null;

    if (is_array($estado)) {
        return $estado;
    }

    $estado = [
        'version' => 1,
        'estado' => 'pendiente',
        'fecha_inicio' => null,
        'fecha_final' => null,
        'duracion_segundos' => null,
        'retencion_dias' => null,
        'cantidad_proyectos' => 0,
        'proyectos' => [],
    ];

    /*
     * Montaje de solo lectura:
     * Ruta configurable mediante HOMELAB_STATUS_FILE.
     */
    $ruta = getenv('HOMELAB_STATUS_FILE') ?: '/var/lib/homelab/state/estado-operativo.json';

    if (!is_file($ruta) || !is_readable($ruta)) {
        return $estado;
    }

    $contenido = file_get_contents($ruta);

    if ($contenido === false) {
        return $estado;
    }

    try {
        $datos = json_decode(
            $contenido,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        return $estado;
    }

    if (!is_array($datos)) {
        return $estado;
    }

    return array_replace_recursive($estado, $datos);
}

function indexarBackupsProyectosDesarrollo(array $estado): array
{
    $indice = [];

    foreach ($estado['proyectos'] ?? [] as $proyecto) {
        $carpeta = $proyecto['carpeta'] ?? null;

        if (is_string($carpeta) && $carpeta !== '') {
            $indice[$carpeta] = $proyecto;
        }
    }

    return $indice;
}
