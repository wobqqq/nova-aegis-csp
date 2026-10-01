<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Wobqqq\AegisCsp\Policy\Source;

final class ValidSource implements ValidationRule
{
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !Source::isValid($value)) {
            $fail('aegis-csp::csp.validation.source')->translate();
        }
    }
}
