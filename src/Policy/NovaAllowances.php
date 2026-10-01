<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Policy;

use Wobqqq\AegisCsp\Enums\Directive;

/**
 * What the Nova panel cannot work without: its inline boot script, the Vue template
 * compiler of the tools, inline styles, data: images and its own API. A Nova policy
 * without them would lock the administrators out of the page that turns it off.
 */
final class NovaAllowances
{
    private const array REQUIRED = [
        Directive::SCRIPT_SRC->value => [Source::SELF, Source::UNSAFE_INLINE, Source::UNSAFE_EVAL],
        Directive::STYLE_SRC->value => [Source::SELF, Source::UNSAFE_INLINE],
        Directive::IMG_SRC->value => [Source::SELF, 'data:'],
        Directive::FONT_SRC->value => [Source::SELF],
        Directive::CONNECT_SRC->value => [Source::SELF],
        Directive::FORM_ACTION->value => [Source::SELF],
    ];

    /**
     * @param list<string> $sources
     *
     * @return list<string>
     */
    public static function missing(Directive $directive, array $sources): array
    {
        return array_values(array_diff(self::REQUIRED[$directive->value] ?? [], $sources));
    }

    /**
     * Sources that make the browser ignore a required one: 'none', and a hash or
     * 'strict-dynamic', which switch 'unsafe-inline' off.
     *
     * @param list<string> $sources
     *
     * @return list<string>
     */
    public static function conflicting(Directive $directive, array $sources): array
    {
        if (!isset(self::REQUIRED[$directive->value])) {
            return [];
        }

        $inline = in_array(Source::UNSAFE_INLINE, self::REQUIRED[$directive->value], true);

        return array_values(array_filter(
            $sources,
            static fn (string $source): bool => $source === Source::NONE
                || ($inline && ($source === Source::STRICT_DYNAMIC || Source::isHash($source))),
        ));
    }

    /**
     * @param list<string> $sources
     *
     * @return list<string>
     */
    public static function apply(Directive $directive, array $sources): array
    {
        $kept = array_values(array_diff($sources, self::conflicting($directive, $sources)));

        return [...self::missing($directive, $kept), ...$kept];
    }
}
