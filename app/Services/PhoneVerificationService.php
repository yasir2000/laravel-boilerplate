<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PhoneVerificationNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PhoneVerificationService
{
    /**
     * Send phone verification code
     */
    public function sendVerificationCode(User $user): bool
    {
        if (!$user->phone) {
            return false;
        }

        // Generate 6-digit verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in cache for 10 minutes
        $key = "phone_verification:{$user->id}";
        Cache::put($key, [
            'code' => $code,
            'attempts' => 0,
            'created_at' => now(),
        ], 600); // 10 minutes

        // Send SMS notification
        $user->notify(new PhoneVerificationNotification($code));

        return true;
    }

    /**
     * Verify phone verification code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $key = "phone_verification:{$user->id}";
        $data = Cache::get($key);

        if (!$data) {
            return false;
        }

        // Check attempts limit
        if ($data['attempts'] >= 3) {
            Cache::forget($key);
            return false;
        }

        // Increment attempts
        $data['attempts']++;
        Cache::put($key, $data, 600);

        // Verify code
        if ($data['code'] === $code) {
            // Mark phone as verified
            $user->update([
                'phone_verified_at' => now(),
            ]);

            // Clear cache
            Cache::forget($key);

            return true;
        }

        return false;
    }

    /**
     * Check if phone verification is pending
     */
    public function isVerificationPending(User $user): bool
    {
        $key = "phone_verification:{$user->id}";
        return Cache::has($key);
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(User $user): int
    {
        $key = "phone_verification:{$user->id}";
        $data = Cache::get($key);

        if (!$data) {
            return 0;
        }

        return max(0, 3 - $data['attempts']);
    }
}