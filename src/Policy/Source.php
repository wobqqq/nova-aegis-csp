<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Policy;

/**
 * One CSP source expression. Anything else (a ";", ",", whitespace, a control character,
 * a stray quote) could add a directive or split the header, so it is never sent.
 */
final class Source
{
    public const int MAX_LENGTH = 255;

    public const string NONE = "'none'";

    public const string SELF = "'self'";

    public const string UNSAFE_INLINE = "'unsafe-inline'";

    public const string UNSAFE_EVAL = "'unsafe-eval'";

    public const string STRICT_DYNAMIC = "'strict-dynamic'";

    private const array KEYWORDS = [
        self::SELF,
        self::NONE,
        self::UNSAFE_INLINE,
        self::UNSAFE_EVAL,
        self::STRICT_DYNAMIC,
        "'unsafe-hashes'",
        "'report-sample'",
        "'wasm-unsafe-eval'",
        "'inline-speculation-rules'",
    ];

    private const string HASH = "/^'sha(?:256|384|512)-[A-Za-z0-9+\\/_-]+={0,2}'\\z/D";

    private const string SCHEME = '/^[a-z][a-z0-9+.\-]*:\z/D';

    private const string HOST = '#^(?:[a-z][a-z0-9+.\-]*://)?(?:\*|(?:\*\.)?[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?)*)(?::(?:[0-9]{1,5}|\*))?(?:/[A-Za-z0-9\-._~!$&()*+=:@%/]*)?\z#iD';

    public static function isValid(string $source): bool
    {
        if ($source === '' || strlen($source) > self::MAX_LENGTH) {
            return false;
        }

        if (str_starts_with($source, "'")) {
            return in_array($source, self::KEYWORDS, true) || self::isHash($source);
        }

        // An unquoted keyword is read by browsers as a host named "self" or "none".
        if (in_array("'" . strtolower($source) . "'", self::KEYWORDS, true)) {
            return false;
        }

        return preg_match(self::SCHEME, $source) === 1 || preg_match(self::HOST, $source) === 1;
    }

    public static function isHash(string $source): bool
    {
        return preg_match(self::HASH, $source) === 1;
    }

    /**
     * The valid sources of a list, without repeats, and without 'none' when another
     * source is listed (browsers ignore it then).
     *
     * @param list<string> $sources
     *
     * @return list<string>
     */
    public static function filter(array $sources, int $limit): array
    {
        $sources = array_slice(array_values(array_unique(array_filter($sources, self::isValid(...)))), 0, $limit);

        return count($sources) > 1 ? array_values(array_diff($sources, [self::NONE])) : $sources;
    }
}
