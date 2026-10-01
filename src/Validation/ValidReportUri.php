<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Wobqqq\AegisCsp\Policy\ReportUri;

final class ValidReportUri implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !ReportUri::isValid($value)) {
            $fail('aegis-csp::csp.validation.report_uri')->translate();
        }
    }
}
