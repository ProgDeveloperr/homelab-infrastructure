<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_GENERAL')) {
    define(
        'RUTA_ESTADO_GENERAL',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/servidor.json'
    );
}

const ANTIGUEDAD_MAXIMA_ESTADO_GENERAL = 900;

function obtenerEstadoGeneralServidor(): array
{
    $predeterminado = [
        'disponible' => false,
        'desactualizado' => true,
        'actualizado_unix' => 0,
        'antiguedad_segundos' => null,
        'nivel' => 'sin-datos',
        'mensaje' => 'No hay información general del servidor',
        'cpu' => [],
        'memoria' => [],
        'swap' => [],
        'disco' => [],
        'sistema' => [],
        'docker' => [],
    ];

    if (
        !is_file(RUTA_ESTADO_GENERAL)
        || !is_readable(RUTA_ESTADO_GENERAL)
    ) {
        return $predeterminado;
    }

    $contenido = file_get_contents(RUTA_ESTADO_GENERAL);

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
            'El estado general contiene JSON inválido';

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
        || $antiguedad > ANTIGUEDAD_MAXIMA_ESTADO_GENERAL;

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
            ? 'La información general está desactualizada'
            : (string) (
                $datos['mensaje']
                ?? 'Estado desconocido'
            ),
        'cpu' => is_array($datos['cpu'] ?? null)
            ? $datos['cpu']
            : [],
        'memoria' => is_array($datos['memoria'] ?? null)
            ? $datos['memoria']
            : [],
        'swap' => is_array($datos['swap'] ?? null)
            ? $datos['swap']
            : [],
        'disco' => is_array($datos['disco'] ?? null)
            ? $datos['disco']
            : [],
        'sistema' => is_array($datos['sistema'] ?? null)
            ? $datos['sistema']
            : [],
        'docker' => is_array($datos['docker'] ?? null)
            ? $datos['docker']
            : [],
    ];
}

function obtenerNumeroEstado(
    array $seccion,
    string $clave,
    int|float|null $predeterminado = null
): int|float|null {
    $valor = $seccion[$clave] ?? null;

    return is_int($valor) || is_float($valor)
        ? $valor
        : $predeterminado;
}

function formatearDuracionServidor(?int $segundos): string
{
    if ($segundos === null || $segundos < 0) {
        return 'Sin datos';
    }

    $dias = intdiv($segundos, 86400);
    $horas = intdiv($segundos % 86400, 3600);
    $minutos = intdiv($segundos % 3600, 60);

    if ($dias > 0) {
        return "{$dias} d {$horas} h";
    }

    if ($horas > 0) {
        return "{$horas} h {$minutos} min";
    }

    return "{$minutos} min";
}
