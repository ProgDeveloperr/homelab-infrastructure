<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_EVENTOS')) {
    define(
        'RUTA_ESTADO_EVENTOS',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/eventos.json'
    );
}

const ANTIGUEDAD_MAXIMA_ESTADO_EVENTOS = 900;

function obtenerEstadoEventos(): array
{
    $predeterminado = [
        'disponible' => false,
        'desactualizado' => true,
        'actualizado_unix' => 0,
        'antiguedad_segundos' => null,
        'nivel' => 'sin-datos',
        'mensaje' => 'No hay información de actividad',
        'resumen' => [
            'timers_total' => 0,
            'timers_activos' => 0,
            'timers_fallidos' => 0,
            'servicios_fallidos' => 0,
        ],
        'arranque' => [],
        'eventos_recientes' => [],
        'timers' => [],
    ];

    if (
        !is_file(RUTA_ESTADO_EVENTOS)
        || !is_readable(RUTA_ESTADO_EVENTOS)
    ) {
        return $predeterminado;
    }

    $contenido = file_get_contents(
        RUTA_ESTADO_EVENTOS
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
            'El estado de actividad contiene JSON inválido';

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
            > ANTIGUEDAD_MAXIMA_ESTADO_EVENTOS;

    if ($desactualizado && $nivel === 'normal') {
        $nivel = 'advertencia';
    }

    $resumen = is_array($datos['resumen'] ?? null)
        ? $datos['resumen']
        : [];

    $arranque = is_array($datos['arranque'] ?? null)
        ? $datos['arranque']
        : [];

    $eventosRecientes = is_array(
        $datos['eventos_recientes'] ?? null
    )
        ? $datos['eventos_recientes']
        : [];

    $timers = is_array($datos['timers'] ?? null)
        ? $datos['timers']
        : [];

    return [
        'disponible' => true,
        'desactualizado' => $desactualizado,
        'actualizado_unix' => $actualizadoUnix,
        'antiguedad_segundos' => $antiguedad,
        'nivel' => $nivel,
        'mensaje' => $desactualizado
            ? 'La información de actividad está desactualizada'
            : (string) (
                $datos['mensaje']
                ?? 'Estado desconocido'
            ),
        'resumen' => [
            'timers_total' => (int) (
                $resumen['timers_total'] ?? 0
            ),
            'timers_activos' => (int) (
                $resumen['timers_activos'] ?? 0
            ),
            'timers_fallidos' => (int) (
                $resumen['timers_fallidos'] ?? 0
            ),
            'servicios_fallidos' => (int) (
                $resumen['servicios_fallidos'] ?? 0
            ),
        ],
        'arranque' => $arranque,
        'eventos_recientes' => $eventosRecientes,
        'timers' => array_values(
            array_filter(
                $timers,
                static fn ($timer): bool =>
                    is_array($timer)
            )
        ),
    ];
}

function obtenerClaseEstadoEvento(
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

function formatearCategoriaTimer(
    string $categoria
): string {
    return match ($categoria) {
        'backups' => 'Backups',
        'disco' => 'Disco',
        'servidor' => 'Servidor',
        'camaras' => 'Cámaras',
        default => 'Sistema',
    };
}

function formatearMomentoTimer(
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

function formatearCuentaRegresivaTimer(
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

function formatearResultadoTimer(
    string $resultado
): string {
    return match ($resultado) {
        'success' => 'Correcto',
        'exit-code' => 'Código de error',
        'timeout' => 'Tiempo agotado',
        'signal' => 'Finalizado por señal',
        'core-dump' => 'Error grave',
        'unset', '' => 'Sin ejecución',
        default => ucfirst($resultado),
    };
}
