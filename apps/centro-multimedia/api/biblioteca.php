<?php

declare(strict_types=1);

require_once __DIR__
    . '/../includes/biblioteca.php';


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'GET'
) {
    header('Allow: GET');

    cmm_library_json_error(
        405,
        'method_not_allowed'
    );
}


try {
    $result = cmm_library_read();
} catch (Throwable $e) {
    cmm_library_json_error(
        503,
        'library_unavailable'
    );
}


$raw = $result['raw'];
$items = $result['items'];


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
    . CMM_LIBRARY_API_VERSION
);

header(
    'X-CMM-Schema: '
    . CMM_LIBRARY_SCHEMA
);

header(
    'X-CMM-Library-Items: '
    . (string) $items
);

header(
    'Content-Length: '
    . (string) strlen($raw)
);


echo $raw;
