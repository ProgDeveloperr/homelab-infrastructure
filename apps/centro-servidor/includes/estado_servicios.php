<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_SERVICIOS')) {
    define(
        'RUTA_ESTADO_SERVICIOS',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/servicios.json'
    );
}

const ANTIGUEDAD_MAXIMA_ESTADO_SERVICIOS = 900;

function obtenerEstadoServicios(): array
{
    $predeterminado = [
        'disponible' => false,
        'desactualizado' => true,
        'actualizado_unix' => 0,
        'antiguedad_segundos' => null,
        'nivel' => 'sin-datos',
        'mensaje' => 'No hay información de servicios',
        'resumen' => [
            'total' => 0,
            'activos' => 0,
            'detenidos' => 0,
            'healthy' => 0,
            'unhealthy' => 0,
            'sin_healthcheck' => 0,
        ],
        'servicios' => [],
    ];

    if (
        !is_file(RUTA_ESTADO_SERVICIOS)
        || !is_readable(RUTA_ESTADO_SERVICIOS)
    ) {
        return $predeterminado;
    }

    $contenido = file_get_contents(
        RUTA_ESTADO_SERVICIOS
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
            'El estado de servicios contiene JSON inválido';

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
            > ANTIGUEDAD_MAXIMA_ESTADO_SERVICIOS;

    if ($desactualizado && $nivel === 'normal') {
        $nivel = 'advertencia';
    }

    $resumen = is_array($datos['resumen'] ?? null)
        ? $datos['resumen']
        : [];

    $servicios = is_array($datos['servicios'] ?? null)
        ? $datos['servicios']
        : [];

    return [
        'disponible' => true,
        'desactualizado' => $desactualizado,
        'actualizado_unix' => $actualizadoUnix,
        'antiguedad_segundos' => $antiguedad,
        'nivel' => $nivel,
        'mensaje' => $desactualizado
            ? 'La información de servicios está desactualizada'
            : (string) (
                $datos['mensaje']
                ?? 'Estado desconocido'
            ),
        'resumen' => [
            'total' => (int) ($resumen['total'] ?? 0),
            'activos' => (int) ($resumen['activos'] ?? 0),
            'detenidos' => (int) ($resumen['detenidos'] ?? 0),
            'healthy' => (int) ($resumen['healthy'] ?? 0),
            'unhealthy' => (int) ($resumen['unhealthy'] ?? 0),
            'sin_healthcheck' => (int) (
                $resumen['sin_healthcheck'] ?? 0
            ),
        ],
        'servicios' => array_values(
            array_filter(
                $servicios,
                static fn ($servicio): bool =>
                    is_array($servicio)
            )
        ),
    ];
}

function obtenerClaseEstadoServicio(
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

function formatearUptimeServicio(
    int|string|null $segundos
): string {
    $valor = filter_var(
        $segundos,
        FILTER_VALIDATE_INT
    );

    if ($valor === false || $valor < 0) {
        return 'Sin datos';
    }

    $dias = intdiv($valor, 86400);
    $horas = intdiv($valor % 86400, 3600);
    $minutos = intdiv($valor % 3600, 60);

    if ($dias > 0) {
        return "{$dias} d {$horas} h";
    }

    if ($horas > 0) {
        return "{$horas} h {$minutos} min";
    }

    return "{$minutos} min";
}

function formatearSaludServicio(
    string $salud
): string {
    return match ($salud) {
        'healthy' => 'Healthy',
        'unhealthy' => 'Unhealthy',
        'starting' => 'Iniciando',
        'sin-healthcheck' => 'Sin healthcheck',
        default => 'Sin datos',
    };
}

function formatearEstadoContenedor(
    string $estado
): string {
    return match ($estado) {
        'running' => 'Activo',
        'exited' => 'Detenido',
        'paused' => 'Pausado',
        'restarting' => 'Reiniciando',
        'created' => 'Creado',
        'dead' => 'Error',
        default => ucfirst($estado),
    };
}

