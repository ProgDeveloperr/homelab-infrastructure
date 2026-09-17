<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_NETWORK_GUARDIAN')) {
    define(
        'RUTA_ESTADO_NETWORK_GUARDIAN',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/network-guardian.json'
    );
}

const ANTIGUEDAD_MAXIMA_NETWORK_GUARDIAN = 120;

function obtenerEstadoNetworkGuardian(): array
{
    $base = [
        'disponible' => false,
        'desactualizado' => true,
        'antiguedad_segundos' => null,
        'nivel' => 'sin-datos',
        'mensaje' => 'Sin datos de Network Guardian',
        'guardian' => [],
        'uplink' => [],
        'comprobacion' => [],
        'recuperaciones' => [],
    ];

    if (!is_readable(RUTA_ESTADO_NETWORK_GUARDIAN)) {
        return $base;
    }

    $contenido = file_get_contents(
        RUTA_ESTADO_NETWORK_GUARDIAN
    );

    if ($contenido === false || trim($contenido) === '') {
        return $base;
    }

    try {
        $datos = json_decode(
            $contenido,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $error) {
        $base['mensaje'] = 'JSON de Guardian inválido';
        return $base;
    }

    if (!is_array($datos)) {
        return $base;
    }

    $mtime = filemtime(RUTA_ESTADO_NETWORK_GUARDIAN);
    $antiguedad = $mtime === false
        ? null
        : max(0, time() - $mtime);

    $desactualizado = $antiguedad === null
        || $antiguedad > ANTIGUEDAD_MAXIMA_NETWORK_GUARDIAN;

    $nivel = (string) ($datos['nivel'] ?? 'sin-datos');

    if ($desactualizado && $nivel === 'normal') {
        $nivel = 'advertencia';
    }

    return [
        'disponible' => true,
        'desactualizado' => $desactualizado,
        'antiguedad_segundos' => $antiguedad,
        'nivel' => $nivel,
        'mensaje' => $desactualizado
            ? 'Información de Network Guardian desactualizada'
            : (string) ($datos['mensaje'] ?? 'Sin datos'),
        'guardian' => is_array($datos['guardian'] ?? null)
            ? $datos['guardian'] : [],
        'uplink' => is_array($datos['uplink'] ?? null)
            ? $datos['uplink'] : [],
        'comprobacion' => is_array($datos['comprobacion'] ?? null)
            ? $datos['comprobacion'] : [],
        'recuperaciones' => is_array($datos['recuperaciones'] ?? null)
            ? $datos['recuperaciones'] : [],
    ];
}
