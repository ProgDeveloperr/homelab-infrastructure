<?php

declare(strict_types=1);

const CMM_DELETE_PROPOSALS_SCHEMA =
    'cmm.delete-proposals.v1';

const CMM_WRITER_SCHEMA =
    'cmm.action-writer.v1';

if (!defined('CMM_WRITER_SOCKET')) {
    define(
        'CMM_WRITER_SOCKET',
        getenv('CMM_WRITER_SOCKET')
        ?: '/run/cmm/cmm-action-writer.sock'
    );
}


const CMM_AUTHORIZER_SCHEMA =
    'cmm.delete-authorizer.v1';

if (!defined('CMM_AUTHORIZER_SOCKET')) {
    define(
        'CMM_AUTHORIZER_SOCKET',
        getenv('CMM_AUTHORIZER_SOCKET')
        ?: '/run/cmm/cmm-delete-authorizer.sock'
    );
}


function respond(
    int $status,
    array $payload
): never {

    http_response_code(
        $status
    );

    header(
        'Content-Type: '
        . 'application/json; '
        . 'charset=utf-8'
    );

    header(
        'Cache-Control: '
        . 'no-store, no-cache, '
        . 'must-revalidate, max-age=0'
    );

    header(
        'Pragma: no-cache'
    );

    header(
        'X-Content-Type-Options: nosniff'
    );

    header(
        'Cross-Origin-Resource-Policy: same-origin'
    );

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function safety(): array
{
    return [
        'database_mutation' =>
            false,

        'action_created' =>
            false,

        'authorization_issued' =>
            false,

        'executor_invoked' =>
            false,

        'filesystem_mutation' =>
            false,

        'irreversible_boundary_crossed' =>
            false,

        'permanent_delete' =>
            false,
    ];
}



function authorizationStatus(
    int $actionId,
    string $requestId
): array {
    $errno=0;
    $errstr='';

    $fp=
        @stream_socket_client(
            'unix://'
            . CMM_AUTHORIZER_SOCKET,
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT
        );

    if ($fp === false) {
        throw new RuntimeException(
            'authorizer_unavailable'
        );
    }

    stream_set_timeout(
        $fp,
        5
    );

    try {
        $payload=
            json_encode(
                [
                    'operation' =>
                        'GET_AUTHORIZATION_STATUS',

                    'action_id' =>
                        $actionId,

                    'request_id' =>
                        $requestId,
                ],
                JSON_UNESCAPED_SLASHES
                |
                JSON_THROW_ON_ERROR
            )
            . "\n";

        $written=fwrite(
            $fp,
            $payload
        );

        if (
            $written === false
            ||
            $written !== strlen(
                $payload
            )
        ) {
            throw new RuntimeException(
                'authorizer_write_failed'
            );
        }

        $raw=fgets(
            $fp,
            65536
        );

        if ($raw === false) {
            throw new RuntimeException(
                'authorizer_read_failed'
            );
        }

        $response=
            json_decode(
                $raw,
                true,
                64,
                JSON_THROW_ON_ERROR
            );

        if (!is_array($response)) {
            throw new RuntimeException(
                'authorizer_invalid_response'
            );
        }

        return $response;

    } finally {
        fclose($fp);
    }
}


if (
    ($_SERVER[
        'REQUEST_METHOD'
    ] ?? '')
    !== 'GET'
) {
    header(
        'Allow: GET'
    );

    respond(
        405,
        array_merge(
            [
                'schema' =>
                    CMM_DELETE_PROPOSALS_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'method_not_allowed',

                'mode' =>
                    'READ_ONLY',
            ],
            safety()
        )
    );
}


$errno=0;
$errstr='';

$fp=
    @stream_socket_client(
        'unix://'
        . CMM_WRITER_SOCKET,
        $errno,
        $errstr,
        5,
        STREAM_CLIENT_CONNECT
    );

if (
    $fp === false
) {
    respond(
        503,
        array_merge(
            [
                'schema' =>
                    CMM_DELETE_PROPOSALS_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'writer_unavailable',

                'mode' =>
                    'READ_ONLY',
            ],
            safety()
        )
    );
}


stream_set_timeout(
    $fp,
    10
);


fwrite(
    $fp,
    json_encode(
        [
            'operation' =>
                'LIST_DELETE_PROPOSALS',
        ],
        JSON_UNESCAPED_SLASHES
    )
    . "\n"
);


$raw=
    fgets(
        $fp,
        32768
    );

fclose(
    $fp
);


if (
    $raw === false
    ||
    $raw === ''
) {
    respond(
        503,
        array_merge(
            [
                'schema' =>
                    CMM_DELETE_PROPOSALS_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'writer_empty_response',

                'mode' =>
                    'READ_ONLY',
            ],
            safety()
        )
    );
}


try {
    $writer=
        json_decode(
            $raw,
            true,
            64,
            JSON_THROW_ON_ERROR
        );
} catch (
    Throwable $e
) {
    respond(
        503,
        array_merge(
            [
                'schema' =>
                    CMM_DELETE_PROPOSALS_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'writer_invalid_json',

                'mode' =>
                    'READ_ONLY',
            ],
            safety()
        )
    );
}


if (
    !is_array(
        $writer
    )
    ||
    ($writer['schema'] ?? null)
        !== CMM_WRITER_SCHEMA
    ||
    ($writer['mode'] ?? null)
        !== 'PROPOSAL_ONLY'
    ||
    ($writer['ok'] ?? null)
        !== true
    ||
    ($writer['operation'] ?? null)
        !== 'LIST_DELETE_PROPOSALS'
    ||
    !is_array(
        $writer['items']
        ?? null
    )
    ||
    !is_int(
        $writer['items_count']
        ?? null
    )
    ||
    ($writer['items_count'] ?? null)
        !== count(
            $writer['items']
        )
    ||
    ($writer['database_mutation'] ?? null)
        !== false
    ||
    ($writer['action_created'] ?? null)
        !== false
    ||
    ($writer['authorization_issued'] ?? null)
        !== false
    ||
    ($writer['executor_invoked'] ?? null)
        !== false
    ||
    ($writer['filesystem_mutation'] ?? null)
        !== false
    ||
    ($writer['irreversible_boundary_crossed'] ?? null)
        !== false
    ||
    ($writer['permanent_delete'] ?? null)
        !== false
) {
    respond(
        503,
        array_merge(
            [
                'schema' =>
                    CMM_DELETE_PROPOSALS_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'writer_contract_invalid',

                'mode' =>
                    'READ_ONLY',
            ],
            safety()
        )
    );
}


$items=[];

foreach (
    $writer['items']
    as $item
) {
    $actionId=
        $item[
            'action_id'
        ]
        ?? null;

    $requestId=
        $item[
            'request_id'
        ]
        ?? null;

    if (
        !is_int($actionId)
        ||
        !is_string($requestId)
    ) {
        respond(
            503,
            array_merge(
                [
                    'schema' =>
                        CMM_DELETE_PROPOSALS_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'writer_item_binding_invalid',

                    'mode' =>
                        'READ_ONLY',
                ],
                safety()
            )
        );
    }

    try {
        $auth=
            authorizationStatus(
                $actionId,
                $requestId
            );

    } catch (Throwable $e) {
        respond(
            503,
            array_merge(
                [
                    'schema' =>
                        CMM_DELETE_PROPOSALS_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'authorizer_unavailable',

                    'mode' =>
                        'READ_ONLY',
                ],
                safety()
            )
        );
    }

    if (
        ($auth['schema'] ?? null)
            !== CMM_AUTHORIZER_SCHEMA
        ||
        ($auth['ok'] ?? null)
            !== true
        ||
        ($auth['operation'] ?? null)
            !== 'GET_AUTHORIZATION_STATUS'
        ||
        ($auth['action_id'] ?? null)
            !== $actionId
        ||
        ($auth['request_id'] ?? null)
            !== $requestId
        ||
        !in_array(
            $auth[
                'authorization_state'
            ]
            ?? null,
            [
                'NONE',
                'ACTIVE',
                'EXPIRED',
            ],
            true
        )
        ||
        !is_bool(
            $auth[
                'authorization_issued'
            ]
            ?? null
        )
        ||
        !is_bool(
            $auth[
                'authorization_active'
            ]
            ?? null
        )
        ||
        !is_bool(
            $auth[
                'ticket_present'
            ]
            ?? null
        )
        ||
        !is_bool(
            $auth[
                'signature_verified'
            ]
            ?? null
        )
        ||
        !is_bool(
            $auth[
                'can_authorize'
            ]
            ?? null
        )
        ||
        ($auth['database_mutation'] ?? null)
            !== false
        ||
        ($auth['executor_invoked'] ?? null)
            !== false
        ||
        ($auth['filesystem_mutation'] ?? null)
            !== false
        ||
        ($auth['irreversible_boundary_crossed'] ?? null)
            !== false
        ||
        ($auth['permanent_delete'] ?? null)
            !== false
    ) {
        respond(
            503,
            array_merge(
                [
                    'schema' =>
                        CMM_DELETE_PROPOSALS_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'authorization_status_invalid',

                    'mode' =>
                        'READ_ONLY',
                ],
                safety()
            )
        );
    }

    $state=
        $auth[
            'authorization_state'
        ];

    $item[
        'authorization'
    ]=[
        'state' =>
            $state,

        'issued' =>
            $auth[
                'authorization_issued'
            ],

        'active' =>
            $auth[
                'authorization_active'
            ],

        'ticket_present' =>
            $auth[
                'ticket_present'
            ],

        'signature_verified' =>
            $auth[
                'signature_verified'
            ],

        'expires_at_utc' =>
            (
                $auth[
                    'ticket_expires_at_utc'
                ]
                ?? null
            ),

        'can_authorize' =>
            $auth[
                'can_authorize'
            ],
    ];

    if ($state === 'ACTIVE') {
        $item[
            'can_cancel'
        ]=false;
    }

    $items[]=$item;
}




respond(
    200,
    array_merge(
        [
            'schema' =>
                CMM_DELETE_PROPOSALS_SCHEMA,

            'ok' =>
                true,

            'mode' =>
                'READ_ONLY',

            'generated_at_utc' =>
                $writer[
                    'generated_at_utc'
                ],

            'items_count' =>
                $writer[
                    'items_count'
                ],

            'items' =>
                $items,
        ],
        safety()
    )
);
