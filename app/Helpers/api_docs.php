<?php

/**
 * API documentation data loaded from resources/api-docs/*.json
 */

function loadApiDocsJson(string $filename): array
{
    static $cache = [];

    if (isset($cache[$filename])) {
        return $cache[$filename];
    }

    $path = resource_path('api-docs/' . $filename);

    if (! is_readable($path)) {
        return $cache[$filename] = [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return $cache[$filename] = is_array($decoded) ? $decoded : [];
}

/**
 * @return list<array<string, mixed>>
 */
function getApiEndpoints(): array
{
    return loadApiDocsJson('endpoints.json');
}

/**
 * @return list<array<string, mixed>>
 */
function getApiPlatformPublishingDocs(): array
{
    return loadApiDocsJson('platform-publishing.json');
}

/**
 * @return list<array<string, mixed>>
 */
function getApiDocAccountNavLinks(): array
{
    return loadApiDocsJson('account-nav-links.json');
}
