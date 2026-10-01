<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisCsp\Enums\Directive;
use Wobqqq\AegisCsp\Enums\Scope;
use Wobqqq\AegisCsp\Policy\NovaAllowances;
use Wobqqq\AegisCsp\Policy\Source;

/**
 * The rules that hold for a directive's list as a whole.
 */
final readonly class ValidSourceList implements ValidationRule
{
    /** Runs on an empty list too: an empty Nova directive falls back to default-src. */
    public bool $implicit;

    public function __construct(private Scope $scope, private Directive $directive)
    {
        $this->implicit = true;
    }

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sources = Values::column(['rows' => $value], 'rows', 'source');

        if (in_array(Source::NONE, $sources, true) && count($sources) > 1) {
            $fail('aegis-csp::csp.validation.none')->translate();
        }

        if ($this->scope !== Scope::NOVA) {
            return;
        }

        $missing = NovaAllowances::missing($this->directive, $sources);
        $conflicting = NovaAllowances::conflicting($this->directive, $sources);

        if ($missing !== []) {
            $fail('aegis-csp::csp.validation.nova_missing')->translate(['sources' => implode(' ', $missing)]);
        }

        if ($conflicting !== []) {
            $fail('aegis-csp::csp.validation.nova_conflicting')->translate(['sources' => implode(' ', $conflicting)]);
        }
    }
}
