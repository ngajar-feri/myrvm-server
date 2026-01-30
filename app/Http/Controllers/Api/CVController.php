<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CVController extends Controller
{
    /**
     * Upload trained model from RVM-CV.
     */
    public function uploadModel(Request $request)
    {
        $request->validate([
            'model_name' => 'required|string|in:yolo11,sam2',
            'version' => 'required|string',
            'model_file' => 'required|file|mimes:pt,onnx|max:204800', // 200MB
            'metrics' => 'nullable|json',
            'training_job_id' => 'nullable|integer',
        ]);

        $modelName = $request->model_name;
        $version = $request->version;

        // Upload to storage
        $filePath = $request->file('model_file')
            ->storeAs("models/{$modelName}/{$version}", "best.pt", 'public');

        $fileSize = $request->file('model_file')->getSize() / 1024 / 1024; // MB
        $fileHash = hash_file('sha256', $request->file('model_file')->getRealPath());

        // Store model version
        $modelId = \DB::table('ai_model_versions')->insertGetId([
            'model_name' => $modelName,
            'version' => $version,
            'file_path' => $filePath,
            'file_size_mb' => round($fileSize, 2),
            'sha256_hash' => $fileHash,
            'training_job_id' => $request->training_job_id,
            'metrics' => $request->metrics,
            'is_active' => false, // Admin activates manually
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Model uploaded successfully',
            'data' => [
                'model_id' => $modelId,
                'sha256_hash' => $fileHash,
                'file_size_mb' => round($fileSize, 2),
                'file_path' => $filePath
            ]
        ], 201);
    }

    /**
     * Training completion callback from RVM-CV.
     */
    public function trainingComplete(Request $request)
    {
        $request->validate([
            'training_job_id' => 'required|integer',
            'status' => 'required|in:success,failed',
            'metrics' => 'nullable|json',
            'error_message' => 'nullable|string',
        ]);

        // Update training job status (if table exists)
        \DB::table('ai_models')->where('id', $request->training_job_id)
            ->update([
                'status' => $request->status,
                'training_metrics' => $request->metrics,
                'error_message' => $request->error_message,
                'completed_at' => now(),
                'updated_at' => now()
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Training status updated'
        ]);
    }

    /**
     * Get dataset for training.
     */
    public function getDataset($id)
    {
        // TODO: Implement dataset retrieval
        // For now, return placeholder
        return response()->json([
            'status' => 'success',
            'data' => [
                'dataset_id' => $id,
                'name' => 'Plastic Bottles Dataset',
                'images_count' => 1000,
                'download_url' => '/storage/datasets/' . $id . '.zip'
            ]
        ]);
    }

    /**
     * Download model file.
     */
    public function downloadModel($versionOrHash)
    {
        // Try to find by hash or version
        $model = \DB::table('ai_model_versions')
            ->where('sha256_hash', $versionOrHash)
            ->orWhere('version', $versionOrHash)
            ->first();

        if (!$model) {
            return response()->json([
                'status' => 'error',
                'message' => 'Model not found'
            ], 404);
        }

        $filePath = storage_path('app/public/' . $model->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Model file not found on server'
            ], 404);
        }

        return response()->download($filePath, "best.pt", [
            'Content-Type' => 'application/octet-stream',
            'X-SHA256-Hash' => $model->sha256_hash
        ]);
    }

    /**
     * Playground inference (manual testing).
     */
    public function playgroundInference(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'model_name' => 'required|string',
            'model_version' => 'nullable|string',
        ]);

        // TODO: Trigger inference on RVM-CV
        // For now, return placeholder
        return response()->json([
            'status' => 'success',
            'message' => 'Inference request queued',
            'data' => [
                'job_id' => rand(1000, 9999),
                'status' => 'processing',
                'estimated_time_seconds' => 5
            ]
        ]);
    }
}
