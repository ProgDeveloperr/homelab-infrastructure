<?php

declare(strict_types=1);

const CMM_API_VERSION = '1.0.0';

const CMM_STORAGE_SCHEMA = 'cmm.storage.v1';

if (!defined('CMM_STORAGE_SOURCE')) {
    define(
        'CMM_STORAGE_SOURCE',
        rtrim(
            getenv('CMM_STATE_DIR') ?: '/var/lib/cmm/state',
            '/'
        ) . '/storage-v1.json'
    );
}


function cmm_json_error(
    int $status,
    string $code
): never {
    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header(
        'X-Content-Type-Options: nosniff'
    );

    header(
        'Cache-Control: no-store, max-age=0'
    );

    echo json_encode(
        [
            'ok' => false,
            'error' => $code,
        ],
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    exit;
}


function cmm_storage_read(): array
{
    $path = CMM_STORAGE_SOURCE;

    if (
        !is_file($path)
        || !is_readable($path)
    ) {
        cmm_json_error(
            503,
            'storage_unavailable'
        );
    }

    $size = filesize($path);

    if (
        $size === false
        || $size < 2
        || $size > 65536
    ) {
        cmm_json_error(
            503,
            'storage_invalid'
        );
    }

    $raw = file_get_contents($path);

    if ($raw === false) {
        cmm_json_error(
            503,
            'storage_unavailable'
        );
    }

    try {
        $data = json_decode(
            $raw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        cmm_json_error(
            503,
            'storage_invalid'
        );
    }

    if (!is_array($data)) {
        cmm_json_error(
            503,
            'storage_invalid'
        );
    }

    $requiredTop = [
        'schema',
        'snapshot',
        'capacity',
        'logical',
        'physical',
        'hardlinks',
        'references',
    ];

    $actualTop = array_keys($data);

    sort($requiredTop);
    sort($actualTop);

    if ($actualTop !== $requiredTop) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    if (
        ($data['schema'] ?? null)
        !== CMM_STORAGE_SCHEMA
    ) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    foreach (
        [
            'snapshot',
            'capacity',
            'logical',
            'physical',
            'hardlinks',
            'references',
        ]
        as $section
    ) {
        if (!is_array($data[$section])) {
            cmm_json_error(
                503,
                'storage_contract_invalid'
            );
        }
    }

    $snapshotId =
        $data['snapshot']['id']
        ?? null;

    $capturedAt =
        $data['snapshot']['captured_at_utc']
        ?? null;

    if (
        !is_int($snapshotId)
        || $snapshotId < 1
        || !is_string($capturedAt)
        || $capturedAt === ''
    ) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    $integerPaths = [
        ['capacity', 'total_bytes'],
        ['capacity', 'used_bytes'],
        ['capacity', 'free_bytes'],
        ['capacity', 'reserved_or_unavailable_bytes'],

        ['logical', 'movies_bytes'],
        ['logical', 'series_bytes'],
        ['logical', 'library_bytes'],
        ['logical', 'torrents_bytes'],
        ['logical', 'all_references_bytes'],

        ['physical', 'unique_objects'],
        ['physical', 'unique_logical_bytes'],
        ['physical', 'unique_allocated_bytes'],

        ['hardlinks', 'shared_logical_bytes'],
        ['hardlinks', 'cross_scope_groups'],

        ['references', 'total'],
        ['references', 'library'],
        ['references', 'torrent'],
    ];

    foreach ($integerPaths as [$section, $key]) {

        $value =
            $data[$section][$key]
            ?? null;

        if (
            !is_int($value)
            || $value < 0
        ) {
            cmm_json_error(
                503,
                'storage_contract_invalid'
            );
        }
    }

    if (
        $data['logical']['library_bytes']
        !==
        $data['logical']['movies_bytes']
        +
        $data['logical']['series_bytes']
    ) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    if (
        $data['logical']['all_references_bytes']
        !==
        $data['logical']['library_bytes']
        +
        $data['logical']['torrents_bytes']
    ) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    if (
        $data['references']['total']
        !==
        $data['references']['library']
        +
        $data['references']['torrent']
    ) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    try {
        $captured =
            new DateTimeImmutable($capturedAt);

        $now =
            new DateTimeImmutable(
                'now',
                new DateTimeZone('UTC')
            );

        $age = max(
            0,
            $now->getTimestamp()
            -
            $captured->getTimestamp()
        );

    } catch (Throwable) {
        cmm_json_error(
            503,
            'storage_contract_invalid'
        );
    }

    return [
        'raw' => $raw,
        'data' => $data,
        'age_seconds' => $age,
    ];
}
