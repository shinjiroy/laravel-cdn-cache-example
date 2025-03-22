<?php

namespace App\Http\Middleware;

use App\Helpers\CacheHeaderHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (CacheHeaderHelper::isCachableResponse($request,$response)) {
            $headers = CacheHeaderHelper::generateCacheHeaders($request);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
} 