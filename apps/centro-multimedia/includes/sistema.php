<?php

declare(strict_types=1);

require_once __DIR__
    . '/aplicacion.php';


const CMM_SYSTEM_SCHEMA =
    'cmm.system.v1';

if (!defined('CMM_SYSTEM_SOURCE')) {
    define(
        'CMM_SYSTEM_SOURCE',
        rtrim(
            getenv('CMM_STATE_DIR') ?: '/var/lib/cmm/state',
            '/'
        ) . '/system-v1.json'
    );
}


function cmm_system_exact_keys(
    array $value,
    array $expected
): bool {
    $actual = array_keys($value);

    sort($actual);
    sort($expected);

    return $actual === $expected;
}


function cmm_system_string(
    mixed $value,
    int $maxLength = 512
): bool {
    return
        is_string($value)
        &&
        $value !== ''
        &&
        strlen($value) <= $maxLength;
}


function cmm_system_non_negative_int(
    mixed $value
): bool {
    return
        is_int($value)
        &&
        $value >= 0;
}


function cmm_system_number(
    mixed $value
): bool {
    return
        (
            is_int($value)
            ||
            is_float($value)
        )
        &&
        is_finite((float) $value);
}


function cmm_system_validate(
    array $data
): void {
    if (
        !cmm_system_exact_keys(
            $data,
            [
                'schema',
                'host',
                'cpu',
                'memory',
                'cmm',
                'snapshot',
            ]
        )
        ||
        ($data['schema'] ?? null)
        !== CMM_SYSTEM_SCHEMA
    ) {
        throw new RuntimeException(
            'Invalid system top-level contract'
        );
    }


    $host = $data['host'];

    if (
        !is_array($host)
        ||
        !cmm_system_exact_keys(
            $host,
            [
                'operating_system',
                'kernel_version',
                'architecture',
                'uptime_seconds',
            ]
        )
        ||
        !cmm_system_string(
            $host['operating_system'] ?? null
        )
        ||
        !cmm_system_string(
            $host['kernel_version'] ?? null,
            128
        )
        ||
        !cmm_system_string(
            $host['architecture'] ?? null,
            64
        )
        ||
        !cmm_system_non_negative_int(
            $host['uptime_seconds'] ?? null
        )
    ) {
        throw new RuntimeException(
            'Invalid system host contract'
        );
    }


    $cpu = $data['cpu'];

    if (
        !is_array($cpu)
        ||
        !cmm_system_exact_keys(
            $cpu,
            [
                'model',
                'logical_processors',
                'load_1',
                'load_5',
                'load_15',
            ]
        )
        ||
        !cmm_system_string(
            $cpu['model'] ?? null
        )
        ||
        !is_int(
            $cpu['logical_processors'] ?? null
        )
        ||
        $cpu['logical_processors'] < 1
    ) {
        throw new RuntimeException(
            'Invalid system CPU contract'
        );
    }


    foreach (
        [
            'load_1',
            'load_5',
            'load_15',
        ]
        as $key
    ) {
        if (
            !cmm_system_number(
                $cpu[$key] ?? null
            )
            ||
            (float) $cpu[$key] < 0
        ) {
            throw new RuntimeException(
                'Invalid system load contract'
            );
        }
    }


    $memory = $data['memory'];

    if (
        !is_array($memory)
        ||
        !cmm_system_exact_keys(
            $memory,
            [
                'total_bytes',
                'used_bytes',
                'available_bytes',
                'used_percent',
            ]
        )
        ||
        !is_int(
            $memory['total_bytes'] ?? null
        )
        ||
        $memory['total_bytes'] <= 0
        ||
        !cmm_system_non_negative_int(
            $memory['used_bytes'] ?? null
        )
        ||
        !cmm_system_non_negative_int(
            $memory['available_bytes'] ?? null
        )
        ||
        !cmm_system_number(
            $memory['used_percent'] ?? null
        )
    ) {
        throw new RuntimeException(
            'Invalid system memory contract'
        );
    }


    if (
        $memory['used_bytes']
        +
        $memory['available_bytes']
        !==
        $memory['total_bytes']
    ) {
        throw new RuntimeException(
            'Invalid system memory invariant'
        );
    }


    $expectedPercent = round(
        (
            $memory['used_bytes']
            *
            100
        )
        /
        $memory['total_bytes'],
        2
    );


    if (
        abs(
            (float) $memory['used_percent']
            -
            $expectedPercent
        )
        > 0.01
    ) {
        throw new RuntimeException(
            'Invalid system memory percentage'
        );
    }


    $cmm = $data['cmm'];

    if (
        !is_array($cmm)
        ||
        !cmm_system_exact_keys(
            $cmm,
            [
                'broker',
                'refresh',
                'providers',
            ]
        )
    ) {
        throw new RuntimeException(
            'Invalid CMM runtime contract'
        );
    }


    $broker = $cmm['broker'];

    if (
        !is_array($broker)
        ||
        !cmm_system_exact_keys(
            $broker,
            [
                'state',
                'version',
                'mode',
            ]
        )
        ||
        !in_array(
            $broker['state'] ?? null,
            [
                'OK',
                'ERROR',
                'UNKNOWN',
            ],
            true
        )
        ||
        !cmm_system_string(
            $broker['version'] ?? null,
            64
        )
        ||
        !cmm_system_string(
            $broker['mode'] ?? null,
            64
        )
    ) {
        throw new RuntimeException(
            'Invalid broker contract'
        );
    }


    if (
        $broker['state'] === 'OK'
        &&
        $broker['mode'] !== 'GET_ONLY'
    ) {
        throw new RuntimeException(
            'Invalid broker healthy mode'
        );
    }


    $refresh = $cmm['refresh'];

    if (
        !is_array($refresh)
        ||
        !cmm_system_exact_keys(
            $refresh,
            [
                'timer_state',
                'last_result',
            ]
        )
        ||
        !in_array(
            $refresh['timer_state'] ?? null,
            [
                'ACTIVE',
                'INACTIVE',
                'UNKNOWN',
            ],
            true
        )
        ||
        !in_array(
            $refresh['last_result'] ?? null,
            [
                'SUCCESS',
                'ERROR',
                'UNKNOWN',
            ],
            true
        )
    ) {
        throw new RuntimeException(
            'Invalid refresh contract'
        );
    }


    $providers = $cmm['providers'];

    if (
        !is_array($providers)
        ||
        !cmm_system_exact_keys(
            $providers,
            [
                'total',
                'success',
                'error',
                'unknown',
                'items',
            ]
        )
    ) {
        throw new RuntimeException(
            'Invalid providers contract'
        );
    }


    foreach (
        [
            'total',
            'success',
            'error',
            'unknown',
        ]
        as $key
    ) {
        if (
            !cmm_system_non_negative_int(
                $providers[$key] ?? null
            )
        ) {
            throw new RuntimeException(
                'Invalid provider counters'
            );
        }
    }


    if (
        $providers['total'] !== 6
        ||
        $providers['success']
        +
        $providers['error']
        +
        $providers['unknown']
        !==
        $providers['total']
    ) {
        throw new RuntimeException(
            'Invalid provider count invariant'
        );
    }


    $items = $providers['items'];

    if (
        !is_array($items)
        ||
        !array_is_list($items)
        ||
        count($items) !== 6
    ) {
        throw new RuntimeException(
            'Invalid provider item list'
        );
    }


    $fixedNames = [
        'storage',
        'library',
        'posters',
        'requests',
        'downloads',
        'activity',
    ];

    $names = [];
    $success = 0;
    $error = 0;
    $unknown = 0;


    foreach ($items as $item) {
        if (
            !is_array($item)
            ||
            !cmm_system_exact_keys(
                $item,
                [
                    'name',
                    'result',
                ]
            )
            ||
            !in_array(
                $item['name'] ?? null,
                $fixedNames,
                true
            )
            ||
            !in_array(
                $item['result'] ?? null,
                [
                    'SUCCESS',
                    'ERROR',
                    'UNKNOWN',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid provider item'
            );
        }


        if (
            in_array(
                $item['name'],
                $names,
                true
            )
        ) {
            throw new RuntimeException(
                'Duplicate provider name'
            );
        }


        $names[] = $item['name'];


        switch ($item['result']) {
            case 'SUCCESS':
                $success++;
                break;

            case 'ERROR':
                $error++;
                break;

            default:
                $unknown++;
                break;
        }
    }


    $sortedNames = $names;
    $sortedFixed = $fixedNames;

    sort($sortedNames);
    sort($sortedFixed);


    if ($sortedNames !== $sortedFixed) {
        throw new RuntimeException(
            'Invalid provider names'
        );
    }


    if (
        $success !== $providers['success']
        ||
        $error !== $providers['error']
        ||
        $unknown !== $providers['unknown']
    ) {
        throw new RuntimeException(
            'Invalid provider result counters'
        );
    }


    $snapshot = $data['snapshot'];

    if (
        !is_array($snapshot)
        ||
        !cmm_system_exact_keys(
            $snapshot,
            [
                'captured_at_utc',
            ]
        )
        ||
        !cmm_system_string(
            $snapshot['captured_at_utc'] ?? null,
            64
        )
        ||
        preg_match(
            '/^\d{4}-\d{2}-\d{2}T'
            . '\d{2}:\d{2}:\d{2}Z$/',
            $snapshot['captured_at_utc']
        )
        !== 1
    ) {
        throw new RuntimeException(
            'Invalid system snapshot contract'
        );
    }


    try {
        new DateTimeImmutable(
            $snapshot['captured_at_utc']
        );
    } catch (Throwable $exception) {
        throw new RuntimeException(
            'Invalid system snapshot timestamp',
            0,
            $exception
        );
    }
}


function cmm_system_read(): array
{
    $path = CMM_SYSTEM_SOURCE;


    if (
        !is_file($path)
        ||
        !is_readable($path)
    ) {
        throw new RuntimeException(
            'System projection unavailable'
        );
    }


    $size = filesize($path);

    if (
        $size === false
        ||
        $size < 2
        ||
        $size > 262144
    ) {
        throw new RuntimeException(
            'System projection size invalid'
        );
    }


    $raw = file_get_contents($path);

    if (
        $raw === false
        ||
        strlen($raw) !== $size
    ) {
        throw new RuntimeException(
            'System projection read failed'
        );
    }


    $data = json_decode(
        $raw,
        true,
        512,
        JSON_THROW_ON_ERROR
    );


    if (!is_array($data)) {
        throw new RuntimeException(
            'System projection root invalid'
        );
    }


    cmm_system_validate($data);


    return $data;
}
