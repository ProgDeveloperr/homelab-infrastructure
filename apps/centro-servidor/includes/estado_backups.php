<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_BACKUPS')) {
    define(
        'RUTA_ESTADO_BACKUPS',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/backups.json'
    );
}

const ANTIGUEDAD_MAXIMA_ESTADO_BACKUPS = 3600;

function obtenerEstadoBackups(): array
{
    $predeterminado = [
        'disponible' => false,
        'desactualizado' => true,
        'actualizado_unix' => 0,
        'antiguedad_segundos' => null,
        'nivel' => 'sin-datos',
        'mensaje' => 'No hay información de backups',
        'configuracion' => [],
        'docker' => [],
    ];

    if (
        !is_file(RUTA_ESTADO_BACKUPS)
        || !is_readable(RUTA_ESTADO_BACKUPS)
    ) {
        return $predeterminado;
    }

    $contenido = file_get_contents(
        RUTA_ESTADO_BACKUPS
    );

    if ($contenido === false || trim($contenido) === '') {
        return $predeterminado;
    }

    try {
        $datos = json_decode(
            $contenido,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $error) {
        $predeterminado['mensaje'] =
            'El estado de backups contiene JSON inválido';

        return $predeterminado;
    }

    if (!is_array($datos)) {
        return $predeterminado;
    }

    $actualizadoUnix = filter_var(
        $datos['actualizado_unix'] ?? 0,
        FILTER_VALIDATE_INT
    );

    $actualizadoUnix = $actualizadoUnix !== false
        ? $actualizadoUnix
        : 0;

    $antiguedad = $actualizadoUnix > 0
        ? max(0, time() - $actualizadoUnix)
        : null;

    $nivel = in_array(
        $datos['nivel'] ?? '',
        ['normal', 'advertencia', 'critico'],
        true
    )
        ? $datos['nivel']
        : 'sin-datos';

    $desactualizado = $antiguedad === null
        || $antiguedad
            > ANTIGUEDAD_MAXIMA_ESTADO_BACKUPS;

    if ($desactualizado && $nivel === 'normal') {
        $nivel = 'advertencia';
    }

    return [
        'disponible' => true,
        'desactualizado' => $desactualizado,
        'actualizado_unix' => $actualizadoUnix,
        'antiguedad_segundos' => $antiguedad,
        'nivel' => $nivel,
        'mensaje' => $desactualizado
            ? 'La información de backups está desactualizada'
            : (string) (
                $datos['mensaje']
                ?? 'Estado desconocido'
            ),
        'configuracion' => is_array(
            $datos['configuracion'] ?? null
        )
            ? $datos['configuracion']
            : [],
        'docker' => is_array(
            $datos['docker'] ?? null
        )
            ? $datos['docker']
            : [],
    ];
}

function formatearFechaBackup(
    int|string|null $timestamp
): string {
    $valor = filter_var(
        $timestamp,
        FILTER_VALIDATE_INT
    );

    if ($valor === false || $valor <= 0) {
        return 'Sin datos';
    }

    return date('d/m/Y H:i:s', $valor);
}

function formatearProximaEjecucionBackup(
    int|string|null $timestamp
): string {
    $valor = filter_var(
        $timestamp,
        FILTER_VALIDATE_INT
    );

    if ($valor === false || $valor <= 0) {
        return 'Sin programación';
    }

    $diferencia = $valor - time();

    if ($diferencia <= 0) {
        return 'Ejecución pendiente';
    }

    $horas = intdiv($diferencia, 3600);
    $minutos = intdiv(
        $diferencia % 3600,
        60
    );

    if ($horas > 0) {
        return "En {$horas} h {$minutos} min";
    }

    return "En {$minutos} min";
}

function obtenerClaseEstadoBackup(
    string $nivel
): string {
    return in_array(
        $nivel,
        ['normal', 'advertencia', 'critico'],
        true
    )
        ? $nivel
        : 'sin-datos';
}
