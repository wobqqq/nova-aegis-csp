<?php

declare(strict_types=1);

return [
    'label' => 'Content Security Policy',
    'description' => 'A Content-Security-Policy header that tells browsers which scripts, styles, images and frames the pages may load, for the site, the Nova panel or both.',

    'fields' => [
        'enabled' => 'Send the Content-Security-Policy header',
        'apply_to' => 'Send it to',
        'apply_to_site' => 'The site',
        'apply_to_nova' => 'Nova',
        'apply_to_both' => 'The site and Nova',
        'report_only' => 'Report only',
        'report_uri' => 'Report URI',
        'source' => 'Source',
        'directive_site' => 'Site: :directive',
        'directive_nova' => 'Nova: :directive',
    ],

    'help' => [
        'enabled' => 'Run php artisan aegis:csp:disable if the policy breaks the pages.',
        'apply_to' => 'Nova has its own policy below: it needs inline scripts and the template compiler of its tools.',
        'report_only' => 'Browsers report what the policy would block (in the console, or to the report URI) without blocking it. Use it to try a policy first.',
        'report_uri' => 'Where browsers send violation reports: an http(s) URL or a path on this site. Leave empty for none.',
        'directives_site' => "The site's policy. One source per row: 'self', 'none', 'unsafe-inline', a hash, a scheme such as https: or data:, or a host such as https://cdn.example.com. An empty directive is not sent.",
        'directives_nova' => "The Nova panel's policy. Nova always keeps 'self', 'unsafe-inline' and 'unsafe-eval' for scripts, 'self' and 'unsafe-inline' for styles, 'self' and data: for images and 'self' for fonts, its API and forms.",
    ],

    'validation' => [
        'source' => "Each row is one source expression, such as 'self', https://cdn.example.com or data:, without spaces, \";\" or \",\". Nonces are refused: a fixed nonce protects nothing.",
        'report_uri' => 'The report URI is an http(s) URL or a path starting with "/", without spaces, quotes, ";" or ",".',
        'none' => "'none' cannot be combined with other sources.",
        'nova_missing' => 'Nova cannot work without :sources here.',
        'nova_conflicting' => 'Nova cannot work with :sources here: it disables a source Nova needs.',
    ],

    'status' => [
        'off' => 'No Content-Security-Policy is sent.',
        'on' => 'The Content-Security-Policy is enforced on :target.',
        'report_only' => 'The Content-Security-Policy only reports on :target, it blocks nothing.',
        'target_site' => 'the site',
        'target_nova' => 'Nova',
        'target_both' => 'the site and Nova',
    ],

    'check' => [
        'label' => 'Content Security Policy strength',
        'not_sent' => 'The site sends no Content-Security-Policy.',
        'pass' => "The site's policy limits scripts, plugins, the base URL and framing.",
        'weak_scripts' => 'Scripts are allowed from :sources, so an injected script still runs.',
        'objects' => "object-src is not 'none'.",
        'base_uri' => 'base-uri is not set.',
        'frame_ancestors' => 'frame-ancestors is not set, so other sites may frame the pages.',
    ],
];
