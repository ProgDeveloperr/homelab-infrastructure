<?php

declare(strict_types=1);

const CMM_REQUESTS_API_VERSION = '1.0.0';

const CMM_REQUESTS_SCHEMA =
    'cmm.requests.v1';

if (!defined('CMM_REQUESTS_SOURCE')) {
    define(
        'CMM_REQUESTS_SOURCE',
        rtrim(
            getenv('CMM_STATE_DIR') ?: '/var/lib/cmm/state',
            '/'
        ) . '/requests-v1.json'
    );
}

const CMM_REQUESTS_MAX_BYTES =
    262144;


function cmm_requests_json_error(
    int $status,
    string $code
): never {
    http_response_code($status);

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

    echo json_encode(
        [
            'ok' => false,
            'error' => $code,
        ],
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );

    exit;
}


function cmm_requests_exact_keys(
    array $value,
    array $expected
): bool {
    $actual = array_keys($value);

    sort($actual);
    sort($expected);

    return $actual === $expected;
}


function cmm_requests_nonnegative_int(
    mixed $value
): bool {
    return (
        is_int($value)
        &&
        $value >= 0
    );
}


function cmm_requests_optional_nonnegative_int(
    mixed $value
): bool {
    return (
        $value === null
        ||
        cmm_requests_nonnegative_int(
            $value
        )
    );
}


function cmm_requests_valid_timestamp(
    mixed $value,
    bool $nullable = false
): bool {
    if ($value === null) {
        return $nullable;
    }

    if (
        !is_string($value)
        ||
        trim($value) === ''
    ) {
        return false;
    }

    try {
        new DateTimeImmutable(
            $value
        );
    } catch (Throwable) {
        return false;
    }

    return true;
}


function cmm_requests_read(): array
{
    $path = CMM_REQUESTS_SOURCE;

    if (
        !is_file($path)
        ||
        !is_readable($path)
    ) {
        throw new RuntimeException(
            'requests_source_unavailable'
        );
    }


    $size = filesize($path);

    if (
        $size === false
        ||
        $size < 2
        ||
        $size > CMM_REQUESTS_MAX_BYTES
    ) {
        throw new RuntimeException(
            'requests_source_size_invalid'
        );
    }


    $raw = file_get_contents(
        $path
    );

    if ($raw === false) {
        throw new RuntimeException(
            'requests_source_unavailable'
        );
    }


    try {
        $data = json_decode(
            $raw,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        throw new RuntimeException(
            'requests_json_invalid'
        );
    }


    if (
        !is_array($data)
        ||
        !cmm_requests_exact_keys(
            $data,
            [
                'schema',
                'generated_at_utc',
                'summary',
                'items',
            ]
        )
    ) {
        throw new RuntimeException(
            'requests_top_contract_invalid'
        );
    }


    if (
        $data['schema']
        !==
        CMM_REQUESTS_SCHEMA
    ) {
        throw new RuntimeException(
            'requests_schema_invalid'
        );
    }


    if (
        !cmm_requests_valid_timestamp(
            $data['generated_at_utc']
        )
    ) {
        throw new RuntimeException(
            'requests_generated_timestamp_invalid'
        );
    }


    $summary = $data['summary'];

    if (
        !is_array($summary)
        ||
        !cmm_requests_exact_keys(
            $summary,
            [
                'total',
                'available',
                'in_progress',
                'waiting',
                'attention',
            ]
        )
    ) {
        throw new RuntimeException(
            'requests_summary_contract_invalid'
        );
    }


    foreach (
        [
            'total',
            'available',
            'in_progress',
            'waiting',
            'attention',
        ]
        as $summaryKey
    ) {
        if (
            !cmm_requests_nonnegative_int(
                $summary[$summaryKey]
            )
        ) {
            throw new RuntimeException(
                'requests_summary_value_invalid'
            );
        }
    }


    $items = $data['items'];

    if (
        !is_array($items)
        ||
        !array_is_list($items)
    ) {
        throw new RuntimeException(
            'requests_items_invalid'
        );
    }


    if (
        $summary['total']
        !==
        count($items)
    ) {
        throw new RuntimeException(
            'requests_summary_total_mismatch'
        );
    }


    $allowedStages = [
        'REQUESTED',
        'ARR_RECEIVED',
        'GRABBED',
        'QUEUED',
        'DOWNLOADING',
        'DOWNLOAD_COMPLETE',
        'IMPORTED',
        'AVAILABLE',
        'FAILED',
    ];


    $stageReasons = [
        'REQUESTED'
            => 'REQUEST_PRESENT',

        'ARR_RECEIVED'
            => 'ARR_REGISTERED',

        'GRABBED'
            => 'ARR_LATEST_RELEVANT_EVENT_GRABBED',

        'QUEUED'
            => 'ARR_CURRENT_QUEUE',

        'DOWNLOADING'
            => 'QBIT_CURRENT_INCOMPLETE',

        'DOWNLOAD_COMPLETE'
            => 'QBIT_CURRENT_COMPLETE',

        'IMPORTED'
            => 'FILESYSTEM_PRESENT',

        'AVAILABLE'
            => 'FILESYSTEM_AND_JELLYFIN',

        'FAILED'
            => 'ARR_LATEST_RELEVANT_EVENT_FAILED',
    ];


    $inventoryStates = [
        'ARR_MANAGED_AVAILABLE',
        'ARR_MANAGED_JELLYFIN_MISSING',
        'ARR_MANAGED_FILESYSTEM_MISSING',
        'ARR_REGISTERED_NO_FILE',
        'ARR_STATE_DRIFT',
        'MANUAL_AVAILABLE',
        'JELLYFIN_FILESYSTEM_MISSING',
        'FILESYSTEM_ONLY',
        'UNKNOWN',
    ];


    $idsSeen = [];

    $derivedAvailable = 0;
    $derivedInProgress = 0;
    $derivedWaiting = 0;
    $derivedAttention = 0;

    $previousTimestamp = null;
    $previousId = null;


    foreach ($items as $item) {

        if (
            !is_array($item)
            ||
            !cmm_requests_exact_keys(
                $item,
                [
                    'id',
                    'media',
                    'requested_at_utc',
                    'request',
                    'seasons',
                    'stage',
                    'stage_reason',
                    'presence',
                    'inventory_state',
                    'downloads',
                    'history',
                ]
            )
        ) {
            throw new RuntimeException(
                'requests_item_contract_invalid'
            );
        }


        if (
            !is_int($item['id'])
            ||
            $item['id'] < 1
            ||
            isset(
                $idsSeen[
                    (string) $item['id']
                ]
            )
        ) {
            throw new RuntimeException(
                'requests_item_id_invalid'
            );
        }


        $idsSeen[
            (string) $item['id']
        ] = true;


        /*
         * Media pública.
         */
        $media = $item['media'];

        if (
            !is_array($media)
            ||
            !cmm_requests_exact_keys(
                $media,
                [
                    'id',
                    'type',
                    'title',
                ]
            )
        ) {
            throw new RuntimeException(
                'requests_media_contract_invalid'
            );
        }


        if (
            !is_int($media['id'])
            ||
            $media['id'] < 1
        ) {
            throw new RuntimeException(
                'requests_media_id_invalid'
            );
        }


        if (
            !in_array(
                $media['type'],
                [
                    'movie',
                    'tv',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'requests_media_type_invalid'
            );
        }


        if (
            !is_string($media['title'])
            ||
            trim($media['title']) === ''
        ) {
            throw new RuntimeException(
                'requests_media_title_invalid'
            );
        }


        /*
         * Orden determinista:
         * requested_at DESC, id DESC.
         */
        if (
            !cmm_requests_valid_timestamp(
                $item['requested_at_utc']
            )
        ) {
            throw new RuntimeException(
                'requests_requested_timestamp_invalid'
            );
        }


        $currentTimestamp =
            (
                new DateTimeImmutable(
                    $item[
                        'requested_at_utc'
                    ]
                )
            )->getTimestamp();


        if ($previousTimestamp !== null) {

            if (
                $currentTimestamp
                >
                $previousTimestamp
            ) {
                throw new RuntimeException(
                    'requests_order_invalid'
                );
            }


            if (
                $currentTimestamp
                ===
                $previousTimestamp
                &&
                $item['id']
                >
                $previousId
            ) {
                throw new RuntimeException(
                    'requests_order_invalid'
                );
            }
        }


        $previousTimestamp =
            $currentTimestamp;

        $previousId =
            $item['id'];


        /*
         * Estado original de Seerr.
         */
        $request = $item['request'];

        if (
            !is_array($request)
            ||
            !cmm_requests_exact_keys(
                $request,
                [
                    'status_code',
                ]
            )
        ) {
            throw new RuntimeException(
                'requests_request_contract_invalid'
            );
        }


        if (
            $request['status_code'] !== null
            &&
            !is_int(
                $request['status_code']
            )
        ) {
            throw new RuntimeException(
                'requests_status_code_invalid'
            );
        }


        /*
         * Temporadas solicitadas.
         */
        $seasons = $item['seasons'];

        if (
            !is_array($seasons)
            ||
            !array_is_list($seasons)
        ) {
            throw new RuntimeException(
                'requests_seasons_invalid'
            );
        }


        $seasonNumbers = [];


        foreach ($seasons as $season) {

            if (
                !is_array($season)
                ||
                !cmm_requests_exact_keys(
                    $season,
                    [
                        'season_number',
                        'status_code',
                    ]
                )
            ) {
                throw new RuntimeException(
                    'requests_season_contract_invalid'
                );
            }


            if (
                !is_int(
                    $season[
                        'season_number'
                    ]
                )
                ||
                $season[
                    'season_number'
                ] < 0
            ) {
                throw new RuntimeException(
                    'requests_season_number_invalid'
                );
            }


            if (
                isset(
                    $seasonNumbers[
                        (string)
                        $season[
                            'season_number'
                        ]
                    ]
                )
            ) {
                throw new RuntimeException(
                    'requests_duplicate_season'
                );
            }


            $seasonNumbers[
                (string)
                $season[
                    'season_number'
                ]
            ] = true;


            if (
                $season['status_code']
                !== null
                &&
                !is_int(
                    $season[
                        'status_code'
                    ]
                )
            ) {
                throw new RuntimeException(
                    'requests_season_status_invalid'
                );
            }
        }


        /*
         * Stage público.
         */
        $stage = $item['stage'];

        if (
            !in_array(
                $stage,
                $allowedStages,
                true
            )
        ) {
            throw new RuntimeException(
                'requests_stage_invalid'
            );
        }


        if (
            !is_string(
                $item['stage_reason']
            )
            ||
            (
                $stageReasons[$stage]
                ??
                null
            )
            !==
            $item['stage_reason']
        ) {
            throw new RuntimeException(
                'requests_stage_reason_invalid'
            );
        }


        /*
         * Presencia actual.
         */
        $presence = $item['presence'];

        if (
            !is_array($presence)
            ||
            !cmm_requests_exact_keys(
                $presence,
                [
                    'arr',
                    'filesystem',
                    'jellyfin',
                ]
            )
        ) {
            throw new RuntimeException(
                'requests_presence_contract_invalid'
            );
        }


        foreach (
            [
                'arr',
                'filesystem',
                'jellyfin',
            ]
            as $presenceKey
        ) {
            if (
                !is_bool(
                    $presence[
                        $presenceKey
                    ]
                )
            ) {
                throw new RuntimeException(
                    'requests_presence_value_invalid'
                );
            }
        }


        if (
            !in_array(
                $item['inventory_state'],
                $inventoryStates,
                true
            )
        ) {
            throw new RuntimeException(
                'requests_inventory_state_invalid'
            );
        }


        /*
         * Descargas públicas sanitizadas.
         */
        $downloads = $item['downloads'];

        if (
            !is_array($downloads)
            ||
            !array_is_list($downloads)
        ) {
            throw new RuntimeException(
                'requests_downloads_invalid'
            );
        }


        foreach ($downloads as $download) {

            if (
                !is_array($download)
                ||
                !cmm_requests_exact_keys(
                    $download,
                    [
                        'state',
                        'progress',
                        'size_bytes',
                        'downloaded_bytes',
                        'dlspeed_bps',
                        'eta_seconds',
                        'completed_at_utc',
                    ]
                )
            ) {
                throw new RuntimeException(
                    'requests_download_contract_invalid'
                );
            }


            if (
                !is_string(
                    $download['state']
                )
                ||
                trim(
                    $download['state']
                ) === ''
            ) {
                throw new RuntimeException(
                    'requests_download_state_invalid'
                );
            }


            $progress =
                $download['progress'];

            if (
                !is_int($progress)
                &&
                !is_float($progress)
            ) {
                throw new RuntimeException(
                    'requests_download_progress_invalid'
                );
            }


            if (
                $progress < 0
                ||
                $progress > 1
            ) {
                throw new RuntimeException(
                    'requests_download_progress_invalid'
                );
            }


            foreach (
                [
                    'size_bytes',
                    'downloaded_bytes',
                    'dlspeed_bps',
                ]
                as $integerKey
            ) {
                if (
                    !cmm_requests_nonnegative_int(
                        $download[
                            $integerKey
                        ]
                    )
                ) {
                    throw new RuntimeException(
                        'requests_download_integer_invalid'
                    );
                }
            }


            if (
                !cmm_requests_optional_nonnegative_int(
                    $download[
                        'eta_seconds'
                    ]
                )
            ) {
                throw new RuntimeException(
                    'requests_eta_invalid'
                );
            }


            if (
                !cmm_requests_valid_timestamp(
                    $download[
                        'completed_at_utc'
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'requests_completed_timestamp_invalid'
                );
            }
        }


        /*
         * Evidencia histórica sanitizada.
         */
        $history = $item['history'];

        if (
            !is_array($history)
            ||
            !cmm_requests_exact_keys(
                $history,
                [
                    'last_grabbed_at_utc',
                    'last_failed_at_utc',
                    'last_imported_at_utc',
                ]
            )
        ) {
            throw new RuntimeException(
                'requests_history_contract_invalid'
            );
        }


        foreach (
            [
                'last_grabbed_at_utc',
                'last_failed_at_utc',
                'last_imported_at_utc',
            ]
            as $historyKey
        ) {
            if (
                !cmm_requests_valid_timestamp(
                    $history[
                        $historyKey
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'requests_history_timestamp_invalid'
                );
            }
        }


        /*
         * Invariantes directamente demostrables
         * por la proyección pública.
         */
        if ($stage === 'AVAILABLE') {

            if (
                !$presence['filesystem']
                ||
                !$presence['jellyfin']
            ) {
                throw new RuntimeException(
                    'requests_available_semantics_invalid'
                );
            }

        } elseif ($stage === 'IMPORTED') {

            if (
                !$presence['filesystem']
                ||
                $presence['jellyfin']
            ) {
                throw new RuntimeException(
                    'requests_imported_semantics_invalid'
                );
            }

        } elseif ($stage === 'DOWNLOADING') {

            if (count($downloads) < 1) {
                throw new RuntimeException(
                    'requests_downloading_semantics_invalid'
                );
            }


            $hasIncomplete = false;

            foreach ($downloads as $download) {
                if (
                    $download['progress']
                    < 1
                ) {
                    $hasIncomplete = true;
                    break;
                }
            }


            if (!$hasIncomplete) {
                throw new RuntimeException(
                    'requests_downloading_semantics_invalid'
                );
            }

        } elseif (
            $stage
            ===
            'DOWNLOAD_COMPLETE'
        ) {

            if (
                count($downloads) < 1
                ||
                $presence['filesystem']
            ) {
                throw new RuntimeException(
                    'requests_download_complete_semantics_invalid'
                );
            }


            foreach ($downloads as $download) {
                if (
                    $download['progress']
                    < 1
                ) {
                    throw new RuntimeException(
                        'requests_download_complete_semantics_invalid'
                    );
                }
            }

        } elseif (
            $stage
            ===
            'ARR_RECEIVED'
        ) {

            if (!$presence['arr']) {
                throw new RuntimeException(
                    'requests_arr_received_semantics_invalid'
                );
            }

        } elseif ($stage === 'FAILED') {

            if (
                $history[
                    'last_failed_at_utc'
                ] === null
            ) {
                throw new RuntimeException(
                    'requests_failed_semantics_invalid'
                );
            }

        } elseif ($stage === 'GRABBED') {

            if (
                $history[
                    'last_grabbed_at_utc'
                ] === null
            ) {
                throw new RuntimeException(
                    'requests_grabbed_semantics_invalid'
                );
            }
        }


        /*
         * Derivación de summary.
         */
        if ($stage === 'AVAILABLE') {

            $derivedAvailable++;

        } elseif (
            in_array(
                $stage,
                [
                    'GRABBED',
                    'QUEUED',
                    'DOWNLOADING',
                    'DOWNLOAD_COMPLETE',
                    'IMPORTED',
                ],
                true
            )
        ) {

            $derivedInProgress++;

        } elseif (
            in_array(
                $stage,
                [
                    'REQUESTED',
                    'ARR_RECEIVED',
                ],
                true
            )
        ) {

            $derivedWaiting++;

        } elseif ($stage === 'FAILED') {

            $derivedAttention++;
        }
    }


    if (
        $summary['available']
        !==
        $derivedAvailable
        ||
        $summary['in_progress']
        !==
        $derivedInProgress
        ||
        $summary['waiting']
        !==
        $derivedWaiting
        ||
        $summary['attention']
        !==
        $derivedAttention
    ) {
        throw new RuntimeException(
            'requests_summary_mismatch'
        );
    }


    if (
        $summary['available']
        +
        $summary['in_progress']
        +
        $summary['waiting']
        +
        $summary['attention']
        !==
        $summary['total']
    ) {
        throw new RuntimeException(
            'requests_summary_partition_invalid'
        );
    }


    return [
        'raw' => $raw,
        'data' => $data,
        'items' => count($items),
    ];
}
