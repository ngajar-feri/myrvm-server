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
 * MaintenanceController
 * 
 * Handles hardware command requests from the kiosk maintenance panel.
 * Commands are validated and broadcasted to the Edge device via WebSocket.
 * 
 * @package App\Http\Controllers\Api\Kiosk
 */
class MaintenanceController extends Controller
{
    /**
     * Available maintenance commands.
     */
    private const ALLOWED_COMMANDS = [
        'test_motor',
        'open_door',
        'close_door',
        'test_led',
        'test_sensor',
        'check_connection',
        'reboot_edge',
        'check_model_update',
    ];

    /**
     * Commands that require elevated permissions.
     */
    private const ELEVATED_COMMANDS = [
        'reboot_edge',
    ];

    /**
     * @OA\Post(
     *     path="/api/v1/kiosk/maintenance/command",
     *     summary="Send hardware command",
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
     *             required={"command", "assignment_id"},
     *             @OA\Property(property="command", type="string", enum={"test_motor", "open_door", "close_door", "test_led", "test_sensor", "check_connection", "reboot_edge", "check_model_update"}),
     *             @OA\Property(property="assignment_id", type="integer"),
     *             @OA\Property(property="params", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Command sent"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function sendCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', Rule::in(self::ALLOWED_COMMANDS)],
            'params' => 'nullable|array',
            'assignment_id' => 'required|integer|exists:technician_assignments,id',
        ]);

        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        // Get machine
        $machine = RvmMachine::where('uuid', $machineUuid)->first();
        
        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan.',
            ], 404);
        }

        $command = $validated['command'];
        $params = $validated['params'] ?? [];

        // Check if command requires elevated permissions
        if (in_array($command, self::ELEVATED_COMMANDS)) {
            // Verify technician has elevated access
            $hasElevatedAccess = $this->verifyElevatedAccess($validated['assignment_id']);
            
            if (!$hasElevatedAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk perintah ini.',
                ], 403);
            }
        }

        // Build command payload
        $payload = [
            'command' => $command,
            'params' => $params,
            'machine_uuid' => $machineUuid,
            'timestamp' => now()->toIso8601String(),
            'request_id' => uniqid('cmd_', true),
        ];

        // Broadcast command to Edge device via WebSocket
        // TODO: Implement actual broadcast when Laravel Reverb is configured
        // broadcast(new HardwareCommandEvent($payload))->toOthers();

        Log::info('Maintenance command sent', [
            'machine_uuid' => $machineUuid,
            'command' => $command,
            'assignment_id' => $validated['assignment_id'],
            'request_id' => $payload['request_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->getCommandMessage($command),
            'data' => [
                'request_id' => $payload['request_id'],
                'command' => $command,
                'status' => 'sent',
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/kiosk/maintenance/status",
     *     summary="Get machine health status",
     *     tags={"Kiosk"},
     *     @OA\Parameter(
     *         name="X-Machine-UUID",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Machine status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="machine", type="object"),
     *                 @OA\Property(property="connections", type="object"),
     *                 @OA\Property(property="hardware", type="object"),
     *                 @OA\Property(property="ai_model", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getStatus(Request $request): JsonResponse
    {
        $machineUuid = $request->header('X-Machine-UUID');
        
        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Machine UUID header is required.',
            ], 400);
        }

        $machine = RvmMachine::where('uuid', $machineUuid)
            ->with('edgeDevice')
            ->first();

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan.',
            ], 404);
        }

        $edgeDevice = $machine->edgeDevice;

        return response()->json([
            'success' => true,
            'data' => [
                'machine' => [
                    'name' => $machine->name,
                    'status' => $machine->status,
                    'last_activity' => $machine->updated_at?->toIso8601String(),
                ],
                'connections' => [
                    'server' => true, // Always true if we're responding
                    'edge_daemon' => $edgeDevice ? $this->isEdgeOnline($edgeDevice) : false,
                    'sensors' => $edgeDevice?->sensor_status ?? 'unknown',
                ],
                'hardware' => [
                    'door_status' => $edgeDevice?->door_status ?? 'unknown',
                    'conveyor_status' => $edgeDevice?->conveyor_status ?? 'unknown',
                    'bin_level' => $edgeDevice?->bin_level ?? 0,
                ],
                'ai_model' => [
                    'version' => $edgeDevice?->model_version ?? 'unknown',
                    'last_updated' => $edgeDevice?->model_updated_at?->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Verify if technician has elevated access.
     *
     * @param int $assignmentId
     * @return bool
     */
    private function verifyElevatedAccess(int $assignmentId): bool
    {
        // Check if assignment has full_access type
        $assignment = \App\Models\TechnicianAssignment::find($assignmentId);
        return $assignment && $assignment->assignment_type === 'full_access';
    }

    /**
     * Check if Edge device was online recently.
     *
     * @param mixed $edgeDevice
     * @return bool
     */
    private function isEdgeOnline($edgeDevice): bool
    {
        if (!$edgeDevice || !$edgeDevice->last_heartbeat_at) {
            return false;
        }

        // Consider online if heartbeat within last 2 minutes
        return $edgeDevice->last_heartbeat_at->isAfter(now()->subMinutes(2));
    }

    /**
     * Get user-friendly message for command.
     *
     * @param string $command
     * @return string
     */
    private function getCommandMessage(string $command): string
    {
        return match ($command) {
            'test_motor' => 'Perintah tes motor dikirim.',
            'open_door' => 'Perintah buka pintu dikirim.',
            'close_door' => 'Perintah tutup pintu dikirim.',
            'test_led' => 'Perintah tes LED dikirim.',
            'test_sensor' => 'Perintah tes sensor dikirim.',
            'check_connection' => 'Memeriksa koneksi...',
            'reboot_edge' => 'Perintah reboot Edge dikirim.',
            'check_model_update' => 'Memeriksa update model AI...',
            default => 'Perintah dikirim.',
        };
    }
}
