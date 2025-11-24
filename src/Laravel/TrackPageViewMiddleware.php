<?php

declare(strict_types=1);

namespace Entrolytics\Laravel;

use Closure;
use Entrolytics\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for automatic page view tracking.
 *
 * Register in app/Http/Kernel.php:
 *
 *     protected $middleware = [
 *         \Entrolytics\Laravel\TrackPageViewMiddleware::class,
 *     ];
 *
 * Or for specific routes:
 *
 *     Route::middleware(['entrolytics.track'])->group(function () {
 *         // Routes with automatic page tracking
 *     });
 */
class TrackPageViewMiddleware
{
    public function __construct(
        protected Client $client
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests
        if ($request->method() !== 'GET' || $response->getStatusCode() >= 400) {
            return $response;
        }

        // Skip excluded paths
        if ($this->shouldSkip($request)) {
            return $response;
        }

        // Track page view asynchronously
        try {
            $this->trackPageView($request);
        } catch (\Throwable $e) {
            Log::warning('Entrolytics tracking failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Determine if this request should be skipped.
     */
    protected function shouldSkip(Request $request): bool
    {
        $excludedPaths = config('entrolytics.excluded_paths', [
            'api/*',
            'telescope/*',
            'horizon/*',
            '_debugbar/*',
        ]);

        foreach ($excludedPaths as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Track a page view for the request.
     */
    protected function trackPageView(Request $request): void
    {
        $websiteId = config('entrolytics.website_id');

        if (empty($websiteId)) {
            return;
        }

        $this->client->pageView([
            'website_id' => $websiteId,
            'url' => $request->fullUrl(),
            'referrer' => $request->header('Referer'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'user_id' => $request->user()?->getKey() ? (string) $request->user()->getKey() : null,
        ]);
    }
}
