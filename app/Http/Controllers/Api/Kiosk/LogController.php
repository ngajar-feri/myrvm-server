<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\Log as SystemLog;
use App\Models\RvmMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LogController
 * 
 * Handles log viewing for the kiosk maintenance panel.
 * Logs are strictly scoped to the current machine for data isolation.
 * 
 * @package App\Http\Controllers\Api\Kiosk
 */
class LogController extends Controller
{
    /**
     * Default number of logs to return.
     */
    private const DEFAULT_LIMIT = 20;

    /**
     * Maximum number of logs allowed per request.
     */
    private const MAX_LIMIT = 50;

    /**
     * @OA\Get(
     *     path="/api/v1/kiosk/logs",
     *     summary="Get machine logs",
     *     tags={"Kiosk"},
     *     @OA\Parameter(
     *         name="X-Machine-UUID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         @OA\Schema(type="string", enum={"error", "warning", "info", "debug"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine logs",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        // Validate machine exists
        $machine = RvmMachine::where('uuid', $machineUuid)->first();
        
        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan.',
            ], 404);
        }

        // Parse query parameters
        $limit = min(
            (int) $request->query('limit', self::DEFAULT_LIMIT),
            self::MAX_LIMIT
        );
        $level = $request->query('level'); // Optional: error, warning, info
        $since = $request->query('since'); // Optional: ISO 8601 timestamp

        // Build query - ALWAYS scoped to this machine
        $query = SystemLog::where('rvm_machine_id', $machine->id)
            ->orderBy('created_at', 'desc');

        // Apply optional filters
        if ($level && in_array($level, ['error', 'warning', 'info', 'debug'])) {
            $query->where('level', $level);
        }

        if ($since) {
            try {
                $sinceDate = \Carbon\Carbon::parse($since);
                $query->where('created_at', '>=', $sinceDate);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        $logs = $query->limit($limit)->get();

        // Transform logs for kiosk display
        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'level' => $log->level,
                'level_icon' => $this->getLevelIcon($log->level),
                'message' => $log->message,
                'context' => $log->context ?? [],
                'timestamp' => $log->created_at->toIso8601String(),
                'relative_time' => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $formattedLogs,
                'count' => $formattedLogs->count(),
                'machine_name' => $machine->name,
            ],
            'meta' => [
                'limit' => $limit,
                'has_more' => $logs->count() === $limit,
            ],
        ]);
    }

    /**
     * Get log level icon for UI display.
     *
     * @param string $level
     * @return string
     */
    private function getLevelIcon(string $level): string
    {
        return match ($level) {
            'error' => '🔴',
            'warning' => '🟡',
            'info' => '🔵',
            'debug' => '⚪',
            default => '⚫',
        };
    }
}
