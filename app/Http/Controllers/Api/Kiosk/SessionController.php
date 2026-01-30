<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SessionController
 * 
 * Handles kiosk session management including token generation
 * for QR code display and guest mode activation.
 * 
 * @package App\Http\Controllers\Api\Kiosk
 */
class SessionController extends Controller
{
    /**
     * Token expiry time in seconds (5 minutes).
     */
    private const TOKEN_EXPIRY = 300;

    /**
     * @OA\Get(
     *     path="/api/v1/kiosk/session/token",
     *     summary="Get session token for QR code",
     *     tags={"Kiosk"},
     *     @OA\Parameter(
     *         name="X-Machine-UUID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session token generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="expires_at", type="string"),
     *                 @OA\Property(property="qr_content", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Missing Machine UUID")
     * )
     */
    public function getToken(Request $request): JsonResponse
    {
        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        // Generate unique session token
        $token = $this->generateSessionToken($machineUuid);
        
        // Calculate expiry timestamp
        $expiresAt = now()->addSeconds(self::TOKEN_EXPIRY);

        // Store token in cache with machine association
        Cache::put(
            "kiosk_session:{$token}",
            [
                'machine_uuid' => $machineUuid,
                'created_at' => now()->toIso8601String(),
                'status' => 'pending',
            ],
            self::TOKEN_EXPIRY
        );

        Log::info('Kiosk session token generated', [
            'machine_uuid' => $machineUuid,
            'token_prefix' => substr($token, 0, 8) . '...',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in' => self::TOKEN_EXPIRY,
                'qr_content' => $this->buildQrContent($token, $machineUuid),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/kiosk/session/guest",
     *     summary="Activate guest mode",
     *     tags={"Kiosk"},
     *     @OA\Parameter(
     *         name="X-Machine-UUID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Guest mode activated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="session_id", type="string"),
     *                 @OA\Property(property="type", type="string", example="guest"),
     *                 @OA\Property(property="display_name", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function activateGuest(Request $request): JsonResponse
    {
        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        // Generate guest session ID
        $guestSessionId = 'guest_' . Str::random(16);
        
        // Store guest session
        Cache::put(
            "kiosk_guest:{$guestSessionId}",
            [
                'machine_uuid' => $machineUuid,
                'created_at' => now()->toIso8601String(),
                'type' => 'donation',
                'status' => 'active',
            ],
            3600 // 1 hour expiry for guest sessions
        );

        Log::info('Guest session activated', [
            'machine_uuid' => $machineUuid,
            'guest_session_id' => $guestSessionId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mode tamu diaktifkan. Silakan masukkan botol Anda.',
            'data' => [
                'session_id' => $guestSessionId,
                'type' => 'guest',
                'display_name' => 'Tamu Dermawan',
            ],
        ]);
    }

    /**
     * Generate a unique session token.
     *
     * @param string $machineUuid
     * @return string
     */
    private function generateSessionToken(string $machineUuid): string
    {
        return hash('sha256', $machineUuid . Str::random(32) . microtime(true));
    }

    /**
     * Build QR code content with machine and token info.
     *
     * @param string $token
     * @param string $machineUuid
     * @return string
     */
    private function buildQrContent(string $token, string $machineUuid): string
    {
        // QR content format: myrvm://session/{token}?m={machineUuid}
        return "myrvm://session/{$token}?m={$machineUuid}";
    }
}
