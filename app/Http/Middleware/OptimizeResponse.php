<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimizeResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start output buffering
        if (ob_get_level() === 0) {
            ob_start();
        }

        // Process the request
        $response = $next($request);

        // Optimize JSON responses
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            // Ensure proper headers
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // Compress JSON if large
            $content = $response->getContent();
            if (strlen($content) > 1024) { // If response is larger than 1KB
                if (function_exists('gzencode') && !headers_sent()) {
                    $acceptEncoding = $request->header('Accept-Encoding', '');
                    if (strpos($acceptEncoding, 'gzip') !== false) {
                        $response->headers->set('Content-Encoding', 'gzip');
                        $response->setContent(gzencode($content));
                    }
                }
            }
        }

        // Clean and flush output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        return $response;
    }

    /**
     * Handle the response after it has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Clean up any remaining output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Force garbage collection for memory cleanup
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}
