<?php

declare(strict_types=1);

require_once __DIR__
    . '/../includes/sistema.php';


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !== 'GET'
) {
    header('Allow: GET');

    cmm_json_error(
        405,
        'method_not_allowed'
    );
}


try {
    $data = cmm_system_read();


    $public = [
        'schema' =>
            $data['schema'],

        'host' => [
            'operating_system' =>
                $data['host']['operating_system'],

            'kernel_version' =>
                $data['host']['kernel_version'],

            'architecture' =>
                $data['host']['architecture'],

            'uptime_seconds' =>
                $data['host']['uptime_seconds'],
        ],

        'cpu' => [
            'model' =>
                $data['cpu']['model'],

            'logical_processors' =>
                $data['cpu']['logical_processors'],

            'load_1' =>
                $data['cpu']['load_1'],

            'load_5' =>
                $data['cpu']['load_5'],

            'load_15' =>
                $data['cpu']['load_15'],
        ],

        'memory' => [
            'total_bytes' =>
                $data['memory']['total_bytes'],

            'used_bytes' =>
                $data['memory']['used_bytes'],

            'available_bytes' =>
                $data['memory']['available_bytes'],

            'used_percent' =>
                $data['memory']['used_percent'],
        ],

        'cmm' => [
            'broker' => [
                'state' =>
                    $data['cmm']['broker']['state'],

                'version' =>
                    $data['cmm']['broker']['version'],

                'mode' =>
                    $data['cmm']['broker']['mode'],
            ],

            'refresh' => [
                'timer_state' =>
                    $data['cmm']['refresh']['timer_state'],

                'last_result' =>
                    $data['cmm']['refresh']['last_result'],
            ],

            'providers' => [
                'total' =>
                    $data['cmm']['providers']['total'],

                'success' =>
                    $data['cmm']['providers']['success'],

                'error' =>
                    $data['cmm']['providers']['error'],

                'unknown' =>
                    $data['cmm']['providers']['unknown'],

                'items' =>
                    array_map(
                        static fn (
                            array $item
                        ): array => [
                            'name' =>
                                $item['name'],

                            'result' =>
                                $item['result'],
                        ],
                        $data['cmm']['providers']['items']
                    ),
            ],
        ],

        'snapshot' => [
            'captured_at_utc' =>
                $data['snapshot']['captured_at_utc'],
        ],
    ];


    $raw = json_encode(
        $public,
        JSON_UNESCAPED_SLASHES
        |
        JSON_UNESCAPED_UNICODE
        |
        JSON_PRESERVE_ZERO_FRACTION
        |
        JSON_THROW_ON_ERROR
    );

    $raw .= "\n";

} catch (Throwable $exception) {
    cmm_json_error(
        503,
        'system_unavailable'
    );
}


http_response_code(200);

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: no-store, max-age=0'
);

header(
    'Pragma: no-cache'
);

header(
    'X-CMM-API-Version: '
    . CMM_API_VERSION
);

header(
    'X-CMM-Schema: '
    . CMM_SYSTEM_SCHEMA
);

header(
    'Content-Length: '
    . (string) strlen($raw)
);


echo $raw;
