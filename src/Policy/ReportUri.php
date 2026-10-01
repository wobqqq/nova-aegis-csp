<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Policy;

/**
 * The report-uri value: an http(s) URL or a path on this host, as one header token.
 */
final class ReportUri
{
    public static function isValid(string $uri): bool
    {
        if ($uri === '' || strlen($uri) > Source::MAX_LENGTH || preg_match('/[^\x21-\x7e]|[;,\'"\\\\]/', $uri) === 1) {
            return false;
        }

        if (str_starts_with($uri, '/')) {
            return !str_starts_with($uri, '//');
        }

        $scheme = parse_url($uri, PHP_URL_SCHEME);

        return filter_var($uri, FILTER_VALIDATE_URL) !== false
            && in_array(is_string($scheme) ? strtolower($scheme) : '', ['http', 'https'], true)
            && is_string(parse_url($uri, PHP_URL_HOST));
    }
}
