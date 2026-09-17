<?php

declare(strict_types=1);

/*
 * CMM R15-K7D9A-R1
 *
 * Product DELETE preflight surface.
 *
 * IMPORTANT:
 * - does NOT create an action;
 * - does NOT write SQLite;
 * - does NOT mutate filesystem;
 * - does NOT authorize deletion;
 * - does NOT invoke any executor.
 */

const CMM_ACTION_SCHEMA =
    'cmm.actions.preflight.v1';

if (!defined('CMM_LIBRARY_URL')) {
    define(
        'CMM_LIBRARY_URL',
        getenv('CMM_LIBRARY_URL')
        ?: 'http://127.0.0.1/centro-multimedia/api/biblioteca.php'
    );
}


function respond(
    int $status,
    array $body
): never {

    $raw = json_encode(
        $body,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    if ($raw === false) {
        $status = 500;

        $raw = json_encode([
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'json_encode_failed',
        ]);
    }

    http_response_code(
        $status
    );

    header(
        'Content-Type: '
        . 'application/json; charset=utf-8'
    );

    header(
        'Cache-Control: '
        . 'no-store, max-age=0'
    );

    header(
        'X-CMM-Schema: '
        . CMM_ACTION_SCHEMA
    );

    header(
        'X-CMM-Action-Mode: '
        . 'PREFLIGHT_ONLY'
    );

    header(
        'Content-Length: '
        . (string) strlen($raw)
    );

    echo $raw;

    exit;
}


$method =
    strtoupper(
        (string) (
            $_SERVER[
                'REQUEST_METHOD'
            ]
            ?? ''
        )
    );


if ($method !== 'POST') {

    header(
        'Allow: POST'
    );

    respond(
        405,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'method_not_allowed',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$contentType =
    strtolower(
        trim(
            explode(
                ';',
                (string) (
                    $_SERVER[
                        'CONTENT_TYPE'
                    ]
                    ?? ''
                ),
                2
            )[0]
        )
    );


if (
    $contentType
    !== 'application/json'
) {
    respond(
        415,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'content_type_required',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$contentLength =
    (int) (
        $_SERVER[
            'CONTENT_LENGTH'
        ]
        ?? 0
    );


if (
    $contentLength <= 0
    ||
    $contentLength > 4096
) {
    respond(
        400,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'invalid_body_size',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$rawInput =
    file_get_contents(
        'php://input'
    );


if (
    $rawInput === false
    ||
    $rawInput === ''
) {
    respond(
        400,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'empty_body',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$request =
    json_decode(
        $rawInput,
        true
    );


if (
    !is_array($request)
    ||
    json_last_error()
        !== JSON_ERROR_NONE
) {
    respond(
        400,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'invalid_json',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$allowedKeys = [
    'operation',
    'media_item_id',
];


foreach (
    array_keys($request)
    as $key
) {
    if (
        !in_array(
            $key,
            $allowedKeys,
            true
        )
    ) {
        respond(
            400,
            [
                'schema' =>
                    CMM_ACTION_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'unknown_field',

                'field' =>
                    $key,

                'mode' =>
                    'PREFLIGHT_ONLY',
            ]
        );
    }
}


if (
    ($request['operation'] ?? null)
    !== 'DELETE_PREFLIGHT'
) {
    respond(
        400,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'invalid_operation',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$mediaId =
    $request[
        'media_item_id'
    ]
    ?? null;


if (
    !is_int($mediaId)
    ||
    $mediaId <= 0
) {
    respond(
        400,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'invalid_media_item_id',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


/*
 * Authoritative source for this phase:
 * current product Library projection.
 *
 * Final execution will perform a deeper,
 * independent live revalidation before any
 * irreversible boundary.
 */

$context =
    stream_context_create([
        'http' => [
            'method' =>
                'GET',

            'timeout' =>
                5,

            'ignore_errors' =>
                false,

            'header' =>
                "Accept: application/json\r\n"
                . "Connection: close\r\n",
        ],
    ]);


$libraryRaw =
    @file_get_contents(
        CMM_LIBRARY_URL,
        false,
        $context
    );


if (
    $libraryRaw === false
    ||
    $libraryRaw === ''
) {
    respond(
        503,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'library_unavailable',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$library =
    json_decode(
        $libraryRaw,
        true
    );


if (
    !is_array($library)
    ||
    ($library['schema'] ?? null)
        !== 'cmm.library.v1'
    ||
    !isset($library['items'])
    ||
    !is_array(
        $library['items']
    )
) {
    respond(
        503,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'library_contract_invalid',

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$matches = [];


foreach (
    $library['items']
    as $item
) {
    if (
        is_array($item)
        &&
        ($item['id'] ?? null)
            === $mediaId
    ) {
        $matches[] = $item;
    }
}


if (count($matches) !== 1) {
    respond(
        409,
        [
            'schema' =>
                CMM_ACTION_SCHEMA,

            'ok' =>
                false,

            'error' =>
                'media_identity_cardinality',

            'media_item_id' =>
                $mediaId,

            'cardinality' =>
                count($matches),

            'mode' =>
                'PREFLIGHT_ONLY',
        ]
    );
}


$item = $matches[0];

$presence =
    is_array(
        $item['presence']
        ?? null
    )
        ? $item['presence']
        : [];


$checks = [
    'media_type_movie' =>
        (
            ($item['type'] ?? null)
            === 'movie'
        ),

    'ownership_manual' =>
        (
            ($item['ownership'] ?? null)
            === 'MANUAL'
        ),

    'inventory_manual_available' =>
        (
            (
                $item[
                    'inventory_state'
                ]
                ?? null
            )
            === 'MANUAL_AVAILABLE'
        ),

    'pipeline_idle' =>
        (
            (
                $item[
                    'pipeline_state'
                ]
                ?? null
            )
            === 'IDLE'
        ),

    'arr_absent' =>
        (
            ($presence['arr'] ?? null)
            === false
        ),

    'filesystem_present' =>
        (
            (
                $presence[
                    'filesystem'
                ]
                ?? null
            )
            === true
        ),

    'jellyfin_present' =>
        (
            (
                $presence[
                    'jellyfin'
                ]
                ?? null
            )
            === true
        ),
];


$reasons = [];


foreach (
    $checks
    as $name => $passed
) {
    if (!$passed) {
        $reasons[] = $name;
    }
}


$eligible =
    count($reasons) === 0;


respond(
    200,
    [
        'schema' =>
            CMM_ACTION_SCHEMA,

        'ok' =>
            true,

        'mode' =>
            'PREFLIGHT_ONLY',

        'operation' =>
            'DELETE_PREFLIGHT',

        'eligible' =>
            $eligible,

        'reasons' =>
            $reasons,

        'checks' =>
            $checks,

        'media' => [
            'id' =>
                $item['id']
                ?? null,

            'title' =>
                $item['title']
                ?? null,

            'type' =>
                $item['type']
                ?? null,

            'ownership' =>
                $item['ownership']
                ?? null,

            'inventory_state' =>
                $item[
                    'inventory_state'
                ]
                ?? null,

            'pipeline_state' =>
                $item[
                    'pipeline_state'
                ]
                ?? null,

            'presence' =>
                $presence,
        ],

        /*
         * These explicit false values are part
         * of the safety contract of R1.
         */
        'action_created' =>
            false,

        'authorization_issued' =>
            false,

        'executor_invoked' =>
            false,

        'filesystem_mutation' =>
            false,

        'database_mutation' =>
            false,

        'irreversible_boundary_crossed' =>
            false,

        'permanent_delete' =>
            false,
    ]
);
