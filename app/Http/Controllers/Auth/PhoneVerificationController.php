<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PhoneVerificationController extends Controller
{
    public function __construct(
        private PhoneVerificationService $phoneVerificationService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Send phone verification code
     */
    public function send(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->phone) {
            return response()->json([
                'message' => __('Phone number is required for verification.'),
            ], 400);
        }

        if ($user->phone_verified_at) {
            return response()->json([
                'message' => __('Phone number is already verified.'),
            ], 400);
        }

        $sent = $this->phoneVerificationService->sendVerificationCode($user);

        if (!$sent) {
            return response()->json([
                'message' => __('Failed to send verification code.'),
            ], 500);
        }

        return response()->json([
            'message' => __('Verification code sent to your phone.'),
        ]);
    }

    /**
     * Verify phone verification code
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if ($user->phone_verified_at) {
            return response()->json([
                'message' => __('Phone number is already verified.'),
            ], 400);
        }

        $verified = $this->phoneVerificationService->verifyCode($user, $request->code);

        if (!$verified) {
            $remaining = $this->phoneVerificationService->getRemainingAttempts($user);
            
            return response()->json([
                'message' => __('Invalid verification code.'),
                'remaining_attempts' => $remaining,
            ], 400);
        }

        return response()->json([
            'message' => __('Phone number verified successfully.'),
            'user' => $user->fresh(['company']),
        ]);
    }

    /**
     * Check verification status
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'is_verified' => !is_null($user->phone_verified_at),
            'phone' => $user->phone,
            'has_pending_verification' => $this->phoneVerificationService->isVerificationPending($user),
            'remaining_attempts' => $this->phoneVerificationService->getRemainingAttempts($user),
        ]);
    }
}