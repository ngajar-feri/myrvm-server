<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RvmMachine;
use App\Models\EdgeDevice;
use App\Models\TelemetryData;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class EdgeDeviceController extends Controller
{
    /**
     * List all Edge devices.
     */
    public function index(Request $request)
    {
        $query = EdgeDevice::with('rvmMachine:id,serial_number,location,status,last_ping');

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $devices = $query->orderBy('updated_at', 'desc')->get();

        // Calculate stats using filter to ensure accessors are triggered
        $stats = [
            'total' => $devices->count(),
            'online' => $devices->filter(fn($d) => $d->status === 'online')->count(),
            'offline' => $devices->filter(fn($d) => $d->status === 'offline')->count(),
            'maintenance' => $devices->filter(fn($d) => $d->status === 'maintenance')->count(),
            'avg_cpu' => $this->calculateAverageMetric($devices, 'cpu_usage'),
            'avg_gpu' => $this->calculateAverageMetric($devices, 'gpu_usage'),
            'avg_temp' => $this->calculateAverageMetric($devices, 'temperature'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $devices,
            'stats' => $stats
        ]);
    }

    /**
     * Calculate average metric from health_metrics JSON.
     */
    private function calculateAverageMetric($devices, $field)
    {
        $values = $devices->filter(function ($d) use ($field) {
            return isset($d->health_metrics[$field]);
        })->pluck("health_metrics.$field");

        if ($values->isEmpty())
            return 0;
        return round($values->avg(), 1);
    }

    /**
     * Send telemetry data from Edge Device.
     * 
     * @OA\Post(
     *      path="/api/v1/devices/{id}/telemetry",
     *      operationId="sendTelemetry",
     *      tags={"Edge Device"},
     *      summary="Send telemetry data (weight, capacity)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="RVM Machine ID",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"plastic_weight","aluminum_weight","total_items"},
     *              @OA\Property(property="plastic_weight", type="number", format="float", example=0.5),
     *              @OA\Property(property="aluminum_weight", type="number", format="float", example=0.2),
     *              @OA\Property(property="glass_weight", type="number", format="float", example=0.0),
     *              @OA\Property(property="total_items", type="integer", example=3),
     *              @OA\Property(property="battery_level", type="integer", example=85),
     *              @OA\Property(property="temperature", type="number", format="float", example=28.5)
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Telemetry recorded",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Telemetry received")
     *          )
     *      )
     * )
     */
    public function telemetry(Request $request, $id)
    {
        $machine = RvmMachine::findOrFail($id);

        $request->validate([
            'plastic_weight' => 'required|numeric|min:0',
            'aluminum_weight' => 'required|numeric|min:0',
            'glass_weight' => 'nullable|numeric|min:0',
            'total_items' => 'required|integer|min:0',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'temperature' => 'nullable|numeric',
        ]);

        TelemetryData::create([
            'rvm_machine_id' => $id,
            'plastic_weight' => $request->plastic_weight,
            'aluminum_weight' => $request->aluminum_weight,
            'glass_weight' => $request->glass_weight ?? 0,
            'total_items' => $request->total_items,
            'battery_level' => $request->battery_level,
            'temperature' => $request->temperature,
        ]);

        // Update last ping machine status
        $machine->update([
            'last_ping' => now(),
            'status' => 'online'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetry received',
        ], 201);
    }

    /**
     * Send heartbeat (Ping) with auto-discovery IP and Hardware Info update.
     * 
     * @OA\Post(
     *      path="/api/v1/devices/{id}/heartbeat",
     *      operationId="sendHeartbeat",
     *      tags={"Edge Device"},
     *      summary="Send heartbeat to indicate device is online",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(property="ip_local", type="string", example="192.168.1.10"),
     *              @OA\Property(property="tailscale_ip", type="string", example="100.64.0.5"),
     *              @OA\Property(property="network_interfaces", type="object"),
     *              @OA\Property(property="health_metrics", type="object"),
     *              @OA\Property(property="hardware_info", type="object", description="Full hardware specs including cameras")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Heartbeat received"
     *      )
     * )
     */
    public function heartbeat(Request $request, $id)
    {
        // Update RVM Machine
        $machine = RvmMachine::findOrFail($id);
        $machine->update([
            'last_ping' => now(),
            'status' => 'online'
        ]);

        // Update EdgeDevice with auto-discovered IP addresses
        $edgeDevice = EdgeDevice::where('rvm_machine_id', $id)->first();
        if ($edgeDevice) {
            $updateData = [
                'status' => 'online',
                'updated_at' => now(),
            ];

            // Auto-update IP addresses from device report
            if ($request->has('ip_local')) {
                $updateData['ip_address_local'] = $request->ip_local;
            }
            if ($request->has('tailscale_ip')) {
                $updateData['tailscale_ip'] = $request->tailscale_ip;
            }
            if ($request->has('network_interfaces')) {
                $updateData['network_interfaces'] = $request->network_interfaces;
            }
            if ($request->has('health_metrics')) {
                $updateData['health_metrics'] = $request->health_metrics;
            }

            // Auto-update Hardware Info & Camera ID
            if ($request->has('hardware_info')) {
                $updateData['hardware_info'] = $request->hardware_info;

                // Extract Camera ID from the first camera in the list
                if (isset($request->hardware_info['cameras']) && is_array($request->hardware_info['cameras']) && count($request->hardware_info['cameras']) > 0) {
                    $camera = $request->hardware_info['cameras'][0];
                    // Prefer 'path' (e.g., /dev/video0) or 'id'
                    if (isset($camera['path'])) {
                        $updateData['camera_id'] = $camera['path'];
                    } elseif (isset($camera['id'])) {
                        $updateData['camera_id'] = $camera['id'];
                    }
                }
            }

            $edgeDevice->update($updateData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Heartbeat received',
            'server_time' => now()->toIso8601String()
        ]);
    }



    /**
     * Register new Edge device.
     */
    public function register(Request $request)
    {
        $request->validate([
            'device_serial' => 'required|string|max:255|unique:edge_devices,device_id',
            'rvm_id' => 'required|exists:rvm_machines,id', // Now required
            'tailscale_ip' => 'nullable|ip',
            'hardware_info' => 'nullable|array',
            // New fields from 3-section form
            'location_name' => 'nullable|string|max:255',
            'inventory_code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string',
            'status' => 'nullable|in:maintenance,inactive,offline',
            'ai_model_version' => 'nullable|string',
        ]);

        // Generate API key (shown only once)
        $apiKey = 'rvm_' . Str::random(60);
        $apiKeyHash = hash('sha256', $apiKey);

        // Extract hardware info
        $hardwareInfo = $request->hardware_info ?? [];

        // Auto-generate inventory_code if not provided
        $inventoryCode = $request->inventory_code;
        if (empty($inventoryCode)) {
            $year = date('Y');
            $lastDevice = EdgeDevice::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
            $sequence = $lastDevice ? (intval(substr($lastDevice->inventory_code ?? 'INV-0000-0000', -4)) + 1) : 1;
            $inventoryCode = sprintf('INV-%s-%04d', $year, $sequence);
        }

        // Create device using model
        $controllerType = $hardwareInfo['controller_type'] ?? 'NVIDIA Jetson';
        $device = EdgeDevice::create([
            'rvm_machine_id' => $request->rvm_id,
            'device_id' => $request->device_serial,
            'type' => $controllerType, // Required NOT NULL column
            'location_name' => $request->location_name,
            'inventory_code' => $inventoryCode,
            'description' => $request->description,
            'tailscale_ip' => $request->tailscale_ip,
            'controller_type' => $controllerType,
            'camera_id' => $hardwareInfo['camera_id'] ?? null,
            'threshold_full' => $hardwareInfo['threshold_full'] ?? 90,
            'ai_model_version' => $request->ai_model_version ?? 'best.pt',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
            'status' => $request->status ?? 'maintenance', // Default to maintenance
            'api_key' => $apiKeyHash,
            'health_metrics' => [],
        ]);

        ActivityLog::log(
            'Edge',
            'Create',
            "Edge device {$request->device_serial} registered at {$request->location_name}",
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device registered successfully',
            'data' => [
                'edge_device_id' => $device->id,
                'device_serial' => $device->device_id,
                'api_key' => $apiKey, // Only returned once, never stored in plain text
                'config' => [
                    'server_url' => config('app.url'),
                    'telemetry_interval_seconds' => 300,
                    'heartbeat_interval_seconds' => 60,
                    'model_sync_interval_minutes' => 30,
                    'threshold_full' => $device->threshold_full,
                ]
            ]
        ], 201);
    }

    /**
     * Check for model updates.
     */
    public function modelSync(Request $request)
    {
        $request->validate([
            'device_serial' => 'required|string',
            'current_version' => 'nullable|string',
            'model_name' => 'required|string',
        ]);

        // Get active model version
        $latestModel = \DB::table('ai_model_versions')
            ->where('model_name', $request->model_name)
            ->where('is_active', true)
            ->orderBy('deployed_at', 'desc')
            ->first();

        if (!$latestModel) {
            return response()->json([
                'status' => 'success',
                'update_available' => false,
                'message' => 'No active model found'
            ]);
        }

        $updateAvailable = $request->current_version !== $latestModel->version;

        if ($updateAvailable) {
            return response()->json([
                'status' => 'success',
                'update_available' => true,
                'data' => [
                    'model_name' => $latestModel->model_name,
                    'latest_version' => $latestModel->version,
                    'current_version' => $request->current_version,
                    'file_path' => $latestModel->file_path,
                    'file_size_mb' => $latestModel->file_size_mb,
                    'sha256_hash' => $latestModel->sha256_hash,
                    'download_url' => "/api/v1/edge/download-model/{$latestModel->sha256_hash}",
                    'deployed_at' => $latestModel->deployed_at
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'update_available' => false,
            'current_version' => $request->current_version
        ]);
    }

    /**
     * Update device location.
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'edge_device_id' => 'required|exists:edge_devices,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_source' => 'required|in:manual,gps_module',
            'accuracy_meters' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'updated_by_user_id' => 'nullable|exists:users,id',
        ]);

        \DB::table('edge_devices')
            ->where('id', $request->edge_device_id)
            ->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location_accuracy_meters' => $request->accuracy_meters,
                'location_source' => $request->location_source,
                'location_address' => $request->address,
                'location_last_updated' => now(),
                'updated_at' => now()
            ]);

        ActivityLog::log('Edge', 'Update', "Edge device #{$request->edge_device_id} location updated", $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Location updated successfully'
        ]);
    }

    /**
     * Upload images to MinIO.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'original_image' => 'required|image|mimes:jpeg,jpg|max:5120', // 5MB
            'processed_image' => 'required|image|mimes:jpeg,jpg|max:5120',
            'mask_image' => 'nullable|image|mimes:png|max:1024', // 1MB
            'metadata' => 'required|json',
        ]);

        $metadata = json_decode($request->metadata, true);
        $sessionId = $metadata['session_id'] ?? 'unknown';
        $itemSequence = $metadata['item_sequence'] ?? 1;
        $date = now()->format('Y-m-d');

        // Storage paths
        $basePath = "images/{$date}/{$sessionId}";

        // Upload original image
        $originalPath = $request->file('original_image')
            ->storeAs("{$basePath}/raw", "item-{$itemSequence}-original.jpg", 'public');

        // Upload processed image
        $processedPath = $request->file('processed_image')
            ->storeAs("{$basePath}/processed", "item-{$itemSequence}-annotated.jpg", 'public');

        // Upload mask if provided
        $maskPath = null;
        if ($request->hasFile('mask_image')) {
            $maskPath = $request->file('mask_image')
                ->storeAs("{$basePath}/masks", "item-{$itemSequence}-mask.png", 'public');
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'original_url' => \Storage::url($originalPath),
                'processed_url' => \Storage::url($processedPath),
                'mask_url' => $maskPath ? \Storage::url($maskPath) : null,
                'uploaded_at' => now()->toIso8601String()
            ]
        ], 201);
    }

    /**
     * Download device configuration as JSON file.
     * Uses Laravel's streamDownload for efficient on-the-fly generation.
     */
    public function downloadConfig($deviceId)
    {
        $device = EdgeDevice::where('device_id', $deviceId)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        // Build config data
        $configData = [
            'rvm_edge_config' => [
                'device_id' => $device->device_id,
                'api_key' => '', // API key not included for security (user copies it manually)
                'location_name' => $device->location_name ?? '',
                'server_url' => config('app.url'),
                'telemetry_interval_seconds' => 300,
                'heartbeat_interval_seconds' => 60,
                'model_sync_interval_minutes' => 30,
                'threshold_full' => $device->threshold_full ?? 90,
            ],
            'generated_at' => now()->toIso8601String(),
            'warning' => 'Keep this file secure. Add your API key manually.'
        ];

        // Sanitize filename
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $device->device_id);
        $fileName = "rvm-config-{$safeName}.json";

        // Use streamDownload for efficient on-the-fly generation (no disk write)
        return response()->streamDownload(function () use ($configData) {
            echo json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Get single Edge Device detail for editing.
     */
    public function show($id)
    {
        $device = EdgeDevice::with('rvmMachine:id,serial_number,location_name')->find($id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $device
        ]);
    }

    /**
     * Update Edge Device.
     */
    public function update(Request $request, $id)
    {
        $device = EdgeDevice::find($id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        $request->validate([
            'location_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'threshold_full' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|in:online,offline,maintenance,inactive',
            'controller_type' => 'nullable|string|max:100',
            'camera_id' => 'nullable|string|max:100',
            'ai_model_version' => 'nullable|string|max:100',
        ]);

        $device->update($request->only([
            'location_name',
            'description',
            'threshold_full',
            'status',
            'controller_type',
            'camera_id',
            'ai_model_version',
        ]));

        ActivityLog::log(
            'Edge',
            'Update',
            "Edge device {$device->device_id} updated",
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device updated successfully',
            'data' => $device->fresh()
        ]);
    }

    /**
     * Soft delete Edge Device.
     * Unlinks from RVM Machine so it becomes available for new registration.
     */
    public function destroy(Request $request, $id)
    {
        $device = EdgeDevice::find($id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        // Store RVM info for response
        $rvmMachineId = $device->rvm_machine_id;
        $deviceSerial = $device->device_id;

        // Unlink from RVM Machine (so RVM is available for new device)
        $device->rvm_machine_id = null;
        $device->save();

        // Soft delete
        $device->delete();

        ActivityLog::log(
            'Edge',
            'Delete',
            "Edge device {$deviceSerial} moved to trash. RVM #{$rvmMachineId} is now available.",
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device dipindahkan ke Kotak Sampah. RVM Machine sekarang tersedia untuk registrasi baru.',
            'unlinked_rvm_id' => $rvmMachineId
        ]);
    }

    /**
     * Get list of soft-deleted (trashed) Edge Devices.
     */
    public function trashed()
    {
        $trashedDevices = EdgeDevice::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $trashedDevices,
            'count' => $trashedDevices->count()
        ]);
    }

    /**
     * Restore a soft-deleted Edge Device.
     * Checks if original RVM is still available before restoring.
     */
    public function restore(Request $request, $id)
    {
        $device = EdgeDevice::onlyTrashed()->find($id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trashed device not found'
            ], 404);
        }

        // Get device's original RVM machine ID from metadata (we need to store this)
        // Since we nullified rvm_machine_id on delete, we need to check if user wants to re-link
        $rvmMachineId = $request->input('rvm_machine_id');

        if ($rvmMachineId) {
            // Check if this RVM is already connected to another device
            $existingDevice = EdgeDevice::where('rvm_machine_id', $rvmMachineId)->first();

            if ($existingDevice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Restore gagal: RVM Machine sudah terhubung dengan Edge Device lain (' . $existingDevice->device_id . '). Pilih RVM lain atau restore tanpa koneksi RVM.',
                    'connected_device' => $existingDevice->device_id
                ], 409); // Conflict
            }

            // Re-link to RVM
            $device->rvm_machine_id = $rvmMachineId;
        }

        // Restore device
        $device->restore();
        $device->save();

        ActivityLog::log(
            'Edge',
            'Restore',
            "Edge device {$device->device_id} restored from trash" . ($rvmMachineId ? " and linked to RVM #{$rvmMachineId}" : ''),
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device berhasil di-restore dari Kotak Sampah.',
            'data' => $device->fresh()
        ]);
    }

    /**
     * Regenerate API key for Edge Device.
     * Returns new key for download (shown only once).
     */
    public function regenerateApiKey(Request $request, $id)
    {
        $device = EdgeDevice::find($id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        // Generate new API key
        $newApiKey = 'rvm_' . Str::random(60);
        $apiKeyHash = hash('sha256', $newApiKey);

        // Update device
        $device->api_key = $apiKeyHash;
        $device->save();

        ActivityLog::log(
            'Edge',
            'Update',
            "API key regenerated for Edge device {$device->device_id}",
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'API Key berhasil di-regenerate. Simpan key ini, tidak akan ditampilkan lagi!',
            'data' => [
                'device_id' => $device->device_id,
                'api_key' => $newApiKey, // Only returned once, never stored in plain text
                'generated_at' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * Handshake endpoint for RVM-Edge Setup Wizard.
     * 
     * Called during initial installation to sync machine identity, hardware config,
     * and receive operational configuration from server.
     * 
     * @OA\Post(
     *      path="/api/v1/edge/handshake",
     *      operationId="edgeHandshake",
     *      tags={"Edge Device"},
     *      summary="Initial handshake for RVM-Edge Setup Wizard",
     *      description="Syncs machine identity, hardware configuration, and health metrics. Returns kiosk URL, WebSocket config, operational policy, and AI model versioning info.",
     *      @OA\Parameter(
     *          name="X-RVM-API-KEY",
     *          in="header",
     *          required=true,
     *          description="API Key from rvm-credentials.json",
     *          @OA\Schema(type="string", example="sk_live_abc123...")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"hardware_id", "name"},
     *              @OA\Property(property="hardware_id", type="string", example="RVM-202601-006", description="From rvm-credentials.json"),
     *              @OA\Property(property="name", type="string", example="RVM KU1", description="Machine display name"),
     *              @OA\Property(property="ip_local", type="string", example="192.168.1.105"),
     *              @OA\Property(property="ip_vpn", type="string", example="100.80.50.20"),
     *              @OA\Property(property="timezone", type="string", example="Asia/Jakarta"),
     *              @OA\Property(property="firmware_version", type="string", example="v1.7.0"),
     *              @OA\Property(property="controller_type", type="string", example="NVIDIA Jetson Orin Nano"),
     *              @OA\Property(property="ai_model_version", type="string", example="YOLO11n-v1.0.0"),
     *              @OA\Property(property="health_metrics", type="object",
     *                  @OA\Property(property="cpu_usage_percent", type="number", example=15.5),
     *                  @OA\Property(property="memory_usage_percent", type="number", example=42.0),
     *                  @OA\Property(property="disk_usage_percent", type="number", example=12.8),
     *                  @OA\Property(property="cpu_temperature", type="number", example=45.0)
     *              ),
     *              @OA\Property(property="hardware_info", type="object", description="Hardware configuration",
     *                  @OA\Property(property="cameras", type="array", @OA\Items(type="object")),
     *                  @OA\Property(property="sensors", type="array", @OA\Items(type="object")),
     *                  @OA\Property(property="actuators", type="array", @OA\Items(type="object")),
     *                  @OA\Property(property="microcontroller", type="object")
     *              ),
     *              @OA\Property(property="diagnostics", type="object",
     *                  @OA\Property(property="network_check", type="string", example="pass"),
     *                  @OA\Property(property="camera_check", type="string", example="pass"),
     *                  @OA\Property(property="motor_test", type="string", example="pass"),
     *                  @OA\Property(property="ai_inference_test", type="string", example="pass")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Handshake successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Handshake successful. Configuration synced."),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="identity", type="object"),
     *                  @OA\Property(property="kiosk", type="object"),
     *                  @OA\Property(property="websocket", type="object"),
     *                  @OA\Property(property="policy", type="object"),
     *                  @OA\Property(property="ai_model", type="object")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthorized - Invalid or missing API key"),
     *      @OA\Response(response=403, description="Forbidden - Machine blocked/suspended"),
     *      @OA\Response(response=422, description="Unprocessable Entity - Validation error"),
     *      @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function handshake(Request $request)
    {
        // Get machine from middleware (ValidateRvmApiKey)
        $machine = $request->attributes->get('rvm_machine');

        if (!$machine) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server Gangguan. Coba lagi nanti.',
                'error_code' => 'MACHINE_NOT_FOUND',
            ], 500);
        }

        // Validate request payload
        $validated = $request->validate([
            // Identity (Required)
            'hardware_id' => 'required|string|max:100',
            'name' => 'required|string|max:255',

            // Network & Location (Auto Detect)
            'ip_local' => 'nullable|ip',
            'ip_vpn' => 'nullable|ip',
            'timezone' => 'nullable|string|max:50',

            // System Info (Auto Detect) - NEW per GAI-handshake.md
            'system' => 'nullable|array',
            'system.jetpack_version' => 'nullable|string|max:50',
            'system.firmware_version' => 'nullable|string|max:50',
            'system.python_version' => 'nullable|string|max:50',
            'system.ai_models' => 'nullable|array',
            'system.ai_models.model_name' => 'nullable|string|max:100',
            'system.ai_models.model_version' => 'nullable|string|max:100',
            'system.ai_models.hash' => 'nullable|string|max:100',

            // Legacy flat fields (for backward compatibility)
            'firmware_version' => 'nullable|string|max:50',
            'controller_type' => 'nullable|string|max:100',
            'ai_model_version' => 'nullable|string|max:100',

            // Health Metrics
            'health_metrics' => 'nullable|array',
            'health_metrics.cpu_usage_percent' => 'nullable|numeric|min:0|max:100',
            'health_metrics.memory_usage_percent' => 'nullable|numeric|min:0|max:100',
            'health_metrics.disk_usage_percent' => 'nullable|numeric|min:0|max:100',
            'health_metrics.cpu_temperature' => 'nullable|numeric',

            // Hardware Info (cameras, sensors, actuators, microcontroller)
            'hardware_info' => 'nullable|array',
            'hardware_info.cameras' => 'nullable|array',
            'hardware_info.sensors' => 'nullable|array',
            'hardware_info.actuators' => 'nullable|array',
            'hardware_info.microcontroller' => 'nullable|array',

            // Diagnostics
            'diagnostics' => 'nullable|array',
        ]);

        // Find or create Edge Device linked to this machine
        $edgeDevice = EdgeDevice::where('rvm_machine_id', $machine->id)->first();

        if (!$edgeDevice) {
            // Create new Edge Device
            $edgeDevice = EdgeDevice::create([
                'rvm_machine_id' => $machine->id,
                'device_id' => $validated['hardware_id'],
                'type' => $validated['controller_type'] ?? 'NVIDIA Jetson',
                'location_name' => $machine->location ?? $validated['name'],
                'status' => 'online',
            ]);
        }

        // Update Edge Device with handshake data
        $edgeDevice->update([
            'device_id' => $validated['hardware_id'],
            'ip_address_local' => $validated['ip_local'] ?? $edgeDevice->ip_address_local,
            'tailscale_ip' => $validated['ip_vpn'] ?? $edgeDevice->tailscale_ip,
            'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
            // Prefer system.firmware_version, fallback to flat field
            'firmware_version' => $validated['system']['firmware_version'] 
                ?? $validated['firmware_version'] 
                ?? $edgeDevice->firmware_version,
            'controller_type' => $validated['controller_type'] ?? $edgeDevice->controller_type,
            // Prefer system.ai_models.model_version, fallback to flat field
            'ai_model_version' => $validated['system']['ai_models']['model_version'] 
                ?? $validated['ai_model_version'] 
                ?? $edgeDevice->ai_model_version,
            'health_metrics' => $validated['health_metrics'] ?? $edgeDevice->health_metrics,
            // Store hardware_info → hardware_config column
            'hardware_config' => $validated['hardware_info'] ?? $edgeDevice->hardware_config,
            'system_info' => $validated['system'] ?? $edgeDevice->system_info,
            'diagnostics_log' => $validated['diagnostics'] ?? $edgeDevice->diagnostics_log,
            'status' => 'online',
            'last_handshake_at' => now(),
        ]);

        // Update RVM Machine status
        $machine->update([
            'last_ping' => now(),
            'status' => 'online',
        ]);

        // Generate signed kiosk URL
        $kioskUrl = $this->generateSignedKioskUrl($machine);

        // Generate WebSocket auth token
        $wsAuthToken = $this->generateWebSocketToken($machine);

        // Get AI model info
        $aiModelInfo = $this->getLatestAiModelInfo();

        // Get operational policy from machine settings
        $policy = $this->getOperationalPolicy($machine, $edgeDevice);

        // Log handshake activity
        ActivityLog::log(
            'Edge',
            'Handshake',
            "Edge device {$validated['hardware_id']} handshake successful from {$validated['ip_local']}",
            null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Handshake successful. Configuration synced.',
            'data' => [
                // 1. Logical Identity (Sync with Server Database)
                'identity' => [
                    'rvm_id' => $machine->id,
                    'rvm_uuid' => $machine->uuid, // 36-char UUID format
                    'rvm_name' => $machine->name ?? $machine->location,
                ],

                // 2. Kiosk UI Configuration
                'kiosk' => [
                    'url' => $kioskUrl,
                    'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
                ],

                // 3. WebSocket Configuration
                'websocket' => [
                    'channel' => "rvm.{$machine->uuid}",
                    'auth_token' => $wsAuthToken,
                    'host' => parse_url(config('app.url'), PHP_URL_HOST),
                    'port' => 443,
                    'scheme' => 'wss',
                ],

                // 4. Operational Policy
                'policy' => $policy,

                // 5. AI Model Versioning
                'ai_model' => $aiModelInfo,
            ],
        ], 200);
    }

    /**
     * Handle single item deposit from RVM Edge.
     * 
     * @OA\Post(
     *      path="/api/v1/edge/deposit",
     *      operationId="edgeDeposit",
     *      tags={"Edge Device"},
     *      summary="Process single item deposit and image",
     *      description="Uploads item image, records transaction item, and updates session.",
     *      @OA\Parameter(
     *          name="X-RVM-API-KEY",
     *          in="header",
     *          required=true,
     *          description="API Key from rvm-credentials.json",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"image", "data", "status"},
     *                  @OA\Property(property="image", type="string", format="binary"),
     *                  @OA\Property(property="status", type="string", example="ACCEPTED"),
     *                  @OA\Property(property="data", type="string", description="JSON string of AI result & session info"),
     *                  @OA\Property(property="session_id", type="string", description="Optional session/transaction ID")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=201, description="Deposit recorded")
     * )
     */
    public function deposit(Request $request)
    {
        $machine = $request->attributes->get('rvm_machine');
        if (!$machine) {
             return response()->json(['status' => 'error', 'message' => 'Machine auth failed'], 401);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'status' => 'required|string',
            'data' => 'required', // Can be JSON string or array
        ]);

        // Parse Data
        $data = is_string($request->data) ? json_decode($request->data, true) : $request->data;
        $sessionId = $request->input('session_id') ?? ($data['session_id'] ?? null);
        
        // Storage Logic
        $date = now()->format('Y-m-d');
        $uploadPath = "deposits/{$date}/" . ($sessionId ?? 'unknown');
        $imagePath = $request->file('image')->store($uploadPath, 'public');

        // Note: Actual Transaction creation logic would link here. 
        // For now, we return the storage path and acknowledged status.
        // If session_id is active, we should theoretically add a TransactionItem.
        // We will assume for this MVP that the server just acknowledges receipt.
        
        return response()->json([
            'status' => 'success',
            'message' => 'Deposit processed',
            'data' => [
                'image_url' => \Storage::url($imagePath),
                'ai_result' => $data,
                'processed_at' => now()->toIso8601String()
            ]
        ], 201);
    }

    /**
     * Sync offline transactions.
     * 
     * @OA\Post(
     *      path="/api/v1/edge/sync-offline",
     *      operationId="edgeSyncOffline",
     *      tags={"Edge Device"},
     *      summary="Bulk upload offline transactions",
     *      @OA\Parameter(
     *          name="X-RVM-API-KEY",
     *          in="header",
     *          required=true,
     *          description="API Key from rvm-credentials.json",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"transactions"},
     *              @OA\Property(property="transactions", type="array", 
     *                  @OA\Items(
     *                      type="object",
     *                      required={"items", "timestamp"},
     *                      @OA\Property(
     *                          property="items",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              required={"type", "weight", "points"},
     *                              @OA\Property(property="type", type="string"),
     *                              @OA\Property(property="weight", type="number", format="float"),
     *                              @OA\Property(property="points", type="integer")
     *                          )
     *                      ),
     *                      @OA\Property(property="timestamp", type="string", format="date-time")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Sync successful")
     * )
     */
    public function syncOffline(Request $request)
    {
         $machine = $request->attributes->get('rvm_machine');
         if (!$machine) return response()->json(['status' => 'error', 'message' => 'Auth failed'], 401);

         $transactions = $request->input('transactions');
         
         if (!is_array($transactions)) {
             return response()->json(['status' => 'error', 'message' => 'Invalid format'], 422);
         }

         $syncedCount = 0;
         DB::beginTransaction();
         try {
             foreach ($transactions as $txData) {
                // Create Transaction
                $transaction = Transaction::create([
                    'rvm_machine_id' => $machine->id,
                    'user_id' => null, // Offline transactions are anonymous unless user_hash provided
                    'total_points' => collect($txData['items'])->sum('points'),
                    'total_weight' => collect($txData['items'])->sum('weight'),
                    'total_items' => count($txData['items']),
                    'status' => 'completed',
                    'completed_at' => $txData['timestamp'] ?? now(),
                    'started_at' => $txData['timestamp'] ?? now()
                ]);

                foreach ($txData['items'] as $item) {
                    $transaction->items()->create([
                        'waste_type' => $item['type'],
                        'weight' => $item['weight'] ?? 0,
                        'points' => $item['points'] ?? 0,
                    ]);
                }
                $syncedCount++;
             }
             DB::commit();
         } catch (\Exception $e) {
             DB::rollBack();
             return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
         }

         return response()->json([
             'status' => 'success',
             'message' => "Synced {$syncedCount} offline transactions",
             'synced_count' => $syncedCount
         ]);
    }

    /**
     * Heartbeat endpoint for Edge Client (No ID in URL).
     * 
     * @OA\Post(
     *      path="/api/v1/edge/heartbeat",
     *      operationId="edgeHeartbeat",
     *      tags={"Edge Device"},
     *      summary="Edge Client Heartbeat",
     *      @OA\Response(response=200, description="Heartbeat received")
     * )
     */
    public function heartbeatEdge(Request $request)
    {
        // Debug: Log incoming payload
        \Log::info('Heartbeat Edge Payload:', $request->all());

        $machine = $request->attributes->get('rvm_machine');
        if (!$machine) {
             return response()->json(['status' => 'error', 'message' => 'Machine auth failed'], 401);
        }

        // Map 'discovery' from Python client to 'hardware_info' if needed
        if ($request->has('discovery') && !$request->has('hardware_info')) {
            $request->merge(['hardware_info' => $request->discovery]);
        }

        // Update RVM Machine last_ping and capacity
        $machine->update([
            'last_ping' => now(),
            'status' => 'online',
            'capacity_percentage' => $request->bin_capacity ?? $machine->capacity_percentage
        ]);

        // Update Edge Device status/metrics
        $edgeDevice = EdgeDevice::where('rvm_machine_id', $machine->id)->first();
        if ($edgeDevice) {
            $updateData = [
                'status' => 'online',
                'updated_at' => now(),
                'health_metrics' => $request->health_metrics ?? $edgeDevice->health_metrics,
                'ip_address_local' => $request->ip_local ?? $edgeDevice->ip_address_local,
                'tailscale_ip' => $request->tailscale_ip ?? $edgeDevice->tailscale_ip
            ];

            // Auto-update Hardware Info & Camera ID
            if ($request->has('hardware_info')) {
                $updateData['hardware_info'] = $request->hardware_info;

                // Extract Camera ID from the first camera in the list
                if (isset($request->hardware_info['cameras']) && is_array($request->hardware_info['cameras']) && count($request->hardware_info['cameras']) > 0) {
                    $camera = $request->hardware_info['cameras'][0];
                    if (isset($camera['path'])) {
                        $updateData['camera_id'] = $camera['path'];
                    } elseif (isset($camera['id'])) {
                        $updateData['camera_id'] = $camera['id'];
                    }
                }
            }

            // Update firmware version if provided
            if ($request->has('version')) {
                $updateData['firmware_version'] = $request->version;
            }

            $edgeDevice->update($updateData);
        }

        // Pull pending commands from cache
        $commands = Cache::pull("edge_cmd_{$machine->id}", []);

        return response()->json([
            'status' => 'success',
            'server_time' => now()->toIso8601String(),
            'commands' => $commands
        ]);
    }

    /**
     * Queue a command for an Edge device (Called from Dashboard).
     */
    public function sendCommand(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:GIT_PULL,RESTART'
        ]);

        $machine = RvmMachine::findOrFail($id);
        
        // Store command in cache for 10 minutes
        $cacheKey = "edge_cmd_{$machine->id}";
        $commands = Cache::get($cacheKey, []);
        $commands[] = [
            'action' => $validated['action'],
            'timestamp' => now()->toIso8601String()
        ];
        Cache::put($cacheKey, $commands, 600);

        return response()->json([
            'status' => 'success',
            'message' => "Command {$validated['action']} queued for device {$machine->name}."
        ]);
    }

    /**
     * Generate signed kiosk URL for RVM-UI browser.
     * 
     * Uses Laravel's URL::signedRoute() to create a cryptographically
     * signed URL that is validated by the 'signed' middleware.
     */
    private function generateSignedKioskUrl(RvmMachine $machine): string
    {
        return URL::signedRoute('kiosk.index', [
            'uuid' => $machine->uuid
        ]);
    }

    /**
     * Generate temporary WebSocket auth token.
     */
    private function generateWebSocketToken(RvmMachine $machine): string
    {
        // Generate a short-lived token for WebSocket authentication
        $payload = [
            'machine_id' => $machine->id,
            'serial' => $machine->serial_number,
            'exp' => now()->addHours(24)->timestamp,
        ];
        
        return base64_encode(json_encode($payload)) . '.' . hash_hmac('sha256', json_encode($payload), config('app.key'));
    }

    /**
     * Get latest AI model information for Edge devices.
     */
    private function getLatestAiModelInfo(): array
    {
        $latestModel = \DB::table('ai_model_versions')
            ->where('is_active', true)
            ->orderBy('deployed_at', 'desc')
            ->first();

        if (!$latestModel) {
            return [
                'target_version' => 'v1.0.0',
                'hash' => null,
                'update_source_url' => null,
            ];
        }

        return [
            'target_version' => $latestModel->version,
            'hash' => $latestModel->sha256_hash,
            'update_source_url' => config('app.url') . "/api/v1/edge/download-model/{$latestModel->sha256_hash}",
        ];
    }

    /**
     * Get operational policy for the machine.
     */
    private function getOperationalPolicy(RvmMachine $machine, EdgeDevice $edgeDevice): array
    {
        return [
            'is_maintenance_mode' => $machine->status === 'maintenance',
            'bin_full_threshold_percent' => $edgeDevice->threshold_full ?? 90,
            'camera_idle_timeout_sec' => 60,
            'motor_speed_delay' => 0.005,
        ];
    }
}

