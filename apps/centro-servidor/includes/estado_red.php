<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_RED')) {
    define(
        'RUTA_ESTADO_RED',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/red.json'
    );
}

const ANTIGUEDAD_MAXIMA_ESTADO_RED = 900;

function obtenerEstadoRed(): array
{
    $predeterminado = [
        'disponible' => false,
        'desactualizado' => true,
        'actualizado_unix' => 0,
        'antiguedad_segundos' => null,
        'nivel' => 'sin-datos',
        'mensaje' => 'No hay información de red',
        'gestor' => [],
        'interfaz' => [],
        'wifi' => [],
        'lan' => [],
        'internet' => [],
        'dns' => [],
        'puertos' => [
            'total' => 0,
            'abiertos' => 0,
            'cerrados' => 0,
            'elementos' => [],
        ],
    ];

    if (
        !is_file(RUTA_ESTADO_RED)
        || !is_readable(RUTA_ESTADO_RED)
    ) {
        return $predeterminado;
    }

    $contenido = file_get_contents(
        RUTA_ESTADO_RED
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
            'El estado de red contiene JSON inválido';

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
            > ANTIGUEDAD_MAXIMA_ESTADO_RED;

    if ($desactualizado && $nivel === 'normal') {
        $nivel = 'advertencia';
    }

    $puertos = is_array($datos['puertos'] ?? null)
        ? $datos['puertos']
        : [];

    return [
        'disponible' => true,
        'desactualizado' => $desactualizado,
        'actualizado_unix' => $actualizadoUnix,
        'antiguedad_segundos' => $antiguedad,
        'nivel' => $nivel,
        'mensaje' => $desactualizado
            ? 'La información de red está desactualizada'
            : (string) (
                $datos['mensaje']
                ?? 'Estado desconocido'
            ),
        'gestor' => is_array($datos['gestor'] ?? null)
            ? $datos['gestor']
            : [],
        'interfaz' => is_array($datos['interfaz'] ?? null)
            ? $datos['interfaz']
            : [],
        'wifi' => is_array($datos['wifi'] ?? null)
            ? $datos['wifi']
            : [],
        'lan' => is_array($datos['lan'] ?? null)
            ? $datos['lan']
            : [],
        'internet' => is_array($datos['internet'] ?? null)
            ? $datos['internet']
            : [],
        'dns' => is_array($datos['dns'] ?? null)
            ? $datos['dns']
            : [],
        'puertos' => [
            'total' => (int) ($puertos['total'] ?? 0),
            'abiertos' => (int) ($puertos['abiertos'] ?? 0),
            'cerrados' => (int) ($puertos['cerrados'] ?? 0),
            'elementos' => array_values(
                array_filter(
                    is_array($puertos['elementos'] ?? null)
                        ? $puertos['elementos']
                        : [],
                    static fn ($puerto): bool =>
                        is_array($puerto)
                )
            ),
        ],
    ];
}

function obtenerClaseEstadoRed(
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

function formatearLatenciaRed(
    float|int|string|null $latencia
): string {
    if (!is_numeric($latencia)) {
        return 'Sin datos';
    }

    return number_format(
        (float) $latencia,
        2,
        ',',
        '.'
    ) . ' ms';
}

function formatearPerdidaRed(
    int|string|null $perdida
): string {
    $valor = filter_var(
        $perdida,
        FILTER_VALIDATE_INT
    );

    if ($valor === false || $valor < 0) {
        return 'Sin datos';
    }

    return $valor . ' %';
}

function formatearEstadoBooleanoRed(
    bool $estado
): string {
    return $estado
        ? 'Disponible'
        : 'No disponible';
}
