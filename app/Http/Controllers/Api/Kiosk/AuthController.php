<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAssignment;
use App\Models\RvmMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * AuthController
 * 
 * Handles technician authentication for maintenance mode access.
 * Uses PIN-based authentication with rate limiting for security.
 * 
 * @package App\Http\Controllers\Api\Kiosk
 */
class AuthController extends Controller
{
    /**
     * Maximum PIN verification attempts per machine per hour.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Rate limit decay time in seconds (1 hour).
     */
    private const DECAY_SECONDS = 3600;

    /**
     * @OA\Post(
     *     path="/api/v1/kiosk/auth/pin",
     *     summary="Verify technician PIN",
     *     tags={"Kiosk"},
     *     @OA\Parameter(
     *         name="X-Machine-UUID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pin"},
     *             @OA\Property(property="pin", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="technician_name", type="string"),
     *                 @OA\Property(property="permissions", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid PIN"),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        // Rate limiting check
        $rateLimitKey = "kiosk_pin:{$machineUuid}";
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            Log::warning('PIN verification rate limit exceeded', [
                'machine_uuid' => $machineUuid,
                'retry_after' => $seconds,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
                'retry_after' => $seconds,
            ], 429);
        }

        // Get machine
        $machine = RvmMachine::where('uuid', $machineUuid)->first();
        
        if (!$machine) {
            RateLimiter::hit($rateLimitKey, self::DECAY_SECONDS);
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan.',
            ], 404);
        }

        // Find valid technician assignment with matching PIN
        $assignment = TechnicianAssignment::where('rvm_machine_id', $machine->id)
            ->where('status', 'active')
            ->get()
            ->first(function ($assignment) use ($validated) {
                // Check PIN match (plaintext comparison)
                if ($assignment->access_pin !== $validated['pin']) {
                    return false;
                }
                // Check PIN expiration
                if ($assignment->pin_expires_at && $assignment->pin_expires_at < now()) {
                    return false;
                }
                return true;
            });

        if (!$assignment) {
            RateLimiter::hit($rateLimitKey, self::DECAY_SECONDS);
            
            $remainingAttempts = self::MAX_ATTEMPTS - RateLimiter::attempts($rateLimitKey);
            
            Log::info('Invalid PIN attempt', [
                'machine_uuid' => $machineUuid,
                'remaining_attempts' => $remainingAttempts,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PIN tidak valid.',
                'remaining_attempts' => max(0, $remainingAttempts),
            ], 401);
        }

        // Clear rate limiter on successful authentication
        RateLimiter::clear($rateLimitKey);

        // Update last access timestamp
        $assignment->update(['last_accessed_at' => now()]);

        Log::info('Technician authenticated via PIN', [
            'machine_uuid' => $machineUuid,
            'technician_id' => $assignment->technician_id,
            'assignment_id' => $assignment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Otentikasi berhasil. Selamat datang, Teknisi.',
            'data' => [
                'technician_name' => $assignment->technician->name ?? 'Teknisi',
                'assignment_id' => $assignment->id,
                'permissions' => $this->getTechnicianPermissions($assignment),
                'session_expires_at' => now()->addHours(2)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get technician permissions based on assignment.
     *
     * @param TechnicianAssignment $assignment
     * @return array<string, bool>
     */
    private function getTechnicianPermissions(TechnicianAssignment $assignment): array
    {
        // Base permissions for all technicians
        $permissions = [
            'view_logs' => true,
            'view_status' => true,
            'test_hardware' => true,
            'toggle_theme' => true,
        ];

        // Additional permissions based on assignment type or role
        if ($assignment->assignment_type === 'full_access') {
            $permissions['reboot_machine'] = true;
            $permissions['update_config'] = true;
        }

        return $permissions;
    }
}
