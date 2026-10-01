<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Policy;

use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisCsp\CspModule;
use Wobqqq\AegisCsp\Enums\Directive;
use Wobqqq\AegisCsp\Enums\Scope;
use Wobqqq\AegisCsp\Enums\Target;

final readonly class CspSettings
{
    /**
     * @param array<value-of<Directive>, list<string>> $site
     * @param array<value-of<Directive>, list<string>> $nova
     */
    public function __construct(
        public bool $enabled,
        public bool $reportOnly,
        public ?string $reportUri,
        public Target $target,
        public array $site,
        public array $nova,
    ) {
    }

    /**
     * Reads the stored values again, whatever they are: an invalid source is dropped and
     * the Nova policy always keeps what Nova needs, since a stored row may predate the rules.
     *
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        $reportUri = Values::string($values, 'report_uri');
        $site = [];
        $nova = [];

        foreach (Directive::cases() as $directive) {
            $site[$directive->value] = Source::filter(Values::column($values, $directive->setting(Scope::SITE), 'source'), CspModule::MAX_SOURCES);
            $nova[$directive->value] = array_slice(NovaAllowances::apply(
                $directive,
                Source::filter(Values::column($values, $directive->setting(Scope::NOVA), 'source'), CspModule::MAX_SOURCES),
            ), 0, CspModule::MAX_SOURCES);
        }

        return new self(
            Values::bool($values, 'enabled'),
            Values::bool($values, 'report_only'),
            ReportUri::isValid($reportUri) ? $reportUri : null,
            Target::tryFrom(Values::string($values, 'apply_to')) ?? Target::SITE,
            $site,
            $nova,
        );
    }

    /**
     * @return array<string, mixed> the values in their stored shape
     */
    public function toArray(): array
    {
        $values = [
            'enabled' => $this->enabled,
            'report_only' => $this->reportOnly,
            'report_uri' => $this->reportUri,
            'apply_to' => $this->target->value,
        ];

        foreach (Directive::cases() as $directive) {
            foreach ([Scope::SITE, Scope::NOVA] as $scope) {
                $values[$directive->setting($scope)] = array_map(
                    static fn (string $source): array => ['source' => $source],
                    $this->sources($scope, $directive),
                );
            }
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    public function sources(Scope $scope, Directive $directive): array
    {
        return ($scope === Scope::NOVA ? $this->nova : $this->site)[$directive->value] ?? [];
    }

    /**
     * The header for the scope, or null when it is not sent there or has no directive.
     */
    public function header(Scope $scope): ?Header
    {
        if (!$this->enabled || !$this->target->covers($scope)) {
            return null;
        }

        $directives = [];

        foreach (Directive::cases() as $directive) {
            $sources = $this->sources($scope, $directive);

            if ($sources !== []) {
                $directives[] = $directive->value . ' ' . implode(' ', $sources);
            }
        }

        if ($directives === []) {
            return null;
        }

        if ($this->reportUri !== null) {
            $directives[] = 'report-uri ' . $this->reportUri;
        }

        return new Header($this->reportOnly ? Header::REPORT_ONLY : Header::ENFORCE, implode('; ', $directives));
    }

    public function with(bool $enabled, Target $target): self
    {
        return new self($enabled, $this->reportOnly, $this->reportUri, $target, $this->site, $this->nova);
    }
}
