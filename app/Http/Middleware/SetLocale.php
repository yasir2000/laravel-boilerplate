<?php

namespace App\Http\Middleware;

use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        private LocalizationService $localizationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $this->localizationService->getUserLocale($user);
        
        App::setLocale($locale);

        // Share locale data with views
        if ($request->expectsJson()) {
            // For API requests, we'll handle this in the controller
        } else {
            // For web requests, share with Inertia
            \Inertia\Inertia::share([
                'locale' => $this->localizationService->getLocalizationData($user),
            ]);
        }

        return $next($request);
    }
}