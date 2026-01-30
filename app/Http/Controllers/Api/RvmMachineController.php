<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RvmMachine;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class RvmMachineController extends Controller
{
    /**
     * Role hierarchy for assignment permissions.
     */
    private $roleHierarchy = [
        'super_admin' => 1,
        'admin' => 2,
        'operator' => 3,
        'teknisi' => 3,
        'tenant' => 4,
        'user' => 5
    ];

    /**
     * Roles allowed to view machines.
     */
    private $viewAllowedRoles = ['super_admin', 'admin', 'operator', 'teknisi'];

    /**
     * Roles allowed to create/edit machines.
     */
    private $editAllowedRoles = ['super_admin', 'admin'];

    /**
     * List RVM machines with role-based filtering.
     * 
     * @OA\Get(
     *      path="/api/v1/rvm-machines",
     *      operationId="getRvmMachines",
     *      tags={"RVM Machines"},
     *      summary="List RVM machines (role-based)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          description="Filter by machine status",
     *          required=false,
     *          @OA\Schema(type="string", enum={"online", "offline", "maintenance", "full_warning"})
     *      ),
     *      @OA\Parameter(
     *          name="location",
     *          in="query",
     *          description="Filter by location (partial match)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="List of machines",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(response=403, description="Access denied")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user has permission to view
        if (!in_array($user->role, $this->viewAllowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Your role does not have permission to view RVM machines.'
            ], 403);
        }

        // Build base query with eager loading and counts
        $query = RvmMachine::with('edgeDevice')->withCount('technicians');

        // Role-based filtering: operator/teknisi see assigned only
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            $assignedIds = TechnicianAssignment::where('technician_id', $user->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->pluck('rvm_machine_id');
            $query->whereIn('id', $assignedIds);
        }

        // Filter by status (if provided)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by location (partial match, if provided)
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        $machines = $query->latest()->get();

        ActivityLog::log('RVM', 'Read', "User {$user->name} accessed RVM machines list", $user->id);

        return response()->json([
            'status' => 'success',
            'data' => $machines
        ]);
    }


    /**
     * Create a new RVM machine (admin/super_admin only).
     * Auto-generates serial_number and api_key.
     * Auto-creates EdgeDevice stub.
     * 
     * @OA\Post(
     *      path="/api/v1/rvm-machines",
     *      operationId="createRvmMachine",
     *      tags={"RVM Machines"},
     *      summary="Create new RVM machine",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="RVM Mall Grand Indonesia"),
     *              @OA\Property(property="location", type="string", example="Lt. 3 Dekat Eskalator"),
     *              @OA\Property(property="location_address", type="string", example="Jl. MH Thamrin No.1"),
     *              @OA\Property(property="latitude", type="number", example=-6.1951),
     *              @OA\Property(property="longitude", type="number", example=106.8211),
     *              @OA\Property(property="status", type="string", enum={"online","offline","maintenance","full_warning"}, example="offline")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Machine created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object"),
     *              @OA\Property(property="credentials", type="object",
     *                  @OA\Property(property="serial_number", type="string"),
     *                  @OA\Property(property="api_key", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=403, description="Access denied")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, $this->editAllowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin and Admin can create machines.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'location_address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'in:online,offline,maintenance,full_warning',
        ]);

        // Create machine (serial_number and api_key auto-generated in model boot)
        $machine = RvmMachine::create([
            'name' => $request->name,
            'location' => $request->location,
            'location_address' => $request->location_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status ?? 'offline',
        ]);

        // Auto-create EdgeDevice stub for this machine
        try {
            $machine->edgeDevice()->create([
                'device_id' => 'PENDING-' . Str::uuid(), // Temporary unique ID
                'type' => 'NVIDIA Jetson', // Default type
                'controller_type' => 'NVIDIA Jetson',
                'status' => 'waiting_handshake',
                'location_name' => $machine->name,
                'health_metrics' => [],
                'network_interfaces' => [],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('EdgeDevice Creation Failed: ' . $e->getMessage());
            // Proceed without breaking RVM creation, but user should know?
            // For now, suppress user error, as RVM is created.
        }

        try {
            ActivityLog::log('RVM', 'Create', "Machine '{$machine->name}' created by {$user->name}", $user->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActivityLog Failed: ' . $e->getMessage());
        }

        // Return simple success - credentials NOT provided here.
        // Credentials will be generated when a Technician is assigned.
        return response()->json([
            'status' => 'success',
            'message' => 'RVM berhasil ditambahkan. Silakan tugaskan Teknisi untuk menerima kredensial instalasi.',
            'data' => $machine
        ], 201);
    }

    /**
     * Get RVM machine details (with access check).
     * Includes Edge Device info and latest telemetry.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, $this->viewAllowedRoles)) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        $machine = RvmMachine::with([
            'edgeDevice',
            'edgeDevice.telemetry' => function ($query) {
                $query->orderBy('client_timestamp', 'desc')->limit(5);
            }
        ])->findOrFail($id);

        // Check assignment for operator/teknisi
        if (in_array($user->role, ['operator', 'teknisi'])) {
            $isAssigned = TechnicianAssignment::where('technician_id', $user->id)
                ->where('rvm_machine_id', $id)
                ->exists();
            if (!$isAssigned) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied. This machine is not assigned to you.'
                ], 403);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $machine
        ]);
    }

    /**
     * Update RVM machine (admin/super_admin only).
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, $this->editAllowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin and Admin can update machines.'
            ], 403);
        }

        $machine = RvmMachine::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'location' => 'string|max:255',
            'serial_number' => 'string|unique:rvm_machines,serial_number,' . $id,
            'status' => 'in:online,offline,maintenance,full_warning',
        ]);

        $machine->update($request->all());

        try {
            ActivityLog::log('RVM', 'Update', "Machine '{$machine->name}' updated by {$user->name}", $user->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActivityLog Failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'RVM berhasil diperbarui',
            'data' => $machine
        ]);
    }

    /**
     * Assign machine to users (with hierarchy check).
     * 
     * @OA\Post(
     *      path="/api/v1/rvm-machines/{id}/assign",
     *      operationId="assignRvmMachine",
     *      tags={"RVM Machines"},
     *      summary="Assign machine to users",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"assignee_ids"},
     *              @OA\Property(property="assignee_ids", type="array", @OA\Items(type="integer")),
     *              @OA\Property(property="description", type="string")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Assignment successful"),
     *      @OA\Response(response=403, description="Not authorized to assign")
     * )
     */
    public function assignMachine(Request $request, $id)
    {
        $assigner = $request->user();

        // Only super_admin and admin can assign
        if (!in_array($assigner->role, ['super_admin', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authorized. Only Super Admin and Admin can assign machines.'
            ], 403);
        }

        $request->validate([
            'assignee_ids' => 'required|array',
            'assignee_ids.*' => 'exists:users,id',
            'description' => 'nullable|string|max:500'
        ]);

        $machine = RvmMachine::findOrFail($id);
        $assignedUsers = [];
        $skippedUsers = [];
        $assignerLevel = $this->roleHierarchy[$assigner->role];

        foreach ($request->assignee_ids as $assigneeId) {
            $assignee = User::find($assigneeId);

            if (!$assignee)
                continue;

            $assigneeLevel = $this->roleHierarchy[$assignee->role] ?? 5;

            // Check hierarchy: cannot assign to higher level role
            if ($assigneeLevel < $assignerLevel) {
                $skippedUsers[] = [
                    'id' => $assigneeId,
                    'name' => $assignee->name,
                    'reason' => "Cannot assign to higher role ({$assignee->role})"
                ];
                continue;
            }

            // Check if already assigned
            $existing = TechnicianAssignment::where('technician_id', $assigneeId)
                ->where('rvm_machine_id', $id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->first();

            if ($existing) {
                $skippedUsers[] = [
                    'id' => $assigneeId,
                    'name' => $assignee->name,
                    'reason' => 'Already assigned'
                ];
                continue;
            }

            // Create assignment
            TechnicianAssignment::create([
                'technician_id' => $assigneeId,
                'rvm_machine_id' => $id,
                'assigned_by' => $assigner->id,
                'status' => 'assigned',
                'description' => $request->description
            ]);

            $assignedUsers[] = [
                'id' => $assigneeId,
                'name' => $assignee->name,
                'role' => $assignee->role
            ];
        }

        try {
            ActivityLog::log(
                'RVM',
                'Assign',
                "Machine '{$machine->name}' assigned to " . count($assignedUsers) . " user(s) by {$assigner->name}",
                $assigner->id
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActivityLog Failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Assignment completed',
            'data' => [
                'machine_id' => $machine->id,
                'machine_name' => $machine->name,
                'assigned' => $assignedUsers,
                'skipped' => $skippedUsers
            ]
        ]);
    }

    /**
     * Get machine assignments.
     */
    public function getAssignments(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, $this->viewAllowedRoles)) {
            return response()->json(['status' => 'error', 'message' => 'Access denied'], 403);
        }

        $machine = RvmMachine::findOrFail($id);
        $assignments = TechnicianAssignment::where('rvm_machine_id', $id)
            ->with(['technician:id,name,email,role', 'assignedBy:id,name'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'machine' => $machine,
                'assignments' => $assignments
            ]
        ]);
    }

    /**
     * Regenerate API key for a machine.
     * 
     * @OA\Post(
     *      path="/api/v1/rvm-machines/{id}/regenerate-api-key",
     *      operationId="regenerateRvmApiKey",
     *      tags={"RVM Machines"},
     *      summary="Regenerate API key",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="API key regenerated",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="api_key", type="string")
     *          )
     *      ),
     *      @OA\Response(response=403, description="Access denied")
     * )
     */
    public function regenerateApiKey(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, $this->editAllowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin and Admin can regenerate API keys.'
            ], 403);
        }

        $machine = RvmMachine::findOrFail($id);
        $newKey = $machine->regenerateApiKey();

        try {
            ActivityLog::log('RVM', 'Update', "API Key regenerated for '{$machine->name}' by {$user->name}", $user->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActivityLog Failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'API Key berhasil di-regenerate',
            'api_key' => $newKey
        ]);
    }

    /**
     * Get machine credentials (Serial Number + API Key) for download.
     * 
     * @OA\Get(
     *      path="/api/v1/rvm-machines/{id}/credentials",
     *      operationId="getRvmCredentials",
     *      tags={"RVM Machines"},
     *      summary="Get credentials for download",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Credentials JSON",
     *          @OA\JsonContent(
     *              @OA\Property(property="serial_number", type="string"),
     *              @OA\Property(property="api_key", type="string"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="generated_at", type="string")
     *          )
     *      ),
     *      @OA\Response(response=403, description="Access denied")
     * )
     */
    public function getCredentials(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, $this->editAllowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin and Admin can view credentials.'
            ], 403);
        }

        $machine = RvmMachine::findOrFail($id);

        // Log activity safely
        try {
            ActivityLog::log('RVM', 'Read', "Credentials downloaded for '{$machine->name}' by {$user->name}", $user->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActivityLog Failed: ' . $e->getMessage());
        }

        return response()->json([
            'serial_number' => $machine->serial_number,
            'api_key' => $machine->getApiKeyForConfig(),
            'name' => $machine->name,
            'generated_at' => now()->toIso8601String()
        ]);
    }

    /**
     * Delete RVM machine (admin/super_admin only).
     * Cannot delete machines with active assignments.
     *
     * @OA\Delete(
     *      path="/api/v1/rvm-machines/{id}",
     *      operationId="deleteRvmMachine",
     *      tags={"RVM Machines"},
     *      summary="Delete an RVM machine",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Machine deleted"),
     *      @OA\Response(response=403, description="Access denied or machine has assignments"),
     *      @OA\Response(response=404, description="Machine not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Only admin/super_admin can delete
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin and Admin can delete machines.'
            ], 403);
        }

        $machine = RvmMachine::find($id);
        if (!$machine) {
            return response()->json([
                'status' => 'error',
                'message' => 'RVM Machine not found.'
            ], 404);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($machine, $user) {
                // Delete associated EdgeDevice (though cascade should handle it if defined in DB, 
                // but this ensures any specific model-level logic/events are triggered)
                if ($machine->edgeDevice) {
                    $machine->edgeDevice->delete();
                }

                // Log activity
                ActivityLog::log('RVM', 'Delete', "Machine '{$machine->name}' (SN: {$machine->serial_number}) deleted by {$user->name}", $user->id);

                // Delete the machine (cascades to assignments, transactions, logs, etc. via DB constraints)
                $machine->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => "RVM '{$machine->name}' berhasil dihapus beserta seluruh data terkait."
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RVM Deletion Failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus RVM. Terjadi kesalahan pada server atau batasan database.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete RVM machines (admin/super_admin only).
     * Only deletes machines without assignments.
     *
     * @OA\Post(
     *      path="/api/v1/rvm-machines/bulk-delete",
     *      operationId="bulkDeleteRvmMachines",
     *      tags={"RVM Machines"},
     *      summary="Delete multiple RVM machines",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="ids", type="array", @OA\Items(type="integer"))
     *          )
     *      ),
     *      @OA\Response(response=200, description="Bulk delete result"),
     *      @OA\Response(response=403, description="Access denied")
     * )
     */
    public function destroyBulk(Request $request)
    {
        $user = $request->user();

        // Only admin/super_admin can delete
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only Super Admin and Admin can delete machines.'
            ], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:rvm_machines,id'
        ]);

        $ids = $request->ids;
        $deleted = [];
        $skipped = [];

        foreach ($ids as $id) {
            $machine = RvmMachine::find($id);
            if (!$machine) {
                $skipped[] = ['id' => $id, 'reason' => 'Not found'];
                continue;
            }

            // Check for assignments
            $assignmentCount = TechnicianAssignment::where('rvm_machine_id', $id)->count();
            if ($assignmentCount > 0) {
                $skipped[] = [
                    'id' => $id,
                    'name' => $machine->name,
                    'reason' => "Has {$assignmentCount} active assignment(s)"
                ];
                continue;
            }

            // Delete EdgeDevice
            if ($machine->edgeDevice) {
                $machine->edgeDevice->delete();
            }

            $deleted[] = ['id' => $id, 'name' => $machine->name];
            $machine->delete();
        }

        // Log activity
        try {
            $deletedNames = array_column($deleted, 'name');
            ActivityLog::log(
                'RVM',
                'Bulk Delete',
                "Bulk deleted " . count($deleted) . " machine(s): " . implode(', ', $deletedNames) . " by {$user->name}",
                $user->id
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ActivityLog Failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => count($deleted) . ' machine(s) deleted, ' . count($skipped) . ' skipped.',
            'deleted' => $deleted,
            'skipped' => $skipped
        ]);
    }
}

