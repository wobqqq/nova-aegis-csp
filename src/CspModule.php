<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp;

use Illuminate\Validation\Rule;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Settings\Field;
use Wobqqq\AegisCsp\Enums\Directive;
use Wobqqq\AegisCsp\Enums\Scope;
use Wobqqq\AegisCsp\Enums\Target;
use Wobqqq\AegisCsp\Policy\CspSettings;
use Wobqqq\AegisCsp\Policy\Source;
use Wobqqq\AegisCsp\Support\Message;
use Wobqqq\AegisCsp\Validation\ValidReportUri;
use Wobqqq\AegisCsp\Validation\ValidSource;
use Wobqqq\AegisCsp\Validation\ValidSourceList;

final class CspModule implements Module
{
    public const string KEY = 'csp';

    public const int MAX_SOURCES = 50;

    /**
     * Loose enough that a typical site keeps working when the policy is switched on.
     */
    private const array SITE = [
        'default-src' => [Source::SELF],
        'script-src' => [Source::SELF, Source::UNSAFE_INLINE, 'https:'],
        'style-src' => [Source::SELF, Source::UNSAFE_INLINE, 'https:'],
        'img-src' => [Source::SELF, 'data:', 'blob:', 'https:'],
        'font-src' => [Source::SELF, 'data:', 'https:'],
        'connect-src' => [Source::SELF, 'https:'],
        'media-src' => [Source::SELF, 'blob:', 'https:'],
        'frame-src' => [Source::SELF, 'https:'],
        'object-src' => [Source::NONE],
        'base-uri' => [Source::SELF],
        'form-action' => [Source::SELF],
        'frame-ancestors' => [Source::NONE],
    ];

    private const array NOVA = [
        'default-src' => [Source::SELF],
        'script-src' => [Source::SELF, Source::UNSAFE_INLINE, Source::UNSAFE_EVAL],
        'style-src' => [Source::SELF, Source::UNSAFE_INLINE],
        'img-src' => [Source::SELF, 'data:', 'blob:', 'https:'],
        'font-src' => [Source::SELF, 'data:'],
        'connect-src' => [Source::SELF],
        'media-src' => [Source::SELF, 'blob:'],
        'frame-src' => [Source::SELF],
        'object-src' => [Source::NONE],
        'base-uri' => [Source::SELF],
        'form-action' => [Source::SELF],
        'frame-ancestors' => [Source::SELF],
    ];

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function label(): string
    {
        return Message::get('aegis-csp::csp.label');
    }

    #[Override]
    public function description(): string
    {
        return Message::get('aegis-csp::csp.description');
    }

    #[Override]
    public function defaults(): array
    {
        $values = [
            'enabled' => false,
            'report_only' => false,
            'report_uri' => null,
            'apply_to' => Target::SITE->value,
        ];

        foreach ([Scope::SITE->value => self::SITE, Scope::NOVA->value => self::NOVA] as $scope => $policy) {
            foreach ($policy as $directive => $sources) {
                $values[Directive::from($directive)->setting(Scope::from($scope))] = array_map(
                    static fn (string $source): array => ['source' => $source],
                    $sources,
                );
            }
        }

        return $values;
    }

    #[Override]
    public function rules(): array
    {
        $rules = [
            'enabled' => ['required', 'boolean'],
            'report_only' => ['required', 'boolean'],
            'report_uri' => ['nullable', 'string', 'max:' . Source::MAX_LENGTH, new ValidReportUri()],
            'apply_to' => ['required', 'string', Rule::enum(Target::class)],
        ];

        foreach ([Scope::SITE, Scope::NOVA] as $scope) {
            foreach (Directive::cases() as $directive) {
                $key = $directive->setting($scope);

                $rules[$key] = ['present', 'array', 'max:' . self::MAX_SOURCES, new ValidSourceList($scope, $directive)];
                $rules[$key . '.*'] = ['array:source'];
                $rules[$key . '.*.source'] = ['nullable', 'string', 'max:' . Source::MAX_LENGTH, new ValidSource()];
            }
        }

        return $rules;
    }

    #[Override]
    public function fields(): array
    {
        $field = static fn (string $name): string => Message::get('aegis-csp::csp.fields.' . $name);
        $help = static fn (string $name): string => Message::get('aegis-csp::csp.help.' . $name);
        $column = [Field::text('source', $field('source'), placeholder: "'self'")];

        $fields = [
            Field::toggle('enabled', $field('enabled'), $help('enabled')),
            Field::select('apply_to', $field('apply_to'), [
                Target::SITE->value => $field('apply_to_site'),
                Target::NOVA->value => $field('apply_to_nova'),
                Target::BOTH->value => $field('apply_to_both'),
            ], $help('apply_to')),
            Field::toggle('report_only', $field('report_only'), $help('report_only')),
            Field::text('report_uri', $field('report_uri'), $help('report_uri'), '/csp-report'),
        ];

        foreach ([Scope::SITE, Scope::NOVA] as $scope) {
            foreach (Directive::cases() as $directive) {
                $fields[] = Field::table(
                    $directive->setting($scope),
                    Message::get('aegis-csp::csp.fields.directive_' . $scope->value, ['directive' => $directive->value]),
                    $column,
                    $directive === Directive::DEFAULT_SRC ? $help('directives_' . $scope->value) : '',
                );
            }
        }

        return $fields;
    }

    #[Override]
    public function status(array $values): CheckResult
    {
        $settings = CspSettings::fromArray($values);
        $label = $this->label();

        if (!$settings->enabled) {
            return CheckResult::warn(self::KEY, $label, Message::get('aegis-csp::csp.status.off'));
        }

        $where = Message::get('aegis-csp::csp.status.target_' . $settings->target->value);

        return $settings->reportOnly
            ? CheckResult::warn(self::KEY, $label, Message::get('aegis-csp::csp.status.report_only', ['target' => $where]))
            : CheckResult::pass(self::KEY, $label, Message::get('aegis-csp::csp.status.on', ['target' => $where]));
    }
}
