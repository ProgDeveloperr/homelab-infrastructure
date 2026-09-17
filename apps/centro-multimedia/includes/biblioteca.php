<?php

declare(strict_types=1);


const CMM_LIBRARY_API_VERSION = '1.0.0';

const CMM_LIBRARY_SCHEMA = 'cmm.library.v1';

if (!defined('CMM_LIBRARY_SOURCE')) {
    define(
        'CMM_LIBRARY_SOURCE',
        rtrim(
            getenv('CMM_STATE_DIR') ?: '/var/lib/cmm/state',
            '/'
        ) . '/library-v1.json'
    );
}


function cmm_library_json_error(
    int $status,
    string $error
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
            'error' => $error,
        ],
        JSON_UNESCAPED_SLASHES
        |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


function cmm_library_exact_keys(
    array $value,
    array $expected
): bool {
    return array_keys($value) === $expected;
}


function cmm_library_optional_positive_int(
    mixed $value
): bool {
    return $value === null
        ||
        (
            is_int($value)
            &&
            $value > 0
        );
}


function cmm_library_nonnegative_int(
    mixed $value
): bool {
    return is_int($value)
        &&
        $value >= 0;
}


function cmm_library_valid_timestamp(
    mixed $value
): bool {
    if (
        !is_string($value)
        ||
        $value === ''
    ) {
        return false;
    }

    try {
        new DateTimeImmutable($value);
    } catch (Throwable) {
        return false;
    }

    return true;
}


function cmm_library_validate_poster_ref(
    mixed $value
): bool {
    if ($value === null) {
        return true;
    }

    if (
        !is_string($value)
        ||
        $value === ''
        ||
        strlen($value) > 1024
    ) {
        return false;
    }

    $protectedPrefixesRaw =
        getenv('CMM_PROTECTED_PREFIXES')
        ?: '/etc/,/home/,/root/,/run/,/var/lib/,/srv/private/';
    
    $forbiddenPrefixes = array_values(
        array_filter(
            array_map(
                'trim',
                explode(',', $protectedPrefixesRaw)
            )
        )
    );

    foreach ($forbiddenPrefixes as $prefix) {
        if (str_starts_with($value, $prefix)) {
            return false;
        }
    }

    if (str_starts_with($value, '/')) {
        return false;
    }

    if (
        preg_match(
            '/^[A-Za-z]:[\\\\\\/]/',
            $value
        ) === 1
    ) {
        return false;
    }

    if (
        preg_match(
            '/[\x00-\x1F]/',
            $value
        ) === 1
    ) {
        return false;
    }

    if (str_contains($value, '://')) {
        $parts = parse_url($value);

        if (
            !is_array($parts)
            ||
            !isset(
                $parts['scheme'],
                $parts['host']
            )
        ) {
            return false;
        }

        if (
            !in_array(
                strtolower(
                    (string) $parts['scheme']
                ),
                [
                    'http',
                    'https',
                ],
                true
            )
        ) {
            return false;
        }

        if (
            isset($parts['user'])
            ||
            isset($parts['pass'])
            ||
            isset($parts['query'])
        ) {
            return false;
        }
    }

    return true;
}


function cmm_library_read(): array
{
    $path = CMM_LIBRARY_SOURCE;

    if (
        !is_file($path)
        ||
        !is_readable($path)
    ) {
        throw new RuntimeException(
            'library_source_unavailable'
        );
    }

    $size = filesize($path);

    if (
        $size === false
        ||
        $size < 2
        ||
        $size > 2 * 1024 * 1024
    ) {
        throw new RuntimeException(
            'library_source_size_invalid'
        );
    }

    $raw = file_get_contents($path);

    if (
        $raw === false
        ||
        strlen($raw) !== $size
    ) {
        throw new RuntimeException(
            'library_source_read_failed'
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
            'library_json_invalid'
        );
    }

    if (!is_array($data)) {
        throw new RuntimeException(
            'library_document_invalid'
        );
    }

    if (
        !cmm_library_exact_keys(
            $data,
            [
                'schema',
                'summary',
                'items',
            ]
        )
    ) {
        throw new RuntimeException(
            'library_top_level_contract_invalid'
        );
    }

    if (
        ($data['schema'] ?? null)
        !==
        CMM_LIBRARY_SCHEMA
    ) {
        throw new RuntimeException(
            'library_schema_invalid'
        );
    }

    $summary = $data['summary'];
    $items = $data['items'];

    if (
        !is_array($summary)
        ||
        !cmm_library_exact_keys(
            $summary,
            [
                'total',
                'types',
                'ownership',
                'inventory_states',
            ]
        )
    ) {
        throw new RuntimeException(
            'library_summary_contract_invalid'
        );
    }

    if (
        !is_array($items)
        ||
        !array_is_list($items)
    ) {
        throw new RuntimeException(
            'library_items_invalid'
        );
    }

    if (
        !cmm_library_nonnegative_int(
            $summary['total']
        )
        ||
        $summary['total'] !== count($items)
    ) {
        throw new RuntimeException(
            'library_summary_total_invalid'
        );
    }

    foreach (
        [
            'types',
            'ownership',
            'inventory_states',
        ]
        as $section
    ) {
        if (!is_array($summary[$section])) {
            throw new RuntimeException(
                'library_summary_section_invalid'
            );
        }

        $sum = 0;

        foreach (
            $summary[$section]
            as $key => $count
        ) {
            if (
                !is_string($key)
                ||
                $key === ''
                ||
                !cmm_library_nonnegative_int(
                    $count
                )
            ) {
                throw new RuntimeException(
                    'library_summary_value_invalid'
                );
            }

            $sum += $count;
        }

        if ($sum !== $summary['total']) {
            throw new RuntimeException(
                'library_summary_identity_invalid'
            );
        }
    }


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


    $pipelineStates = [
        'IDLE',
        'REQUESTED',
        'APPROVED',
        'ARR_RECEIVED',
        'SEARCHING',
        'GRABBED',
        'QUEUED',
        'DOWNLOADING',
        'DOWNLOAD_COMPLETE',
        'IMPORTING',
        'IMPORTED',
        'JELLYFIN_SCANNING',
        'AVAILABLE',
        'FAILED',
    ];


    $idsSeen = [];

    $derivedTypes = [];
    $derivedOwnership = [];
    $derivedStates = [];


    foreach ($items as $item) {

        if (
            !is_array($item)
            ||
            !cmm_library_exact_keys(
                $item,
                [
                    'id',
                    'type',
                    'title',
                    'sort_title',
                    'ownership',
                    'inventory_state',
                    'pipeline_state',
                    'presence',
                    'ids',
                    'poster',
                    'updated_at_utc',
                    'episodes',
                ]
            )
        ) {
            throw new RuntimeException(
                'library_item_contract_invalid'
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
                'library_item_id_invalid'
            );
        }

        $idsSeen[
            (string) $item['id']
        ] = true;


        if (
            !in_array(
                $item['type'],
                [
                    'movie',
                    'series',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'library_item_type_invalid'
            );
        }


        if (
            !is_string($item['title'])
            ||
            trim($item['title']) === ''
        ) {
            throw new RuntimeException(
                'library_item_title_invalid'
            );
        }


        if (
            $item['sort_title'] !== null
            &&
            !is_string(
                $item['sort_title']
            )
        ) {
            throw new RuntimeException(
                'library_sort_title_invalid'
            );
        }


        if (
            !in_array(
                $item['ownership'],
                [
                    'ARR',
                    'MANUAL',
                    'UNKNOWN',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'library_ownership_invalid'
            );
        }


        if (
            !in_array(
                $item['inventory_state'],
                $inventoryStates,
                true
            )
        ) {
            throw new RuntimeException(
                'library_inventory_state_invalid'
            );
        }


        if (
            !in_array(
                $item['pipeline_state'],
                $pipelineStates,
                true
            )
        ) {
            throw new RuntimeException(
                'library_pipeline_state_invalid'
            );
        }


        if (
            !is_array($item['presence'])
            ||
            !cmm_library_exact_keys(
                $item['presence'],
                [
                    'arr',
                    'jellyfin',
                    'filesystem',
                ]
            )
        ) {
            throw new RuntimeException(
                'library_presence_contract_invalid'
            );
        }


        foreach (
            [
                'arr',
                'jellyfin',
                'filesystem',
            ]
            as $presenceKey
        ) {
            if (
                !is_bool(
                    $item['presence'][
                        $presenceKey
                    ]
                )
            ) {
                throw new RuntimeException(
                    'library_presence_value_invalid'
                );
            }
        }


        if (
            !is_array($item['ids'])
            ||
            !cmm_library_exact_keys(
                $item['ids'],
                [
                    'tmdb',
                    'tvdb',
                    'imdb',
                    'radarr',
                    'sonarr',
                    'jellyfin',
                ]
            )
        ) {
            throw new RuntimeException(
                'library_ids_contract_invalid'
            );
        }


        foreach (
            [
                'tmdb',
                'tvdb',
                'radarr',
                'sonarr',
            ]
            as $integerId
        ) {
            if (
                !cmm_library_optional_positive_int(
                    $item['ids'][$integerId]
                )
            ) {
                throw new RuntimeException(
                    'library_integer_id_invalid'
                );
            }
        }


        $imdb = $item['ids']['imdb'];

        if (
            $imdb !== null
            &&
            (
                !is_string($imdb)
                ||
                preg_match(
                    '/^tt\d+$/',
                    $imdb
                ) !== 1
            )
        ) {
            throw new RuntimeException(
                'library_imdb_id_invalid'
            );
        }


        $jellyfin = $item['ids']['jellyfin'];

        if (
            $jellyfin !== null
            &&
            (
                !is_string($jellyfin)
                ||
                preg_match(
                    '/^[0-9a-fA-F]{32}$/',
                    $jellyfin
                ) !== 1
            )
        ) {
            throw new RuntimeException(
                'library_jellyfin_id_invalid'
            );
        }


        if (
            !is_array($item['poster'])
            ||
            !cmm_library_exact_keys(
                $item['poster'],
                [
                    'source',
                    'ref',
                ]
            )
        ) {
            throw new RuntimeException(
                'library_poster_contract_invalid'
            );
        }


        if (
            $item['poster']['source'] !== null
            &&
            !is_string(
                $item['poster']['source']
            )
        ) {
            throw new RuntimeException(
                'library_poster_source_invalid'
            );
        }


        if (
            !cmm_library_validate_poster_ref(
                $item['poster']['ref']
            )
        ) {
            throw new RuntimeException(
                'library_poster_ref_invalid'
            );
        }


        if (
            !cmm_library_valid_timestamp(
                $item['updated_at_utc']
            )
        ) {
            throw new RuntimeException(
                'library_updated_timestamp_invalid'
            );
        }


        if ($item['type'] === 'movie') {

            if ($item['episodes'] !== null) {
                throw new RuntimeException(
                    'library_movie_episodes_invalid'
                );
            }

        } else {

            $episodes = $item['episodes'];

            if (
                !is_array($episodes)
                ||
                !cmm_library_exact_keys(
                    $episodes,
                    [
                        'source',
                        'total',
                        'filesystem_present',
                        'filesystem_absent',
                    ]
                )
            ) {
                throw new RuntimeException(
                    'library_episodes_contract_invalid'
                );
            }


            if ($episodes['source'] === 'SONARR') {

                foreach (
                    [
                        'total',
                        'filesystem_present',
                        'filesystem_absent',
                    ]
                    as $episodeCount
                ) {
                    if (
                        !cmm_library_nonnegative_int(
                            $episodes[$episodeCount]
                        )
                    ) {
                        throw new RuntimeException(
                            'library_episode_count_invalid'
                        );
                    }
                }


                if (
                    $episodes['filesystem_present']
                    +
                    $episodes['filesystem_absent']
                    !==
                    $episodes['total']
                ) {
                    throw new RuntimeException(
                        'library_episode_identity_invalid'
                    );
                }

            } elseif (
                $episodes['source']
                ===
                'UNAVAILABLE'
            ) {

                if (
                    $episodes['total'] !== null
                    ||
                    $episodes['filesystem_present'] !== null
                    ||
                    $episodes['filesystem_absent'] !== null
                ) {
                    throw new RuntimeException(
                        'library_unavailable_episode_semantics_invalid'
                    );
                }

            } else {

                throw new RuntimeException(
                    'library_episode_source_invalid'
                );
            }
        }


        $derivedTypes[
            $item['type']
        ] = (
            $derivedTypes[
                $item['type']
            ]
            ??
            0
        ) + 1;


        $derivedOwnership[
            $item['ownership']
        ] = (
            $derivedOwnership[
                $item['ownership']
            ]
            ??
            0
        ) + 1;


        $derivedStates[
            $item['inventory_state']
        ] = (
            $derivedStates[
                $item['inventory_state']
            ]
            ??
            0
        ) + 1;
    }


    ksort($derivedTypes);
    ksort($derivedOwnership);
    ksort($derivedStates);


    $summaryTypes =
        $summary['types'];

    $summaryOwnership =
        $summary['ownership'];

    $summaryStates =
        $summary['inventory_states'];


    ksort($summaryTypes);
    ksort($summaryOwnership);
    ksort($summaryStates);


    if ($summaryTypes !== $derivedTypes) {
        throw new RuntimeException(
            'library_types_summary_mismatch'
        );
    }

    if ($summaryOwnership !== $derivedOwnership) {
        throw new RuntimeException(
            'library_ownership_summary_mismatch'
        );
    }

    if ($summaryStates !== $derivedStates) {
        throw new RuntimeException(
            'library_inventory_summary_mismatch'
        );
    }


    return [
        'raw' => $raw,
        'data' => $data,
        'items' => count($items),
    ];
}
