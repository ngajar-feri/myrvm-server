<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\RvmMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * ConfigController
 * 
 * Handles kiosk configuration updates, primarily theme settings.
 * 
 * @package App\Http\Controllers\Api\Kiosk
 */
class ConfigController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/kiosk/config/theme",
     *     summary="Update kiosk theme",
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
     *             required={"theme_mode"},
     *             @OA\Property(property="theme_mode", type="string", enum={"auto", "light", "dark"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Theme updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme_mode' => ['required', 'string', Rule::in(['auto', 'light', 'dark'])],
        ]);

        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        $machine = RvmMachine::where('uuid', $machineUuid)->first();
        
        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan.',
            ], 404);
        }

        // Update theme preference
        $machine->update([
            'theme_mode' => $validated['theme_mode'],
        ]);

        Log::info('Kiosk theme updated', [
            'machine_uuid' => $machineUuid,
            'theme_mode' => $validated['theme_mode'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tema berhasil diperbarui.',
            'data' => [
                'theme_mode' => $validated['theme_mode'],
                'applied_theme' => $this->resolveTheme($validated['theme_mode'], $machine->timezone),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/kiosk/config",
     *     summary="Get kiosk config",
     *     tags={"Kiosk"},
     *     @OA\Parameter(
     *         name="X-Machine-UUID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine config",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getConfig(Request $request): JsonResponse
    {
        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        $machine = RvmMachine::where('uuid', $machineUuid)->first();
        
        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan.',
            ], 404);
        }

        $themeMode = $machine->theme_mode ?? 'auto';

        return response()->json([
            'success' => true,
            'data' => [
                'machine_name' => $machine->name,
                'theme_mode' => $themeMode,
                'applied_theme' => $this->resolveTheme($themeMode, $machine->timezone),
                'timezone' => $machine->timezone ?? 'Asia/Jakarta',
                'qr_refresh_interval' => 300,
            ],
        ]);
    }

    /**
     * Resolve the actual theme based on mode and time.
     *
     * @param string $mode
     * @param string|null $timezone
     * @return string
     */
    private function resolveTheme(string $mode, ?string $timezone): string
    {
        if ($mode !== 'auto') {
            return $mode;
        }

        // Auto mode: dark between 18:00 - 06:00
        $currentHour = (int) now()->setTimezone($timezone ?? 'Asia/Jakarta')->format('H');
        return ($currentHour >= 18 || $currentHour < 6) ? 'dark' : 'light';
    }
}
