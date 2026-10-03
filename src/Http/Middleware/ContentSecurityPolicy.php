<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Util;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Wobqqq\AegisCsp\CspService;
use Wobqqq\AegisCsp\Enums\Scope;
use Wobqqq\AegisCsp\Policy\Header;

final readonly class ContentSecurityPolicy
{
    public function __construct(private CspService $csp)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $header = $this->csp->header(Util::isNovaRequest($request) ? Scope::NOVA : Scope::SITE);
        } catch (Throwable $throwable) {
            report($throwable);

            return $response;
        }

        if ($header instanceof Header) {
            $response->headers->set($header->name, $header->value);
        }

        return $response;
    }
}
