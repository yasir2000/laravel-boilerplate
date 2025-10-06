<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class LocalizationService
{
    const SUPPORTED_LOCALES = ['en', 'ar'];
    const DEFAULT_LOCALE = 'en';
    const RTL_LOCALES = ['ar'];

    /**
     * Set user locale
     */
    public function setUserLocale(User $user, string $locale): bool
    {
        if (!in_array($locale, self::SUPPORTED_LOCALES)) {
            return false;
        }

        // Update user preference
        $user->update(['locale' => $locale]);

        // Set application locale
        App::setLocale($locale);

        // Store in session
        Session::put('locale', $locale);

        // Cache user locale
        Cache::put("user_locale_{$user->id}", $locale, 86400); // 24 hours

        return true;
    }

    /**
     * Get user locale
     */
    public function getUserLocale(?User $user = null): string
    {
        if ($user) {
            // Check user preference
            if ($user->locale && in_array($user->locale, self::SUPPORTED_LOCALES)) {
                return $user->locale;
            }

            // Check cache
            $cachedLocale = Cache::get("user_locale_{$user->id}");
            if ($cachedLocale && in_array($cachedLocale, self::SUPPORTED_LOCALES)) {
                return $cachedLocale;
            }
        }

        // Check session
        $sessionLocale = Session::get('locale');
        if ($sessionLocale && in_array($sessionLocale, self::SUPPORTED_LOCALES)) {
            return $sessionLocale;
        }

        // Check browser preference
        $browserLocale = $this->getBrowserLocale();
        if ($browserLocale && in_array($browserLocale, self::SUPPORTED_LOCALES)) {
            return $browserLocale;
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * Check if locale is RTL
     */
    public function isRtl(string $locale = null): bool
    {
        $locale = $locale ?? App::getLocale();
        return in_array($locale, self::RTL_LOCALES);
    }

    /**
     * Get locale direction
     */
    public function getDirection(string $locale = null): string
    {
        return $this->isRtl($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Get all supported locales with their metadata
     */
    public function getSupportedLocales(): array
    {
        return [
            'en' => [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'flag' => '🇺🇸',
            ],
            'ar' => [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'flag' => '🇸🇦',
            ],
        ];
    }

    /**
     * Get browser preferred locale
     */
    private function getBrowserLocale(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            $locale = strtolower(trim(explode(';', $language)[0]));
            
            // Extract primary language code
            $primaryCode = explode('-', $locale)[0];
            
            if (in_array($primaryCode, self::SUPPORTED_LOCALES)) {
                return $primaryCode;
            }
        }

        return null;
    }

    /**
     * Get localized data for frontend
     */
    public function getLocalizationData(?User $user = null): array
    {
        $currentLocale = $this->getUserLocale($user);
        App::setLocale($currentLocale);

        return [
            'current_locale' => $currentLocale,
            'direction' => $this->getDirection($currentLocale),
            'is_rtl' => $this->isRtl($currentLocale),
            'supported_locales' => $this->getSupportedLocales(),
            'translations' => $this->getTranslationsForFrontend($currentLocale),
        ];
    }

    /**
     * Get essential translations for frontend
     */
    private function getTranslationsForFrontend(string $locale): array
    {
        $translations = [];
        
        // Load essential translation files
        $files = ['auth', 'notifications', 'workflow', 'common'];
        
        foreach ($files as $file) {
            $filePath = base_path("lang/{$locale}/{$file}.php");
            if (file_exists($filePath)) {
                $translations[$file] = include $filePath;
            }
        }

        return $translations;
    }

    /**
     * Format date according to locale
     */
    public function formatDate(\DateTime $date, string $format = null, string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        
        // Use appropriate date format for locale
        if (!$format) {
            $format = match ($locale) {
                'ar' => 'd/m/Y',
                default => 'm/d/Y',
            };
        }

        return $date->format($format);
    }

    /**
     * Format number according to locale
     */
    public function formatNumber($number, int $decimals = 0, string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        
        return match ($locale) {
            'ar' => number_format($number, $decimals, '٫', '٬'),
            default => number_format($number, $decimals, '.', ','),
        };
    }
}