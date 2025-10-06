<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limiter = 'api'): Response
    {
        $key = $this->resolveRequestSignature($request, $limiter);
        
        // Define rate limits based on limiter type
        $limits = $this->getLimits($limiter, $request);
        
        foreach ($limits as $limit) {
            $executed = RateLimiter::attempt(
                $key . ':' . $limit['key'],
                $limit['attempts'],
                function () {
                    // This callback is executed if the rate limit is not exceeded
                },
                $limit['decay']
            );

            if (!$executed) {
                return $this->buildResponse($request, $key . ':' . $limit['key'], $limit['attempts'], $limit['decay']);
            }
        }

        return $next($request);
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $limiter): string
    {
        $user = $request->user();
        
        if ($user) {
            // Authenticated user rate limiting
            return "user:{$user->id}:{$limiter}";
        }
        
        // Guest rate limiting by IP
        return "ip:{$request->ip()}:{$limiter}";
    }

    /**
     * Get rate limits for different limiter types.
     */
    protected function getLimits(string $limiter, Request $request): array
    {
        $user = $request->user();
        $isPremium = $user && $user->company && $user->company->subscription_plan === 'premium';
        
        switch ($limiter) {
            case 'api':
                return [
                    [
                        'key' => 'minute',
                        'attempts' => $isPremium ? 120 : 60,
                        'decay' => 60
                    ],
                    [
                        'key' => 'hour',
                        'attempts' => $isPremium ? 2000 : 1000,
                        'decay' => 3600
                    ]
                ];
                
            case 'auth':
                return [
                    [
                        'key' => 'minute',
                        'attempts' => 5,
                        'decay' => 60
                    ],
                    [
                        'key' => 'hour',
                        'attempts' => 20,
                        'decay' => 3600
                    ]
                ];
                
            case 'upload':
                return [
                    [
                        'key' => 'minute',
                        'attempts' => $isPremium ? 20 : 10,
                        'decay' => 60
                    ],
                    [
                        'key' => 'hour',
                        'attempts' => $isPremium ? 200 : 100,
                        'decay' => 3600
                    ]
                ];
                
            case 'reports':
                return [
                    [
                        'key' => 'minute',
                        'attempts' => $isPremium ? 10 : 5,
                        'decay' => 60
                    ],
                    [
                        'key' => 'hour',
                        'attempts' => $isPremium ? 50 : 25,
                        'decay' => 3600
                    ]
                ];
                
            case 'bulk':
                return [
                    [
                        'key' => 'minute',
                        'attempts' => $isPremium ? 5 : 2,
                        'decay' => 60
                    ],
                    [
                        'key' => 'hour',
                        'attempts' => $isPremium ? 20 : 10,
                        'decay' => 3600
                    ]
                ];
                
            default:
                return [
                    [
                        'key' => 'minute',
                        'attempts' => 60,
                        'decay' => 60
                    ]
                ];
        }
    }

    /**
     * Build the rate limit response.
     */
    protected function buildResponse(Request $request, string $key, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            'Retry-After' => $retryAfter,
        ];
        
        $message = "Too many requests. Please try again in {$retryAfter} seconds.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => $message,
                'retry_after' => $retryAfter,
            ], 429, $headers);
        }
        
        return response($message, 429, $headers);
    }
}