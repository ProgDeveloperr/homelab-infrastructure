<?php

declare(strict_types=1);

require_once __DIR__
    . '/../includes/caratula.php';


if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'GET'
) {
    header('Allow: GET');

    cmm_poster_json_error(
        405,
        'method_not_allowed'
    );
}


$id =
    cmm_poster_request_id();


try {

    $poster =
        cmm_poster_resolve($id);

} catch (CmmPosterFileMissing $e) {

    cmm_poster_json_error(
        404,
        'poster_file_missing'
    );

} catch (Throwable $e) {

    cmm_poster_json_error(
        503,
        'poster_cache_unavailable'
    );
}


if ($poster === null) {
    cmm_poster_json_error(
        404,
        'poster_not_found'
    );
}


$handle =
    @fopen(
        $poster['path'],
        'rb'
    );


if ($handle === false) {
    cmm_poster_json_error(
        404,
        'poster_file_missing'
    );
}


http_response_code(200);

header(
    'Content-Type: '
    . $poster['content_type']
);

header(
    'Content-Length: '
    . (string) $poster['bytes']
);

header(
    'ETag: "'
    . $poster['sha256']
    . '"'
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: private, max-age=300'
);

header(
    'X-CMM-API-Version: '
    . CMM_POSTER_API_VERSION
);

header(
    'X-CMM-Poster-ID: '
    . $poster['id']
);


fpassthru($handle);

fclose($handle);
