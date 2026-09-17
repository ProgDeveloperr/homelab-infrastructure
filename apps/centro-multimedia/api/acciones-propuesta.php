<?php

declare(strict_types=1);

/*
 * CMM R15-K7D9B-R1C
 *
 * Web security boundary for creation of
 * DELETE proposals.
 *
 * Proposal creation is fail-closed and must be
 * enabled explicitly through the environment.
 */

const CMM_PROPOSAL_SCHEMA =
    'cmm.actions.proposal.v1';

const CMM_WRITER_SCHEMA =
    'cmm.action-writer.v1';

if (!defined('CMM_WRITER_SOCKET')) {
    define(
        'CMM_WRITER_SOCKET',
        getenv('CMM_WRITER_SOCKET')
        ?: '/run/cmm/cmm-action-writer.sock'
    );
}

/*
 * HARD FEATURE GATE.
 *
 * The public export defaults to disabled.
 */
if (!defined('CMM_PROPOSAL_CREATE_ENABLED')) {
    define(
        'CMM_PROPOSAL_CREATE_ENABLED',
        filter_var(
            getenv('CMM_PROPOSAL_CREATE_ENABLED') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        )
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

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}



function authorizerRequest(
    array $request
): array {
    $socketPath =
        getenv('CMM_AUTHORIZER_SOCKET')
        ?: '/run/cmm/cmm-delete-authorizer.sock';

    $fp =
        @stream_socket_client(
            'unix://' . $socketPath,
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT
        );

    if ($fp === false) {
        throw new RuntimeException(
            'authorizer_connect_failed'
        );
    }

    stream_set_timeout(
        $fp,
        5
    );

    try {
        $payload =
            json_encode(
                $request,
                JSON_UNESCAPED_SLASHES
                |
                JSON_UNESCAPED_UNICODE
                |
                JSON_THROW_ON_ERROR
            )
            . "\n";

        $written =
            fwrite(
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

        $raw =
            fgets(
                $fp,
                65536
            );

        if ($raw === false) {
            throw new RuntimeException(
                'authorizer_read_failed'
            );
        }

        $response =
            json_decode(
                $raw,
                true,
                512,
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


function safetyEnvelope(): array
{
    return [
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


function expectedOrigin(): string
{
    $host =
        $_SERVER[
            'HTTP_HOST'
        ]
        ?? '';

    if (
        $host === ''
        ||
        preg_match(
            '/[\r\n]/',
            $host
        )
    ) {
        return '';
    }

    $https =
        (
            isset(
                $_SERVER['HTTPS']
            )
            &&
            $_SERVER['HTTPS']
                !== ''
            &&
            strtolower(
                (string)
                $_SERVER['HTTPS']
            )
                !== 'off'
        );

    return (
        $https
            ? 'https://'
            : 'http://'
    )
    . $host;
}


function requireSameOrigin(): void
{
    $origin =
        $_SERVER[
            'HTTP_ORIGIN'
        ]
        ?? '';

    $expected =
        expectedOrigin();

    if (
        $origin === ''
        ||
        $expected === ''
        ||
        !hash_equals(
            $expected,
            $origin
        )
    ) {
        respond(
            403,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'origin_denied',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }

    $site =
        $_SERVER[
            'HTTP_SEC_FETCH_SITE'
        ]
        ?? '';

    if (
        $site !== ''
        &&
        $site !== 'same-origin'
    ) {
        respond(
            403,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'fetch_site_denied',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }
}


function readRequest(): array
{
    $contentType =
        $_SERVER[
            'CONTENT_TYPE'
        ]
        ?? '';

    $contentType =
        strtolower(
            trim(
                explode(
                    ';',
                    $contentType,
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
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'unsupported_media_type',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }

    $length =
        (int) (
            $_SERVER[
                'CONTENT_LENGTH'
            ]
            ?? 0
        );

    if (
        $length <= 0
        ||
        $length > 4096
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'invalid_body_size',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }

    $raw =
        file_get_contents(
            'php://input'
        );

    if (
        $raw === false
        ||
        $raw === ''
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'empty_body',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }

    try {
        $decoded =
            json_decode(
                $raw,
                true,
                32,
                JSON_THROW_ON_ERROR
            );
    } catch (
        JsonException $e
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'invalid_json',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        !is_array($decoded)
        ||
        array_is_list(
            $decoded
        )
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'request_not_object',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }

    return $decoded;
}


function requireExactKeys(
    array $request,
    array $allowed
): void {

    foreach (
        array_keys(
            $request
        )
        as $key
    ) {
        if (
            !in_array(
                $key,
                $allowed,
                true
            )
        ) {
            respond(
                400,
                array_merge(
                    [
                        'schema' =>
                            CMM_PROPOSAL_SCHEMA,

                        'ok' =>
                            false,

                        'error' =>
                            'unknown_field',

                        'field' =>
                            $key,

                        'proposal_creation_enabled' =>
                            CMM_PROPOSAL_CREATE_ENABLED,
                    ],
                    safetyEnvelope()
                )
            );
        }
    }
}


function initSession(): void
{
    ini_set(
        'session.use_strict_mode',
        '1'
    );

    ini_set(
        'session.use_only_cookies',
        '1'
    );

    ini_set(
        'session.cookie_httponly',
        '1'
    );

    ini_set(
        'session.cookie_samesite',
        'Strict'
    );

    session_name(
        'CMM_ACTIONS'
    );

    session_set_cookie_params([
        'lifetime' =>
            0,

        'path' =>
            '/centro-multimedia/',

        'secure' =>
            false,

        'httponly' =>
            true,

        'samesite' =>
            'Strict',
    ]);

    if (
        session_status()
        !== PHP_SESSION_ACTIVE
    ) {
        if (
            !session_start()
        ) {
            respond(
                503,
                array_merge(
                    [
                        'schema' =>
                            CMM_PROPOSAL_SCHEMA,

                        'ok' =>
                            false,

                        'error' =>
                            'session_unavailable',

                        'proposal_creation_enabled' =>
                            CMM_PROPOSAL_CREATE_ENABLED,
                    ],
                    safetyEnvelope()
                )
            );
        }
    }
}


function csrfToken(): string
{
    $current =
        $_SESSION[
            'cmm_proposal_csrf'
        ]
        ?? null;

    if (
        !is_string(
            $current
        )
        ||
        !preg_match(
            '/^[a-f0-9]{64}$/',
            $current
        )
    ) {
        $current =
            bin2hex(
                random_bytes(
                    32
                )
            );

        $_SESSION[
            'cmm_proposal_csrf'
        ] =
            $current;
    }

    return $current;
}


function requireCsrf(
    mixed $value
): void {

    $expected =
        csrfToken();

    if (
        !is_string(
            $value
        )
        ||
        !preg_match(
            '/^[a-f0-9]{64}$/',
            $value
        )
        ||
        !hash_equals(
            $expected,
            $value
        )
    ) {
        respond(
            403,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'csrf_denied',

                    'proposal_creation_enabled' =>
                        CMM_PROPOSAL_CREATE_ENABLED,
                ],
                safetyEnvelope()
            )
        );
    }
}


function writerRequest(
    array $payload
): array {

    $errno = 0;
    $errstr = '';

    $fp =
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
        throw new RuntimeException(
            'writer_connect_failed'
        );
    }

    stream_set_timeout(
        $fp,
        10
    );

    $raw =
        json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
            |
            JSON_THROW_ON_ERROR
        );

    fwrite(
        $fp,
        $raw . "\n"
    );

    $response =
        fgets(
            $fp,
            16384
        );

    fclose(
        $fp
    );

    if (
        $response === false
        ||
        $response === ''
    ) {
        throw new RuntimeException(
            'writer_empty_response'
        );
    }

    $decoded =
        json_decode(
            $response,
            true,
            64,
            JSON_THROW_ON_ERROR
        );

    if (
        !is_array(
            $decoded
        )
        ||
        ($decoded['schema'] ?? null)
            !== CMM_WRITER_SCHEMA
        ||
        ($decoded['mode'] ?? null)
            !== 'PROPOSAL_ONLY'
        ||
        ($decoded['authorization_issued'] ?? null)
            !== false
        ||
        ($decoded['executor_invoked'] ?? null)
            !== false
        ||
        ($decoded['filesystem_mutation'] ?? null)
            !== false
        ||
        ($decoded['irreversible_boundary_crossed'] ?? null)
            !== false
        ||
        ($decoded['permanent_delete'] ?? null)
            !== false
    ) {
        throw new RuntimeException(
            'writer_contract_invalid'
        );
    }

    return $decoded;
}


if (
    ($_SERVER[
        'REQUEST_METHOD'
    ] ?? '')
    !== 'POST'
) {
    header(
        'Allow: POST'
    );

    respond(
        405,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'method_not_allowed',

                'proposal_creation_enabled' =>
                    CMM_PROPOSAL_CREATE_ENABLED,
            ],
            safetyEnvelope()
        )
    );
}


requireSameOrigin();

initSession();

$request =
    readRequest();

$operation =
    $request[
        'operation'
    ]
    ?? null;


if (
    $operation
    === 'GET_PROPOSAL_TOKEN'
) {
    requireExactKeys(
        $request,
        [
            'operation',
        ]
    );

    respond(
        200,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    true,

                'operation' =>
                    'GET_PROPOSAL_TOKEN',

                'csrf_token' =>
                    csrfToken(),

                'proposal_creation_enabled' =>
                    CMM_PROPOSAL_CREATE_ENABLED,

                'action_created' =>
                    false,

                'database_mutation' =>
                    false,
            ],
            safetyEnvelope()
        )
    );
}




if (
    $operation
    === 'ISSUE_DELETE_AUTHORIZATION'
) {
    requireExactKeys(
        $request,
        [
            'operation',
            'action_id',
            'request_id',
            'csrf_token',
        ]
    );

    $actionId =
        $request[
            'action_id'
        ]
        ?? null;

    $requestId =
        $request[
            'request_id'
        ]
        ?? null;

    if (
        !is_int($actionId)
        ||
        $actionId <= 0
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'invalid_action_id',
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        !is_string($requestId)
        ||
        !preg_match(
            '/^[A-Za-z0-9_-]{16,80}$/',
            $requestId
        )
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'invalid_request_id',
                ],
                safetyEnvelope()
            )
        );
    }

    requireCsrf(
        $request[
            'csrf_token'
        ]
        ?? null
    );

    try {
        $authorizer =
            authorizerRequest([
                'operation' =>
                    'ISSUE_DELETE_AUTHORIZATION',

                'action_id' =>
                    $actionId,

                'request_id' =>
                    $requestId,
            ]);

    } catch (Throwable $e) {
        respond(
            503,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'authorizer_unavailable',
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        ($authorizer['ok'] ?? null)
            !== true
        ||
        ($authorizer['operation'] ?? null)
            !== 'ISSUE_DELETE_AUTHORIZATION'
        ||
        ($authorizer['action_id'] ?? null)
            !== $actionId
        ||
        ($authorizer['request_id'] ?? null)
            !== $requestId
        ||
        ($authorizer['authorization_issued'] ?? null)
            !== true
        ||
        ($authorizer['execution_authorized'] ?? null)
            !== true
        ||
        ($authorizer['permanent_delete_authorized'] ?? null)
            !== true
        ||
        ($authorizer['irreversible_boundary_authorized'] ?? null)
            !== true
        ||
        ($authorizer['executor_invoked'] ?? null)
            !== false
        ||
        ($authorizer['filesystem_mutation'] ?? null)
            !== false
        ||
        ($authorizer['irreversible_boundary_crossed'] ?? null)
            !== false
        ||
        ($authorizer['permanent_delete'] ?? null)
            !== false
        ||
        !is_string(
            $authorizer[
                'ticket_sha256'
            ]
            ?? null
        )
        ||
        !preg_match(
            '/^[a-f0-9]{64}$/',
            $authorizer[
                'ticket_sha256'
            ]
        )
        ||
        !is_string(
            $authorizer[
                'signature_sha256'
            ]
            ?? null
        )
        ||
        !preg_match(
            '/^[a-f0-9]{64}$/',
            $authorizer[
                'signature_sha256'
            ]
        )
    ) {
        respond(
            409,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        (
                            $authorizer[
                                'error'
                            ]
                            ??
                            'authorization_denied'
                        ),
                ],
                safetyEnvelope()
            )
        );
    }

    respond(
        200,
        [
            'schema' =>
                CMM_PROPOSAL_SCHEMA,

            'ok' =>
                true,

            'operation' =>
                'ISSUE_DELETE_AUTHORIZATION',

            'action_id' =>
                $actionId,

            'request_id' =>
                $requestId,

            'media_item_id' =>
                (
                    $authorizer[
                        'media_item_id'
                    ]
                    ?? null
                ),

            'authorization_issued' =>
                true,

            'execution_authorized' =>
                true,

            'permanent_delete_authorized' =>
                true,

            'irreversible_boundary_authorized' =>
                true,

            'ticket_schema' =>
                (
                    $authorizer[
                        'ticket_schema'
                    ]
                    ?? null
                ),

            'ticket_sha256' =>
                $authorizer[
                    'ticket_sha256'
                ],

            'signature_sha256' =>
                $authorizer[
                    'signature_sha256'
                ],

            'ticket_expires_at_utc' =>
                (
                    $authorizer[
                        'ticket_expires_at_utc'
                    ]
                    ?? null
                ),

            'database_mutation' =>
                false,

            'executor_invoked' =>
                false,

            'filesystem_mutation' =>
                false,

            'irreversible_boundary_crossed' =>
                false,

            'permanent_delete' =>
                false,
        ]
    );
}


if (
    $operation
    === 'CANCEL_DELETE_PROPOSAL'
) {
    requireExactKeys(
        $request,
        [
            'operation',
            'action_id',
            'request_id',
            'csrf_token',
        ]
    );

    $actionId =
        $request[
            'action_id'
        ]
        ?? null;

    $requestId =
        $request[
            'request_id'
        ]
        ?? null;

    if (
        !is_int(
            $actionId
        )
        ||
        $actionId <= 0
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'invalid_action_id',
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        !is_string(
            $requestId
        )
        ||
        !preg_match(
            '/^[A-Za-z0-9_-]{16,80}$/',
            $requestId
        )
    ) {
        respond(
            400,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'invalid_request_id',
                ],
                safetyEnvelope()
            )
        );
    }

    requireCsrf(
        $request[
            'csrf_token'
        ]
        ?? null
    );


    try {
        $authorizationStatus =
            authorizerRequest([
                'operation' =>
                    'GET_AUTHORIZATION_STATUS',

                'action_id' =>
                    $actionId,

                'request_id' =>
                    $requestId,
            ]);

    } catch (Throwable $e) {
        respond(
            503,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'authorizer_unavailable',
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        ($authorizationStatus['ok'] ?? null)
            !== true
        ||
        ($authorizationStatus['operation'] ?? null)
            !== 'GET_AUTHORIZATION_STATUS'
        ||
        ($authorizationStatus['action_id'] ?? null)
            !== $actionId
        ||
        ($authorizationStatus['request_id'] ?? null)
            !== $requestId
    ) {
        respond(
            409,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        (
                            $authorizationStatus[
                                'error'
                            ]
                            ??
                            'authorization_status_denied'
                        ),
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        ($authorizationStatus[
            'authorization_state'
        ] ?? null)
        === 'ACTIVE'
    ) {
        respond(
            409,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'active_authorization_exists',
                ],
                safetyEnvelope()
            )
        );
    }



    $cancelAuthorizationState =
        $authorizationStatus[
            'authorization_state'
        ]
        ?? null;

    if (
        !in_array(
            $cancelAuthorizationState,
            [
                'NONE',
                'EXPIRED',
            ],
            true
        )
    ) {
        respond(
            409,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'authorization_state_not_cancellable',
                ],
                safetyEnvelope()
            )
        );
    }


    try {
        $writer =
            writerRequest([
                'operation' =>
                    'CANCEL_DELETE_PROPOSAL',

                'action_id' =>
                    $actionId,

                'request_id' =>
                    $requestId,

                'authorization_state' =>
                    $cancelAuthorizationState,
            ]);
    } catch (
        Throwable $e
    ) {
        respond(
            503,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        'writer_unavailable',
                ],
                safetyEnvelope()
            )
        );
    }

    if (
        ($writer['ok'] ?? null)
            !== true
        ||
        ($writer['operation'] ?? null)
            !== 'CANCEL_DELETE_PROPOSAL'
        ||
        ($writer['action_id'] ?? null)
            !== $actionId
        ||
        ($writer['status'] ?? null)
            !== 'CANCELLED'
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
            409,
            array_merge(
                [
                    'schema' =>
                        CMM_PROPOSAL_SCHEMA,

                    'ok' =>
                        false,

                    'error' =>
                        (
                            $writer[
                                'error'
                            ]
                            ?? 'cancel_denied'
                        ),
                ],
                safetyEnvelope()
            )
        );
    }

    respond(
        200,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    true,

                'operation' =>
                    'CANCEL_DELETE_PROPOSAL',

                'action_id' =>
                    $actionId,

                'status' =>
                    'CANCELLED',

                'database_mutation' =>
                    (
                        $writer[
                            'database_mutation'
                        ]
                        ?? false
                    ),

                'idempotent_replay' =>
                    (
                        $writer[
                            'idempotent_replay'
                        ]
                        ?? false
                    ),
            ],
            safetyEnvelope()
        )
    );
}


if (
    $operation
    !== 'CREATE_DELETE_PROPOSAL'
) {
    respond(
        400,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'invalid_operation',

                'proposal_creation_enabled' =>
                    CMM_PROPOSAL_CREATE_ENABLED,
            ],
            safetyEnvelope()
        )
    );
}


requireExactKeys(
    $request,
    [
        'operation',
        'media_item_id',
        'request_id',
        'csrf_token',
    ]
);


$mediaId =
    $request[
        'media_item_id'
    ]
    ?? null;

$requestId =
    $request[
        'request_id'
    ]
    ?? null;


if (
    !is_int(
        $mediaId
    )
    ||
    $mediaId <= 0
) {
    respond(
        400,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'invalid_media_item_id',

                'proposal_creation_enabled' =>
                    CMM_PROPOSAL_CREATE_ENABLED,
            ],
            safetyEnvelope()
        )
    );
}


if (
    !is_string(
        $requestId
    )
    ||
    !preg_match(
        '/^[A-Za-z0-9_-]{16,80}$/',
        $requestId
    )
) {
    respond(
        400,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'invalid_request_id',

                'proposal_creation_enabled' =>
                    CMM_PROPOSAL_CREATE_ENABLED,
            ],
            safetyEnvelope()
        )
    );
}


requireCsrf(
    $request[
        'csrf_token'
    ]
    ?? null
);


/*
 * R1C-R1 deliberately stops here.
 *
 * No writer CREATE request is sent while
 * this feature gate remains false.
 */
if (
    !CMM_PROPOSAL_CREATE_ENABLED
) {
    respond(
        503,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'proposal_creation_disabled',

                'proposal_creation_enabled' =>
                    false,

                'action_created' =>
                    false,

                'database_mutation' =>
                    false,
            ],
            safetyEnvelope()
        )
    );
}


try {
    $writer =
        writerRequest([
            'operation' =>
                'CREATE_DELETE_PROPOSAL',

            'media_item_id' =>
                $mediaId,

            'request_id' =>
                $requestId,
        ]);
} catch (
    Throwable $e
) {
    respond(
        503,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    'writer_unavailable',

                'proposal_creation_enabled' =>
                    true,

                'action_created' =>
                    false,

                'database_mutation' =>
                    false,
            ],
            safetyEnvelope()
        )
    );
}


if (
    ($writer['ok'] ?? null)
    !== true
    ||
    ($writer['operation'] ?? null)
    !== 'CREATE_DELETE_PROPOSAL'
    ||
    ($writer['status'] ?? null)
    !== 'PENDING_CONFIRMATION'
    ||
    !is_int(
        $writer['action_id']
        ?? null
    )
) {
    respond(
        409,
        array_merge(
            [
                'schema' =>
                    CMM_PROPOSAL_SCHEMA,

                'ok' =>
                    false,

                'error' =>
                    (
                        $writer[
                            'error'
                        ]
                        ?? 'proposal_denied'
                    ),

                'proposal_creation_enabled' =>
                    true,

                'action_created' =>
                    false,

                'database_mutation' =>
                    false,
            ],
            safetyEnvelope()
        )
    );
}


respond(
    201,
    array_merge(
        [
            'schema' =>
                CMM_PROPOSAL_SCHEMA,

            'ok' =>
                true,

            'operation' =>
                'CREATE_DELETE_PROPOSAL',

            'proposal_creation_enabled' =>
                true,

            'action_id' =>
                $writer[
                    'action_id'
                ],

            'status' =>
                'PENDING_CONFIRMATION',

            'action_created' =>
                (
                    $writer[
                        'action_created'
                    ]
                    ?? false
                ),

            'database_mutation' =>
                (
                    $writer[
                        'database_mutation'
                    ]
                    ?? false
                ),

            'idempotent_replay' =>
                (
                    $writer[
                        'idempotent_replay'
                    ]
                    ?? false
                ),

            'preflight_sha256' =>
                (
                    $writer[
                        'preflight_sha256'
                    ]
                    ?? null
                ),
        ],
        safetyEnvelope()
    )
);
