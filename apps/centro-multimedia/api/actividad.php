<?php

declare(strict_types=1);


require_once
    __DIR__
    . '/../includes/actividad.php';


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, max-age=0'
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'X-CMM-API-Version: 1.0.0'
);

header(
    'X-CMM-Schema: cmm.activity.v1'
);


$method =
    $_SERVER['REQUEST_METHOD']
    ?? 'GET';


if ($method !== 'GET') {

    header(
        'Allow: GET'
    );

    http_response_code(405);
    exit;
}


try {

    $json =
        cmmActividadLeerJson();


    header(
        'Content-Length: '
        . strlen($json)
    );


    echo $json;

} catch (Throwable $error) {

    http_response_code(503);

    echo
        '{"error":"activity_unavailable"}'
        . "\n";
}
