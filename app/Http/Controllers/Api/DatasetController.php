<?php

namespace App\Http\Controllers\Api;

use App\Models\DatasetImageRaw;
use App\Models\RvmMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

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
            'image' => 'required|file|max:10240', // Max 10MB (Allow file for mock testing)
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
            
            // Path: on "/storage/app"
            // and image path is "/dataset/images/raw"
            // Note: 'local' disk usually points to storage/app but this is bersifat private.
            // so this path is "/storage/app/private/dataset/images/raw" was right.
            $path = $file->storeAs('/dataset/images/raw', $filename, 'local');

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

    /**
     * Get the latest raw image for a specific machine.
     */
    public function getLatest(Request $request, $id)
    {
        try {
            $image = DatasetImageRaw::where('rvm_machine_id', $id)
                ->latest('captured_at')
                ->first();

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'No images found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => route('dataset.serve', ['filename' => $image->filename]),
                    'captured_at' => $image->captured_at,
                    'camera_port' => $image->camera_port ?? 'unknown'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("GetLatest Image Error: " . $e->getMessage() . " Line: " . $e->getLine());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Serve a private dataset image.
     */
    public function serveImage($filename)
    {
        // Files are physically located in storage/app/private/dataset/images/raw
        $path = "dataset/images/raw/{$filename}";
        
        Log::info("Checking Path Existence: " . Storage::disk('local')->path($path));

        if (!Storage::disk('local')->exists($path)) {
            Log::warning("ServeImage: File not found at {$path}");
            abort(404);
        }

        // Serve the file AS-IS with proper headers for accurate color rendering
        // No compression, no conversion - exact file from disk
        return Storage::disk('local')->response($path, null, [
            'Content-Type' => Storage::disk('local')->mimeType($path),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            // Ensure browser doesn't apply color management
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }
}
