<?php

declare(strict_types=1);

require_once __DIR__
    . '/../includes/aplicacion.php';


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
    $storage = cmm_storage_read();

    $data = $storage['data'];


    if (
        !is_array($data)
        ||
        ($data['schema'] ?? null)
        !== CMM_STORAGE_SCHEMA
    ) {
        throw new RuntimeException(
            'Invalid storage projection'
        );
    }


    $public = [
        'schema' =>
            $data['schema'],

        'capacity' => [
            'total_bytes' =>
                $data['capacity']['total_bytes'],

            'used_bytes' =>
                $data['capacity']['used_bytes'],

            'used_percent' =>
                $data['capacity']['used_percent'],

            'free_bytes' =>
                $data['capacity']['free_bytes'],

            'free_percent' =>
                $data['capacity']['free_percent'],

            'reserved_or_unavailable_bytes' =>
                $data['capacity']['reserved_or_unavailable_bytes'],
        ],

        'logical' => [
            'all_references_bytes' =>
                $data['logical']['all_references_bytes'],

            'library_bytes' =>
                $data['logical']['library_bytes'],

            'movies_bytes' =>
                $data['logical']['movies_bytes'],

            'series_bytes' =>
                $data['logical']['series_bytes'],

            'torrents_bytes' =>
                $data['logical']['torrents_bytes'],
        ],

        'physical' => [
            'unique_allocated_bytes' =>
                $data['physical']['unique_allocated_bytes'],

            'unique_logical_bytes' =>
                $data['physical']['unique_logical_bytes'],

            'unique_objects' =>
                $data['physical']['unique_objects'],
        ],

        'hardlinks' => [
            'cross_scope_groups' =>
                $data['hardlinks']['cross_scope_groups'],

            'shared_logical_bytes' =>
                $data['hardlinks']['shared_logical_bytes'],
        ],

        'references' => [
            'total' =>
                $data['references']['total'],

            'library' =>
                $data['references']['library'],

            'torrent' =>
                $data['references']['torrent'],
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
        'storage_unavailable'
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
    . CMM_STORAGE_SCHEMA
);

header(
    'Content-Length: '
    . (string) strlen($raw)
);


echo $raw;
