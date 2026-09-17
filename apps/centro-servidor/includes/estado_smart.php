<?php

declare(strict_types=1);

if (!defined('RUTA_ESTADO_SMART')) {
    define(
        'RUTA_ESTADO_SMART',
        rtrim(
            getenv('HOMELAB_STATE_DIR') ?: '/var/lib/homelab/state',
            '/'
        ) . '/smart.json'
    );
}
const ANTIGUEDAD_MAXIMA_SMART = 3600;

function obtenerEstadoSmart(): array
{
    $estadoPredeterminado = [
        'disponible' => false,
        'actualizado' => null,
        'actualizado_unix' => 0,
        'antiguedad_segundos' => null,
        'desactualizado' => true,
        'nivel' => 'sin-datos',
        'mensaje' => 'No hay información SMART disponible',
        'smart_general' => 'DESCONOCIDO',
        'temperatura_c' => null,
        'horas_encendido' => null,
        'sectores_reasignados' => null,
        'sectores_pendientes' => null,
        'sectores_no_corregibles' => null,
        'errores_crc' => null,
        'errores_crc_nuevos' => null,
        'command_timeout' => null,
        'command_timeout_nuevos' => null,
        'ultimo_autotest' => 'Sin información',
    ];

    if (
        !is_file(RUTA_ESTADO_SMART)
        || !is_readable(RUTA_ESTADO_SMART)
    ) {
        return $estadoPredeterminado;
    }

    $contenido = file_get_contents(RUTA_ESTADO_SMART);

    if ($contenido === false || trim($contenido) === '') {
        return $estadoPredeterminado;
    }

    try {
        $datos = json_decode(
            $contenido,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $error) {
        $estadoPredeterminado['mensaje'] =
            'El archivo SMART contiene JSON inválido';

        return $estadoPredeterminado;
    }

    if (!is_array($datos)) {
        return $estadoPredeterminado;
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

    $nivelPermitido = in_array(
        $datos['nivel'] ?? '',
        ['normal', 'advertencia', 'critico'],
        true
    )
        ? $datos['nivel']
        : 'sin-datos';

    $desactualizado = $antiguedad === null
        || $antiguedad > ANTIGUEDAD_MAXIMA_SMART;

    if ($desactualizado && $nivelPermitido === 'normal') {
        $nivelPermitido = 'advertencia';
    }

    return [
        'disponible' => true,
        'actualizado' => (string) (
            $datos['actualizado'] ?? ''
        ),
        'actualizado_unix' => $actualizadoUnix,
        'antiguedad_segundos' => $antiguedad,
        'desactualizado' => $desactualizado,
        'nivel' => $nivelPermitido,
        'mensaje' => $desactualizado
            ? 'La información SMART está desactualizada'
            : (string) (
                $datos['mensaje'] ?? 'Estado desconocido'
            ),
        'smart_general' => (string) (
            $datos['smart_general'] ?? 'DESCONOCIDO'
        ),
        'temperatura_c' => obtenerEnteroSmart(
            $datos,
            'temperatura_c'
        ),
        'horas_encendido' => obtenerEnteroSmart(
            $datos,
            'horas_encendido'
        ),
        'sectores_reasignados' => obtenerEnteroSmart(
            $datos,
            'sectores_reasignados'
        ),
        'sectores_pendientes' => obtenerEnteroSmart(
            $datos,
            'sectores_pendientes'
        ),
        'sectores_no_corregibles' => obtenerEnteroSmart(
            $datos,
            'sectores_no_corregibles'
        ),
        'errores_crc' => obtenerEnteroSmart(
            $datos,
            'errores_crc'
        ),
        'errores_crc_nuevos' => obtenerEnteroSmart(
            $datos,
            'errores_crc_nuevos'
        ),
        'command_timeout' => obtenerEnteroSmart(
            $datos,
            'command_timeout'
        ),
        'command_timeout_nuevos' => obtenerEnteroSmart(
            $datos,
            'command_timeout_nuevos'
        ),
        'ultimo_autotest' => (string) (
            $datos['ultimo_autotest'] ?? 'Sin información'
        ),
    ];
}

function obtenerEnteroSmart(array $datos, string $clave): ?int
{
    if (!array_key_exists($clave, $datos)) {
        return null;
    }

    $valor = filter_var(
        $datos[$clave],
        FILTER_VALIDATE_INT
    );

    return $valor !== false ? $valor : null;
}

function formatearHorasSmart(?int $horas): string
{
    if ($horas === null) {
        return 'Sin datos';
    }

    $anios = intdiv($horas, 8760);
    $diasRestantes = intdiv($horas % 8760, 24);

    return number_format($horas, 0, ',', '.')
        . ' h'
        . (
            $anios > 0
                ? " · {$anios} años y {$diasRestantes} días"
                : ''
        );
}

function formatearAntiguedadSmart(?int $segundos): string
{
    if ($segundos === null) {
        return 'Sin actualización';
    }

    if ($segundos < 60) {
        return 'Hace menos de un minuto';
    }

    if ($segundos < 3600) {
        $minutos = intdiv($segundos, 60);

        return "Hace {$minutos} min";
    }

    $horas = intdiv($segundos, 3600);

    return "Hace {$horas} h";
}
