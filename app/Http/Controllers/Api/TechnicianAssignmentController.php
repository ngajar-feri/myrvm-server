<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * TechnicianAssignment API Controller
 * 
 * Manages static access rights (Hak Akses Tetap) for technicians to RVM machines.
 * 
 * Business Rules:
 * - Only super_admin and admin can create/delete assignments
 * - Admin cannot assign to higher roles (super_admin, other admins)
 * - Admin can assign to: self, teknisi, operator
 */
class TechnicianAssignmentController extends Controller
{
    /**
     * Role hierarchy (lower number = higher privilege)
     */
    private const ROLE_HIERARCHY = [
        'super_admin' => 1,
        'admin' => 2,
        'operator' => 3,
        'teknisi' => 4,
        'tenant' => 5,
        'user' => 6,
    ];

    /**
     * Get all assignments with relationships
     */
    public function index(): JsonResponse
    {
        $assignments = TechnicianAssignment::with(['technician', 'rvmMachine', 'assignedBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'user' => $a->technician ? [
                        'id' => $a->technician->id,
                        'name' => $a->technician->name,
                        'email' => $a->technician->email,
                        'role' => $a->technician->role,
                    ] : null,
                    'rvm_machine' => $a->rvmMachine ? [
                        'id' => $a->rvmMachine->id,
                        'name' => $a->rvmMachine->name,
                        'location' => $a->rvmMachine->location,
                        'serial_number' => $a->rvmMachine->serial_number,
                    ] : null,
                    'assigned_by' => $a->assignedBy?->name,
                    'assigned_at' => $a->created_at,
                    'status' => $a->status,
                ];
            });

        return response()->json(['data' => $assignments]);
    }

    /**
     * Create new assignment
     * 
     * Validates role hierarchy before creation.
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = Auth::user();

        // Guard: Only super_admin and admin can create assignments
        if (!in_array($currentUser->role, ['super_admin', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized. Only Admin can create assignments.'
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'rvm_machine_id' => 'required|exists:rvm_machines,id',
        ]);

        // Get target user role
        $targetUser = \App\Models\User::find($validated['user_id']);
        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Guard: Role hierarchy check
        if (!$this->canAssignToRole($currentUser->role, $targetUser->role, $currentUser->id, $targetUser->id)) {
            return response()->json([
                'message' => 'Cannot assign to this role. You can only assign to lower or equal roles.'
            ], 403);
        }

        // Check for duplicate assignment
        $exists = TechnicianAssignment::where('technician_id', $validated['user_id'])
            ->where('rvm_machine_id', $validated['rvm_machine_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'User already assigned to this RVM machine.'
            ], 422);
        }

        // Check if RVM Machine has API Key, if not generate one (First assignment initialization)
        $rvmMachine = \App\Models\RvmMachine::find($validated['rvm_machine_id']);
        if ($rvmMachine && empty($rvmMachine->api_key)) {
            $rvmMachine->regenerateApiKey();
        }

        $assignment = TechnicianAssignment::create([
            'technician_id' => $validated['user_id'],
            'rvm_machine_id' => $validated['rvm_machine_id'],
            'assigned_by' => $currentUser->id,
            'status' => 'assigned',
        ]);

        return response()->json([
            'message' => 'Assignment created successfully',
            'data' => $assignment
        ], 201);
    }

    /**
     * Delete assignment
     */
    public function destroy(int $id): JsonResponse
    {
        $currentUser = Auth::user();

        // Guard: Only super_admin and admin can delete
        if (!in_array($currentUser->role, ['super_admin', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized. Only Admin can remove assignments.'
            ], 403);
        }

        $assignment = TechnicianAssignment::find($id);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment removed successfully']);
    }

    /**
     * Get single assignment detail
     */
    public function show(int $id): JsonResponse
    {
        $assignment = TechnicianAssignment::with(['technician', 'rvmMachine', 'assignedBy'])->find($id);

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $assignment->id,
                'user' => $assignment->technician ? [
                    'id' => $assignment->technician->id,
                    'name' => $assignment->technician->name,
                    'email' => $assignment->technician->email,
                    'role' => $assignment->technician->role,
                ] : null,
                'rvm_machine' => $assignment->rvmMachine ? [
                    'id' => $assignment->rvmMachine->id,
                    'name' => $assignment->rvmMachine->name,
                    'location' => $assignment->rvmMachine->location,
                    'serial_number' => $assignment->rvmMachine->serial_number,
                ] : null,
                'assigned_by' => $assignment->assignedBy?->name,
                'status' => $assignment->status,
                'description' => $assignment->description,
                'access_pin' => $assignment->access_pin,
                'pin_expires_at' => $assignment->pin_expires_at,
                'created_at' => $assignment->created_at,
                'updated_at' => $assignment->updated_at,
            ]
        ]);
    }

    /**
     * Update assignment (status, description, etc.)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $currentUser = Auth::user();

        // Guard: Only super_admin and admin can update
        if (!in_array($currentUser->role, ['super_admin', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized. Only Admin can update assignments.'
            ], 403);
        }

        $assignment = TechnicianAssignment::find($id);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:assigned,active,suspended,revoked',
            'description' => 'nullable|string|max:500',
        ]);

        if (isset($validated['status'])) {
            $assignment->status = $validated['status'];
        }
        if (isset($validated['description'])) {
            $assignment->description = $validated['description'];
        }

        $assignment->save();

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => $assignment->load(['technician', 'rvmMachine'])
        ]);
    }

    /**
     * Generate access PIN for assignment
     */
    public function generatePin(int $id): JsonResponse
    {
        $assignment = TechnicianAssignment::find($id);
        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        // Generate 6-digit PIN
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $assignment->access_pin = $pin;
        $assignment->pin_expires_at = now()->addHours(24);
        $assignment->save();

        return response()->json([
            'message' => 'PIN generated successfully',
            'data' => [
                'pin' => $pin,
                'expires_at' => $assignment->pin_expires_at
            ]
        ]);
    }

    /**
     * Get assignments for a specific RVM (for ticket assignment dropdown)
     */
    public function getByRvm(int $rvmId): JsonResponse
    {
        $assignments = TechnicianAssignment::with('technician')
            ->where('rvm_machine_id', $rvmId)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->technician_id,
                    'name' => $a->technician?->name,
                    'email' => $a->technician?->email,
                    'role' => $a->technician?->role,
                ];
            });

        return response()->json(['data' => $assignments]);
    }

    /**
     * Check if current user role can assign to target role
     */
    private function canAssignToRole(string $currentRole, string $targetRole, int $currentId, int $targetId): bool
    {
        // Self-assignment is always allowed
        if ($currentId === $targetId) {
            return true;
        }

        $currentLevel = self::ROLE_HIERARCHY[$currentRole] ?? 99;
        $targetLevel = self::ROLE_HIERARCHY[$targetRole] ?? 99;

        // super_admin can assign anyone
        if ($currentRole === 'super_admin') {
            return true;
        }

        // admin can only assign to lower privilege roles (higher number)
        return $targetLevel > $currentLevel;
    }
}
