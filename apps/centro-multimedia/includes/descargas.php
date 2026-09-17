<?php

declare(strict_types=1);


const CMM_DOWNLOADS_API_VERSION =
    '1.0.0';

const CMM_DOWNLOADS_SCHEMA =
    'cmm.downloads.v1';

if (!defined('CMM_DOWNLOADS_SOURCE')) {
    define(
        'CMM_DOWNLOADS_SOURCE',
        rtrim(
            getenv('CMM_STATE_DIR') ?: '/var/lib/cmm/state',
            '/'
        ) . '/downloads-v1.json'
    );
}

const CMM_DOWNLOADS_MAX_BYTES =
    262144;


function cmm_downloads_json_error(
    int $status,
    string $code
): never {

    http_response_code(
        $status
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

    echo json_encode(
        [
            'error' => $code,
        ],
        JSON_UNESCAPED_SLASHES
        |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function cmm_downloads_exact_keys(
    array $value,
    array $expected
): bool {

    $actual =
        array_keys(
            $value
        );

    sort(
        $actual,
        SORT_STRING
    );

    sort(
        $expected,
        SORT_STRING
    );

    return (
        $actual === $expected
    );
}


function cmm_downloads_nonnegative_int(
    mixed $value
): bool {

    return (
        is_int($value)
        &&
        $value >= 0
    );
}


function cmm_downloads_positive_int(
    mixed $value
): bool {

    return (
        is_int($value)
        &&
        $value > 0
    );
}


function cmm_downloads_timestamp(
    mixed $value,
    bool $nullable = false
): bool {

    if ($value === null) {
        return $nullable;
    }

    if (
        !is_string($value)
        ||
        preg_match(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D',
            $value
        ) !== 1
    ) {
        return false;
    }

    $parsed =
        DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            $value,
            new DateTimeZone('UTC')
        );

    if ($parsed === false) {
        return false;
    }

    return (
        $parsed->format(
            'Y-m-d\TH:i:s\Z'
        )
        ===
        $value
    );
}


function cmm_downloads_progress(
    mixed $value
): bool {

    if (
        !is_int($value)
        &&
        !is_float($value)
    ) {
        return false;
    }

    $number =
        (float) $value;

    return (
        is_finite($number)
        &&
        $number >= 0.0
        &&
        $number <= 1.0
    );
}


function cmm_downloads_seasons(
    mixed $value
): bool {

    if (!is_array($value)) {
        return false;
    }

    $previous = null;

    foreach (
        $value as $season
    ) {
        if (
            !is_int($season)
            ||
            $season < 0
        ) {
            return false;
        }

        if (
            $previous !== null
            &&
            $season <= $previous
        ) {
            return false;
        }

        $previous =
            $season;
    }

    return true;
}


function cmm_downloads_read(): array
{
    $path =
        CMM_DOWNLOADS_SOURCE;


    if (
        !is_file($path)
        ||
        !is_readable($path)
    ) {
        throw new RuntimeException(
            'downloads_source_unavailable'
        );
    }


    $size =
        filesize(
            $path
        );


    if (
        $size === false
        ||
        $size < 2
        ||
        $size > CMM_DOWNLOADS_MAX_BYTES
    ) {
        throw new RuntimeException(
            'downloads_source_size_invalid'
        );
    }


    $raw =
        file_get_contents(
            $path
        );


    if (
        $raw === false
        ||
        strlen($raw) !== $size
    ) {
        throw new RuntimeException(
            'downloads_source_read_invalid'
        );
    }


    try {

        $data =
            json_decode(
                $raw,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

    } catch (JsonException $e) {

        throw new RuntimeException(
            'downloads_json_invalid',
            0,
            $e
        );
    }


    if (
        !is_array($data)
        ||
        !cmm_downloads_exact_keys(
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
            'downloads_top_level_invalid'
        );
    }


    if (
        $data['schema']
        !==
        CMM_DOWNLOADS_SCHEMA
    ) {
        throw new RuntimeException(
            'downloads_schema_invalid'
        );
    }


    if (
        !cmm_downloads_timestamp(
            $data[
                'generated_at_utc'
            ]
        )
    ) {
        throw new RuntimeException(
            'downloads_generated_at_invalid'
        );
    }


    $summary =
        $data['summary'];


    if (
        !is_array($summary)
        ||
        !cmm_downloads_exact_keys(
            $summary,
            [
                'total',
                'downloading',
                'complete',
                'waiting',
                'attention',
            ]
        )
    ) {
        throw new RuntimeException(
            'downloads_summary_invalid'
        );
    }


    foreach (
        [
            'total',
            'downloading',
            'complete',
            'waiting',
            'attention',
        ]
        as $key
    ) {
        if (
            !cmm_downloads_nonnegative_int(
                $summary[$key]
            )
        ) {
            throw new RuntimeException(
                'downloads_summary_value_invalid'
            );
        }
    }


    if (
        $summary['total']
        !==
        (
            $summary['downloading']
            +
            $summary['complete']
            +
            $summary['waiting']
            +
            $summary['attention']
        )
    ) {
        throw new RuntimeException(
            'downloads_summary_partition_invalid'
        );
    }


    $items =
        $data['items'];


    if (!is_array($items)) {
        throw new RuntimeException(
            'downloads_items_invalid'
        );
    }


    if (
        count($items)
        !==
        $summary['total']
    ) {
        throw new RuntimeException(
            'downloads_total_mismatch'
        );
    }


    $statuses = [
        'DOWNLOADING',
        'COMPLETE',
        'WAITING',
        'ATTENTION',
    ];


    $reasons = [
        'PROGRESS_COMPLETE',
        'ACTIVE_TRANSFER',
        'STALLED',
        'QUEUED',
        'PAUSED',
        'CHECKING',
        'METADATA',
        'ERROR',
        'MISSING_FILES',
        'UNKNOWN_INCOMPLETE',
    ];


    $waitingReasons = [
        'STALLED',
        'QUEUED',
        'PAUSED',
        'CHECKING',
        'METADATA',
        'UNKNOWN_INCOMPLETE',
    ];


    $ids = [];

    $derived = [
        'DOWNLOADING' => 0,
        'COMPLETE' => 0,
        'WAITING' => 0,
        'ATTENTION' => 0,
    ];


    foreach (
        $items as $item
    ) {

        if (
            !is_array($item)
            ||
            !cmm_downloads_exact_keys(
                $item,
                [
                    'id',
                    'media',
                    'targets',
                    'status',
                    'status_reason',
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
                'downloads_item_invalid'
            );
        }


        if (
            !cmm_downloads_positive_int(
                $item['id']
            )
            ||
            isset(
                $ids[
                    $item['id']
                ]
            )
        ) {
            throw new RuntimeException(
                'downloads_id_invalid'
            );
        }


        $ids[
            $item['id']
        ] = true;


        $media =
            $item['media'];


        if (
            !is_array($media)
            ||
            !cmm_downloads_exact_keys(
                $media,
                [
                    'id',
                    'type',
                    'title',
                ]
            )
        ) {
            throw new RuntimeException(
                'downloads_media_invalid'
            );
        }


        if (
            !cmm_downloads_positive_int(
                $media['id']
            )
        ) {
            throw new RuntimeException(
                'downloads_media_id_invalid'
            );
        }


        if (
            !in_array(
                $media['type'],
                [
                    'movie',
                    'series',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'downloads_media_type_invalid'
            );
        }


        if (
            !is_string(
                $media['title']
            )
            ||
            trim(
                $media['title']
            ) === ''
        ) {
            throw new RuntimeException(
                'downloads_media_title_invalid'
            );
        }


        $targets =
            $item['targets'];


        if (
            !is_array($targets)
            ||
            !cmm_downloads_exact_keys(
                $targets,
                [
                    'mode',
                    'count',
                    'seasons',
                ]
            )
        ) {
            throw new RuntimeException(
                'downloads_targets_invalid'
            );
        }


        if (
            !in_array(
                $targets['mode'],
                [
                    'media',
                    'episodes',
                ],
                true
            )
            ||
            !cmm_downloads_positive_int(
                $targets['count']
            )
            ||
            !cmm_downloads_seasons(
                $targets['seasons']
            )
        ) {
            throw new RuntimeException(
                'downloads_target_values_invalid'
            );
        }


        if (
            $media['type'] === 'movie'
        ) {
            if (
                $targets['mode']
                !==
                'media'
                ||
                $targets['seasons']
                !==
                []
            ) {
                throw new RuntimeException(
                    'downloads_movie_target_invalid'
                );
            }

        } else {

            if (
                $targets['mode']
                !==
                'episodes'
                ||
                count(
                    $targets['seasons']
                ) < 1
            ) {
                throw new RuntimeException(
                    'downloads_series_target_invalid'
                );
            }
        }


        $status =
            $item['status'];

        $reason =
            $item['status_reason'];


        if (
            !is_string($status)
            ||
            !in_array(
                $status,
                $statuses,
                true
            )
            ||
            !is_string($reason)
            ||
            !in_array(
                $reason,
                $reasons,
                true
            )
        ) {
            throw new RuntimeException(
                'downloads_status_invalid'
            );
        }


        if (
            !cmm_downloads_progress(
                $item['progress']
            )
        ) {
            throw new RuntimeException(
                'downloads_progress_invalid'
            );
        }


        $progress =
            (float)
            $item['progress'];


        if (
            $status === 'COMPLETE'
        ) {
            if (
                $progress !== 1.0
                ||
                $reason
                !==
                'PROGRESS_COMPLETE'
                ||
                $item['eta_seconds']
                !==
                null
            ) {
                throw new RuntimeException(
                    'downloads_complete_semantics_invalid'
                );
            }

        } elseif (
            $progress >= 1.0
        ) {
            throw new RuntimeException(
                'downloads_progress_status_mismatch'
            );
        }


        if (
            $status === 'DOWNLOADING'
            &&
            $reason
            !==
            'ACTIVE_TRANSFER'
        ) {
            throw new RuntimeException(
                'downloads_downloading_reason_invalid'
            );
        }


        if (
            $status === 'ATTENTION'
            &&
            !in_array(
                $reason,
                [
                    'ERROR',
                    'MISSING_FILES',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'downloads_attention_reason_invalid'
            );
        }


        if (
            $status === 'WAITING'
            &&
            !in_array(
                $reason,
                $waitingReasons,
                true
            )
        ) {
            throw new RuntimeException(
                'downloads_waiting_reason_invalid'
            );
        }


        foreach (
            [
                'size_bytes',
                'downloaded_bytes',
                'dlspeed_bps',
            ]
            as $key
        ) {
            if (
                !cmm_downloads_nonnegative_int(
                    $item[$key]
                )
            ) {
                throw new RuntimeException(
                    'downloads_integer_field_invalid'
                );
            }
        }


        if (
            $item['eta_seconds']
            !==
            null
        ) {
            if (
                !cmm_downloads_nonnegative_int(
                    $item['eta_seconds']
                )
                ||
                $item['eta_seconds']
                >=
                8640000
            ) {
                throw new RuntimeException(
                    'downloads_eta_invalid'
                );
            }
        }


        if (
            !cmm_downloads_timestamp(
                $item[
                    'completed_at_utc'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'downloads_completed_at_invalid'
            );
        }


        $derived[
            $status
        ]++;
    }


    if (
        $summary['downloading']
        !==
        $derived['DOWNLOADING']
        ||
        $summary['complete']
        !==
        $derived['COMPLETE']
        ||
        $summary['waiting']
        !==
        $derived['WAITING']
        ||
        $summary['attention']
        !==
        $derived['ATTENTION']
    ) {
        throw new RuntimeException(
            'downloads_summary_derivation_invalid'
        );
    }


    return [
        'raw' =>
            $raw,

        'data' =>
            $data,
    ];
}
