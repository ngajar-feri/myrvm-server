<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CvModel;
use App\Models\EdgeShellCommand;
use App\Models\RvmMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class PlaygroundController extends Controller
{
    /**
     * Get list of available CV models from models.json merged with database status.
     */
    public function getModels()
    {
        // Read models.json as base
        $modelsPath = storage_path('app/private/models/models.json');
        if (!file_exists($modelsPath)) {
            return response()->json(['status' => 'error', 'message' => 'models.json not found'], 404);
        }
        
        $models = json_decode(file_get_contents($modelsPath), true);
        
        // Merge with database status
        $dbModels = CvModel::all()->keyBy('slug');
        
        foreach ($models as &$model) {
            if ($dbModels->has($model['slug'])) {
                $dbModel = $dbModels->get($model['slug']);
                $model['status'] = $dbModel->status;
                $model['local_path'] = $dbModel->local_path;
                $model['size_bytes'] = $dbModel->size_bytes;
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $models
        ]);
    }
    
    /**
     * Download a model to Edge device.
     */
    public function downloadModel(Request $request, $slug)
    {
        $modelsPath = storage_path('app/private/models/models.json');
        $models = json_decode(file_get_contents($modelsPath), true);
        
        $model = collect($models)->firstWhere('slug', $slug);
        if (!$model) {
            return response()->json(['status' => 'error', 'message' => 'Model not found'], 404);
        }
        
        // Create or update cv_models record with 'downloading' status
        $cvModel = CvModel::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $model['name'],
                'download_url' => $model['url'],
                'status' => 'downloading',
            ]
        );
        
        // Queue download command for Edge device
        $machineId = $request->input('machine_id');
        if ($machineId) {
            $cacheKey = "edge_cmd_{$machineId}";
            $commands = Cache::get($cacheKey, []);
            $commands[] = [
                'action' => 'DOWNLOAD_MODEL',
                'payload' => [
                    'slug' => $slug,
                    'url' => $model['url'],
                    'name' => $model['name'],
                ],
                'timestamp' => now()->toIso8601String()
            ];
            Cache::put($cacheKey, $commands, 600);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => "Download command queued for model: {$model['name']}",
            'data' => $cvModel
        ]);
    }
    
    /**
     * Upload a custom model file.
     */
    public function uploadModel(Request $request)
    {
        $request->validate([
            'model_file' => 'required|file|max:512000', // Max 500MB
            'name' => 'required|string|max:255',
        ]);
        
        $file = $request->file('model_file');
        $slug = \Str::slug($request->input('name'));
        $filename = $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('models', $filename, 'private');
        
        $cvModel = CvModel::create([
            'name' => $request->input('name'),
            'slug' => $slug . '_' . time(),
            'download_url' => 'local://' . $path,
            'local_path' => storage_path('app/private/' . $path),
            'status' => 'ready',
            'size_bytes' => $file->getSize(),
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Model uploaded successfully',
            'data' => $cvModel
        ]);
    }
    
    /**
     * Get IoT components from machine's hardware_config.
     */
    public function getComponents($machineId)
    {
        $machine = RvmMachine::with('edgeDevice')->findOrFail($machineId);
        $edgeDevice = $machine->edgeDevice;
        
        if (!$edgeDevice || !$edgeDevice->hardware_config) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }
        
        $config = $edgeDevice->hardware_config;
        $components = [];
        
        // Extract sensors
        if (isset($config['sensors'])) {
            foreach ($config['sensors'] as $sensor) {
                $components[] = [
                    'name' => $sensor['name'] ?? 'Unknown Sensor',
                    'type' => 'sensor',
                    'pin' => $sensor['pin'] ?? null,
                    'interface' => $sensor['interface'] ?? 'GPIO',
                ];
            }
        }
        
        // Extract actuators
        if (isset($config['actuators'])) {
            foreach ($config['actuators'] as $actuator) {
                $components[] = [
                    'name' => $actuator['name'] ?? 'Unknown Actuator',
                    'type' => 'actuator',
                    'pin' => $actuator['pin'] ?? null,
                    'interface' => $actuator['interface'] ?? 'GPIO',
                ];
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $components
        ]);
    }
    
    /**
     * Send command to read sensor value.
     */
    public function readComponent(Request $request, $machineId)
    {
        $request->validate([
            'component_name' => 'required|string',
        ]);
        
        $machine = RvmMachine::findOrFail($machineId);
        
        $cacheKey = "edge_cmd_{$machineId}";
        $commands = Cache::get($cacheKey, []);
        $commands[] = [
            'action' => 'READ_SENSOR',
            'payload' => [
                'component_name' => $request->input('component_name'),
            ],
            'timestamp' => now()->toIso8601String()
        ];
        Cache::put($cacheKey, $commands, 600);
        
        return response()->json([
            'status' => 'success',
            'message' => "Read command queued for: {$request->input('component_name')}"
        ]);
    }
    
    /**
     * Send command to trigger actuator.
     */
    public function triggerComponent(Request $request, $machineId)
    {
        $request->validate([
            'component_name' => 'required|string',
            'action' => 'required|string|in:open,close,trigger,reset',
        ]);
        
        $machine = RvmMachine::findOrFail($machineId);
        
        $cacheKey = "edge_cmd_{$machineId}";
        $commands = Cache::get($cacheKey, []);
        $commands[] = [
            'action' => 'TRIGGER_ACTUATOR',
            'payload' => [
                'component_name' => $request->input('component_name'),
                'action' => $request->input('action'),
            ],
            'timestamp' => now()->toIso8601String()
        ];
        Cache::put($cacheKey, $commands, 600);
        
        return response()->json([
            'status' => 'success',
            'message' => "Trigger command queued for: {$request->input('component_name')}"
        ]);
    }
    
    /**
     * Get list of recommended shell commands.
     */
    public function getShellCommands()
    {
        $commands = EdgeShellCommand::orderBy('category')->get();
        
        // Group by category
        $grouped = $commands->groupBy('category');
        
        return response()->json([
            'status' => 'success',
            'data' => $grouped
        ]);
    }
    
    /**
     * Execute shell command on Edge device.
     */
    public function executeShellCommand(Request $request, $machineId)
    {
        $request->validate([
            'command' => 'required|string|max:1000',
        ]);
        
        $machine = RvmMachine::findOrFail($machineId);
        
        // Security check: block dangerous patterns if not in allowed list
        $command = $request->input('command');
        $isAllowed = EdgeShellCommand::where('command', $command)->exists();
        
        if (!$isAllowed) {
            // Check for dangerous patterns in custom commands
            $dangerousPatterns = ['rm -rf', 'mkfs', 'dd if=', ':(){', 'chmod 777 /', 'chown -R'];
            foreach ($dangerousPatterns as $pattern) {
                if (stripos($command, $pattern) !== false) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This command contains dangerous patterns and is not allowed.'
                    ], 403);
                }
            }
        }
        
        $cacheKey = "edge_cmd_{$machineId}";
        $commands = Cache::get($cacheKey, []);
        $commands[] = [
            'action' => 'SHELL_COMMAND',
            'payload' => [
                'command' => $command,
            ],
            'timestamp' => now()->toIso8601String()
        ];
        Cache::put($cacheKey, $commands, 600);
        
        return response()->json([
            'status' => 'success',
            'message' => "Shell command queued for execution"
        ]);
    }
    
    /**
     * Run inference on image (proxy to Edge device).
     */
    public function runInference(Request $request, $machineId)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'model_slug' => 'required|string',
            'confidence' => 'nullable|numeric|min:0|max:1',
        ]);
        
        $machine = RvmMachine::findOrFail($machineId);
        
        // Store image temporarily
        $image = $request->file('image');
        $filename = 'inference_' . time() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('dataset/images/raw', $filename, 'private');
        
        // Queue inference command
        $cacheKey = "edge_cmd_{$machineId}";
        $commands = Cache::get($cacheKey, []);
        $commands[] = [
            'action' => 'RUN_INFERENCE',
            'payload' => [
                'image_path' => storage_path('app/private/' . $path),
                'model_slug' => $request->input('model_slug'),
                'confidence' => $request->input('confidence', 0.5),
            ],
            'timestamp' => now()->toIso8601String()
        ];
        Cache::put($cacheKey, $commands, 600);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Inference command queued',
            'data' => [
                'image_path' => $path,
            ]
        ]);
    }
}
