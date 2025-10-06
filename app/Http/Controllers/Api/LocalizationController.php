<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocalizationController extends Controller
{
    public function __construct(
        private LocalizationService $localizationService
    ) {
        $this->middleware('auth:sanctum')->except(['locales', 'translations']);
    }

    /**
     * Get supported locales
     */
    public function locales(): JsonResponse
    {
        return $this->successResponse([
            'locales' => $this->localizationService->getSupportedLocales(),
            'default' => LocalizationService::DEFAULT_LOCALE,
        ], 'Supported locales retrieved successfully');
    }

    /**
     * Get current locale data
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $localizationData = $this->localizationService->getLocalizationData($user);

        return $this->successResponse($localizationData, 'Current locale data retrieved successfully');
    }

    /**
     * Set user locale
     */
    public function setLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:' . implode(',', LocalizationService::SUPPORTED_LOCALES),
        ]);

        $user = $request->user();
        $success = $this->localizationService->setUserLocale($user, $request->locale);

        if (!$success) {
            return $this->errorResponse('Failed to set locale', 400);
        }

        return $this->successResponse([
            'locale' => $this->localizationService->getLocalizationData($user),
        ], 'Locale updated successfully');
    }

    /**
     * Get translations for a specific file
     */
    public function translations(Request $request, string $locale, string $file = null): JsonResponse
    {
        if (!in_array($locale, LocalizationService::SUPPORTED_LOCALES)) {
            return $this->errorResponse('Unsupported locale', 400);
        }

        if ($file) {
            // Get specific translation file
            $filePath = base_path("lang/{$locale}/{$file}.php");
            
            if (!file_exists($filePath)) {
                return $this->errorResponse('Translation file not found', 404);
            }

            $translations = include $filePath;
            
            return $this->successResponse([
                'file' => $file,
                'locale' => $locale,
                'translations' => $translations,
            ], 'Translations retrieved successfully');
        }

        // Get all translations for locale
        $translations = [];
        $langPath = base_path("lang/{$locale}");
        
        if (is_dir($langPath)) {
            $files = scandir($langPath);
            
            foreach ($files as $filename) {
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
                    $key = pathinfo($filename, PATHINFO_FILENAME);
                    $translations[$key] = include "{$langPath}/{$filename}";
                }
            }
        }

        return $this->successResponse([
            'locale' => $locale,
            'translations' => $translations,
        ], 'All translations retrieved successfully');
    }

    /**
     * Format number according to current locale
     */
    public function formatNumber(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|numeric',
            'decimals' => 'nullable|integer|min:0|max:10',
            'locale' => 'nullable|string|in:' . implode(',', LocalizationService::SUPPORTED_LOCALES),
        ]);

        $formatted = $this->localizationService->formatNumber(
            $request->number,
            $request->decimals ?? 0,
            $request->locale
        );

        return $this->successResponse([
            'original' => $request->number,
            'formatted' => $formatted,
            'locale' => $request->locale ?? app()->getLocale(),
        ], 'Number formatted successfully');
    }

    /**
     * Format date according to current locale
     */
    public function formatDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'format' => 'nullable|string',
            'locale' => 'nullable|string|in:' . implode(',', LocalizationService::SUPPORTED_LOCALES),
        ]);

        $date = new \DateTime($request->date);
        $formatted = $this->localizationService->formatDate(
            $date,
            $request->format,
            $request->locale
        );

        return $this->successResponse([
            'original' => $request->date,
            'formatted' => $formatted,
            'locale' => $request->locale ?? app()->getLocale(),
        ], 'Date formatted successfully');
    }
}