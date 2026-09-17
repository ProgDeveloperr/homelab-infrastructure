<?php

declare(strict_types=1);


/*
 * CMM Activity read-only projection include.
 *
 * Public contract:
 *   cmm.activity.v1
 *
 * This include performs no writes.
 */


if (PHP_SAPI !== 'cli') {

    $scriptFilename =
        $_SERVER['SCRIPT_FILENAME']
        ?? '';

    if (
        $scriptFilename !== ''
        && realpath($scriptFilename) === __FILE__
    ) {
        http_response_code(404);
        exit;
    }
}


function cmmActividadLeerJson(): string
{
    $stateDir = rtrim(
        getenv('CMM_STATE_DIR') ?: '/var/lib/cmm/state',
        '/'
    );
    
    $path = $stateDir . '/activity-v1.json';


    if (
        !is_file($path)
        || !is_readable($path)
    ) {
        throw new RuntimeException(
            'Activity projection unavailable.'
        );
    }


    $raw =
        file_get_contents($path);


    if ($raw === false) {
        throw new RuntimeException(
            'Activity projection unavailable.'
        );
    }


    $decoded =
        json_decode(
            $raw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );


    if (
        !is_array($decoded)
        || (
            $decoded['schema']
            ?? null
        ) !== 'cmm.activity.v1'
    ) {
        throw new RuntimeException(
            'Activity projection invalid.'
        );
    }


    if (
        !isset(
            $decoded['summary'],
            $decoded['items']
        )
        || !is_array(
            $decoded['summary']
        )
        || !is_array(
            $decoded['items']
        )
    ) {
        throw new RuntimeException(
            'Activity projection invalid.'
        );
    }


    return $raw;
}
