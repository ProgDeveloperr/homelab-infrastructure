<?php

declare(strict_types=1);

const CMM_POSTER_API_VERSION = '1.0.0';

const CMM_POSTER_SCHEMA =
    'cmm.posters.v1';

if (!defined('CMM_POSTER_ROOT')) {
    define(
        'CMM_POSTER_ROOT',
        rtrim(
            getenv('CMM_POSTER_ROOT') ?: '/var/lib/cmm/posters',
            '/'
        )
    );
}

if (!defined('CMM_POSTER_MANIFEST')) {
    define(
        'CMM_POSTER_MANIFEST',
        CMM_POSTER_ROOT . '/index-v1.json'
    );
}

const CMM_POSTER_MAX_BYTES =
    8 * 1024 * 1024;


final class CmmPosterCacheUnavailable
    extends RuntimeException
{
}


final class CmmPosterFileMissing
    extends RuntimeException
{
}


function cmm_poster_json_error(
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

    header(
        'X-CMM-API-Version: '
        . CMM_POSTER_API_VERSION
    );


    $raw = json_encode(
        [
            'ok' => false,
            'error' => $code,
        ],
        JSON_UNESCAPED_SLASHES
        |
        JSON_UNESCAPED_UNICODE
        |
        JSON_THROW_ON_ERROR
    );


    header(
        'Content-Length: '
        . (string) strlen($raw)
    );


    echo $raw;
    exit;
}


function cmm_poster_request_id(): string
{
    $query =
        $_SERVER['QUERY_STRING']
        ?? '';


    if ($query === '') {
        cmm_poster_json_error(
            400,
            'missing_id'
        );
    }


    if (
        preg_match(
            '/^id=([1-9][0-9]*)$/D',
            $query,
            $match
        ) !== 1
    ) {
        cmm_poster_json_error(
            400,
            'invalid_id'
        );
    }


    return $match[1];
}


function cmm_poster_manifest(): array
{
    if (
        !is_file(CMM_POSTER_MANIFEST)
        ||
        !is_readable(CMM_POSTER_MANIFEST)
    ) {
        throw new CmmPosterCacheUnavailable(
            'manifest_unavailable'
        );
    }


    $raw =
        file_get_contents(
            CMM_POSTER_MANIFEST
        );


    if ($raw === false) {
        throw new CmmPosterCacheUnavailable(
            'manifest_read_failed'
        );
    }


    try {

        $manifest =
            json_decode(
                $raw,
                true,
                32,
                JSON_THROW_ON_ERROR
            );

    } catch (JsonException $e) {

        throw new CmmPosterCacheUnavailable(
            'manifest_json_invalid',
            0,
            $e
        );
    }


    if (
        !is_array($manifest)
        ||
        ($manifest['schema'] ?? null)
            !== CMM_POSTER_SCHEMA
        ||
        !is_array(
            $manifest['items']
            ?? null
        )
    ) {
        throw new CmmPosterCacheUnavailable(
            'manifest_contract_invalid'
        );
    }


    return $manifest;
}


function cmm_poster_resolve(
    string $id
): ?array {

    $manifest =
        cmm_poster_manifest();

    $items =
        $manifest['items'];


    if (
        !array_key_exists(
            $id,
            $items
        )
    ) {
        return null;
    }


    $entry =
        $items[$id];


    if (!is_array($entry)) {
        throw new CmmPosterCacheUnavailable(
            'entry_invalid'
        );
    }


    $filename =
        $entry['file']
        ?? null;

    $contentType =
        $entry['content_type']
        ?? null;

    $bytes =
        $entry['bytes']
        ?? null;

    $sha256 =
        $entry['sha256']
        ?? null;


    if (
        !is_string($filename)
        ||
        preg_match(
            '/^[1-9][0-9]*-[a-f0-9]{64}\.(jpg|png|webp)$/D',
            $filename
        ) !== 1
        ||
        strpos(
            $filename,
            $id . '-'
        ) !== 0
    ) {
        throw new CmmPosterCacheUnavailable(
            'filename_invalid'
        );
    }


    if (
        !is_string($contentType)
        ||
        !in_array(
            $contentType,
            [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
            true
        )
    ) {
        throw new CmmPosterCacheUnavailable(
            'content_type_invalid'
        );
    }


    if (
        !is_int($bytes)
        ||
        $bytes <= 0
        ||
        $bytes > CMM_POSTER_MAX_BYTES
    ) {
        throw new CmmPosterCacheUnavailable(
            'size_invalid'
        );
    }


    if (
        !is_string($sha256)
        ||
        preg_match(
            '/^[a-f0-9]{64}$/D',
            $sha256
        ) !== 1
    ) {
        throw new CmmPosterCacheUnavailable(
            'sha256_invalid'
        );
    }


    $rootReal =
        realpath(
            CMM_POSTER_ROOT
        );


    if ($rootReal === false) {
        throw new CmmPosterCacheUnavailable(
            'root_unavailable'
        );
    }


    $path =
        CMM_POSTER_ROOT
        . '/'
        . $filename;


    if (!file_exists($path)) {
        throw new CmmPosterFileMissing(
            'poster_missing'
        );
    }


    if (is_link($path)) {
        throw new CmmPosterCacheUnavailable(
            'poster_symlink_denied'
        );
    }


    $pathReal =
        realpath($path);


    if ($pathReal === false) {
        throw new CmmPosterFileMissing(
            'poster_missing'
        );
    }


    if (
        dirname($pathReal)
        !==
        $rootReal
    ) {
        throw new CmmPosterCacheUnavailable(
            'poster_path_escape'
        );
    }


    if (
        !is_file($pathReal)
        ||
        !is_readable($pathReal)
    ) {
        throw new CmmPosterFileMissing(
            'poster_unavailable'
        );
    }


    $actualBytes =
        filesize($pathReal);


    if (
        $actualBytes === false
        ||
        $actualBytes !== $bytes
    ) {
        throw new CmmPosterCacheUnavailable(
            'poster_size_mismatch'
        );
    }


    $actualSha =
        hash_file(
            'sha256',
            $pathReal
        );


    if (
        !is_string($actualSha)
        ||
        !hash_equals(
            $sha256,
            $actualSha
        )
    ) {
        throw new CmmPosterCacheUnavailable(
            'poster_hash_mismatch'
        );
    }


    return [
        'id' => $id,
        'path' => $pathReal,
        'content_type' => $contentType,
        'bytes' => $bytes,
        'sha256' => $sha256,
    ];
}
