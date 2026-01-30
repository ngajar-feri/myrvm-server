<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Notifications\AssignmentCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignmentController extends Controller
{
    /**
     * Display a listing of assignments
     */
    public function index(Request $request)
    {
        try {
            $query = Assignment::with(['user', 'machine', 'assignedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by machine
            if ($request->has('machine_id')) {
                $query->where('machine_id', $request->machine_id);
            }

            $assignments = $query->orderBy('assigned_at', 'desc')
                ->paginate($request->per_page ?? 15);

            // Append team attribute to each assignment
            $assignments->getCollection()->transform(function ($assignment) {
                $assignment->append('team');
                return $assignment;
            });

            return response()->json($assignments);

        } catch (\Exception $e) {
            Log::error('Failed to list assignments: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve assignments'
            ], 500);
        }
    }

    /**
     * Store bulk assignments in storage
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'machine_ids' => 'required|array|min:1',
                'machine_ids.*' => 'exists:rvm_machines,id',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            $assignments = [];
            $assignedBy = auth()->id();

            // Generate a batch_id to group all assignments from this request
            $batchId = \Illuminate\Support\Str::uuid()->toString();

            // Create assignment for each machine (grouped by batch_id)
            // All users share the same batch_id for each machine
            foreach ($validated['machine_ids'] as $machineId) {
                $assignment = Assignment::create([
                    'batch_id' => $batchId,
                    'user_id' => $validated['user_ids'][0], // Primary user (first in list)
                    'team_user_ids' => $validated['user_ids'], // All team members
                    'machine_id' => $machineId,
                    'assigned_by' => $assignedBy,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Load relationships
                $assignment->load(['user', 'machine', 'assignedBy']);

                // Send notification to ALL assigned users
                foreach ($validated['user_ids'] as $userId) {
                    try {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            $user->notify(new AssignmentCreated($assignment));
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to send notification: ' . $e->getMessage());
                    }
                }

                $assignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'message' => 'Assignments created successfully',
                'count' => count($assignments),
                'batch_id' => $batchId,
                'assignments' => $assignments
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create assignments: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create assignments'
            ], 500);
        }
    }

    /**
     * Display the specified assignment
     */
    public function show(string $id)
    {
        try {
            $assignment = Assignment::with(['user', 'machine', 'assignedBy'])->findOrFail($id);
            return response()->json($assignment);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }
    }

    /**
     * Update the specified assignment (mainly for status updates)
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
                'notes' => 'sometimes|nullable|string|max:1000',
            ]);

            $assignment = Assignment::findOrFail($id);
            $assignment->update($validated);

            // Auto-set completed_at when status changes to completed
            if (isset($validated['status']) && $validated['status'] === 'completed') {
                $assignment->completed_at = now();
                $assignment->save();
            }

            return response()->json([
                'message' => 'Assignment updated successfully',
                'assignment' => $assignment->load(['user', 'machine', 'assignedBy'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update assignment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update assignment'], 500);
        }
    }

    /**
     * Cancel/Delete the specified assignment
     */
    public function destroy(string $id)
    {
        try {
            $assignment = Assignment::findOrFail($id);

            // Only allow cancellation of pending/in_progress assignments
            if (in_array($assignment->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'error' => 'Cannot cancel completed or already cancelled assignment'
                ], 400);
            }

            $assignment->status = 'cancelled';
            $assignment->save();

            return response()->json([
                'message' => 'Assignment cancelled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel assignment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel assignment'], 500);
        }
    }

    /**
     * Update assignment status
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed,cancelled',
            ]);

            $assignment = Assignment::findOrFail($id);
            $oldStatus = $assignment->status;
            $assignment->status = $validated['status'];

            // Auto-set completed_at if status is completed
            if ($validated['status'] === 'completed' && !$assignment->completed_at) {
                $assignment->completed_at = now();
            }

            $assignment->save();

            // Log status change
            Log::info('Assignment status updated', [
                'assignment_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Assignment status updated successfully',
                'assignment' => $assignment->load(['user', 'machine', 'assignedBy'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update assignment status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update assignment status'], 500);
        }
    }
}
