<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Checks;

use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\AegisCsp\CspService;
use Wobqqq\AegisCsp\Enums\Directive;
use Wobqqq\AegisCsp\Enums\Scope;
use Wobqqq\AegisCsp\Policy\CspSettings;
use Wobqqq\AegisCsp\Policy\Header;
use Wobqqq\AegisCsp\Policy\Source;
use Wobqqq\AegisCsp\Support\Message;

/**
 * How much of the site's policy actually stops an injected script.
 */
final readonly class PolicyStrengthCheck implements Check
{
    public const string KEY = 'csp-strength';

    private const array WEAK_SCRIPT_SOURCES = [Source::UNSAFE_INLINE, Source::UNSAFE_EVAL, '*', 'https:', 'http:', 'data:'];

    public function __construct(private CspService $csp)
    {
    }

    #[Override]
    public function run(): CheckResult
    {
        $settings = $this->csp->settings();
        $label = Message::get('aegis-csp::csp.check.label');

        if (!$settings->header(Scope::SITE) instanceof Header) {
            return CheckResult::info(self::KEY, $label, Message::get('aegis-csp::csp.check.not_sent'));
        }

        $weaknesses = [];
        $scripts = $this->effective($settings, Directive::SCRIPT_SRC);
        $weak = array_values(array_intersect($scripts, self::WEAK_SCRIPT_SOURCES));

        if ($weak !== []) {
            $weaknesses[] = Message::get('aegis-csp::csp.check.weak_scripts', ['sources' => implode(' ', $weak)]);
        }

        if ($this->effective($settings, Directive::OBJECT_SRC) !== [Source::NONE]) {
            $weaknesses[] = Message::get('aegis-csp::csp.check.objects');
        }

        if ($settings->sources(Scope::SITE, Directive::BASE_URI) === []) {
            $weaknesses[] = Message::get('aegis-csp::csp.check.base_uri');
        }

        if ($settings->sources(Scope::SITE, Directive::FRAME_ANCESTORS) === []) {
            $weaknesses[] = Message::get('aegis-csp::csp.check.frame_ancestors');
        }

        if ($weaknesses === []) {
            return CheckResult::pass(self::KEY, $label, Message::get('aegis-csp::csp.check.pass'));
        }

        return CheckResult::warn(self::KEY, $label, implode(' ', $weaknesses));
    }

    /**
     * @return list<string>
     */
    private function effective(CspSettings $settings, Directive $directive): array
    {
        $sources = $settings->sources(Scope::SITE, $directive);

        return $sources !== [] ? $sources : $settings->sources(Scope::SITE, Directive::DEFAULT_SRC);
    }
}
