<?php

declare(strict_types=1);

require_once __DIR__
    . '/../includes/solicitudes.php';


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'GET'
) {
    header('Allow: GET');

    cmm_requests_json_error(
        405,
        'method_not_allowed'
    );
}


try {
    $result =
        cmm_requests_read();

} catch (Throwable $e) {

    cmm_requests_json_error(
        503,
        'requests_unavailable'
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
    . CMM_REQUESTS_API_VERSION
);

header(
    'X-CMM-Schema: '
    . CMM_REQUESTS_SCHEMA
);

header(
    'X-CMM-Requests-Items: '
    . (string) $items
);

header(
    'Content-Length: '
    . (string) strlen($raw)
);


/*
 * Entrega byte-exacta del artefacto
 * cmm.requests.v1 ya validado.
 */
echo $raw;
