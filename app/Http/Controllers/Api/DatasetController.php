<?php

namespace App\Http\Controllers\Api;

use App\Models\DatasetImageRaw;
use App\Models\RvmMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatasetController extends Controller
{
    /**
     * Store a raw dataset image uploaded from Edge Device.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validate Request
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'camera_port' => 'nullable|string',
        ]);

        // 2. Validate Header & Machine (Using Middleware Attribute)
        $machine = $request->attributes->get('rvm_machine');
        
        if (!$machine) {
             // Fallback for manual check if middleware didn't run (though it should)
             $apiKey = $request->header('X-RVM-API-KEY');
             if ($apiKey) {
                 $machine = RvmMachine::where('api_key', $apiKey)->first();
             }
        }

        if (!$machine) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Machine'], 401);
        }

        try {
            // 3. Handle File Upload
            $file = $request->file('image');
            $timestamp = now()->timestamp;
            $randomString = Str::random(8); // Random string for uniqueness
            // Custom Name Format: {timestamp}_{randomstring}_raw.jpg
            $filename = "{$timestamp}_{$randomString}_raw." . $file->getClientOriginalExtension();
            
            // Path: private/dataset/images/raw
            // Note: 'local' disk usually points to storage/app
            $path = $file->storeAs('private/dataset/images/raw', $filename, 'local');

            // 4. Save to Database
            $datasetImage = DatasetImageRaw::create([
                'rvm_machine_id' => $machine->id,
                'file_path' => $path,
                'filename' => $filename,
                'camera_port' => $request->camera_port,
                'captured_at' => now(),
            ]);

            Log::info("Dataset Image Saved: {$filename} from Machine {$machine->serial_number}");

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $datasetImage
            ], 201);

        } catch (\Exception $e) {
            Log::error("Dataset Upload Error: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to process image: ' . $e->getMessage()
            ], 500);
        }
    }
}
