<?php

declare(strict_types=1);

require_once __DIR__
    . '/../includes/descargas.php';


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'GET'
) {

    header(
        'Allow: GET'
    );

    cmm_downloads_json_error(
        405,
        'method_not_allowed'
    );
}


try {

    $result =
        cmm_downloads_read();

} catch (Throwable $e) {

    cmm_downloads_json_error(
        503,
        'downloads_unavailable'
    );
}


$raw =
    $result['raw'];


http_response_code(
    200
);


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
    . CMM_DOWNLOADS_API_VERSION
);

header(
    'X-CMM-Schema: '
    . CMM_DOWNLOADS_SCHEMA
);

header(
    'Content-Length: '
    . strlen($raw)
);


/*
 * Entrega byte-exacta del artefacto
 * cmm.downloads.v1 ya validado.
 */
echo $raw;
