<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * Update user profile.
     * 
     * @OA\Put(
     *      path="/api/v1/profile",
     *      operationId="updateProfile",
     *      tags={"User"},
     *      summary="Update user profile information",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email"},
     *              @OA\Property(property="name", type="string", example="Jane Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="jane@example.com")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Profil berhasil diperbarui"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        ActivityLog::log('User', 'Update', "User {$user->name} updated profile", $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ]);
    }

    /**
     * Change password.
     * 
     * @OA\Put(
     *      path="/api/v1/change-password",
     *      operationId="changePassword",
     *      tags={"User"},
     *      summary="Change user password",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"current_password","new_password","new_password_confirmation"},
     *              @OA\Property(property="current_password", type="string", format="password", example="oldsecret"),
     *              @OA\Property(property="new_password", type="string", format="password", example="newsecret"),
     *              @OA\Property(property="new_password_confirmation", type="string", format="password", example="newsecret")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Password changed successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Password berhasil diubah")
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password saat ini salah',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        ActivityLog::log('User', 'Security', "User {$user->name} changed password", $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah',
        ]);
    }

    /**
     * Get user balance.
     */
    public function balance(Request $request)
    {
        $user = $request->user();

        // Calculate total earned
        $totalEarned = \DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('total_points') ?? 0;

        // Calculate total redeemed (join user_vouchers with vouchers to get points_required)
        $totalRedeemed = \DB::table('user_vouchers')
            ->join('vouchers', 'user_vouchers.voucher_id', '=', 'vouchers.id')
            ->where('user_vouchers.user_id', $user->id)
            ->sum('vouchers.points_required') ?? 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $user->id,
                'points_balance' => $user->points_balance ?? 0,
                'tier' => 'silver', // TODO: Implement tier logic
                'total_earned' => $totalEarned,
                'total_redeemed' => $totalRedeemed
            ]
        ]);
    }

    /**
     * Upload profile photo.
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:2048', // 2MB
        ]);

        $user = $request->user();

        // Delete old photo if exists
        if ($user->photo_url) {
            $oldPath = str_replace('/storage/', '', $user->photo_url);
            \Storage::disk('public')->delete($oldPath);
        }

        // Upload new photo
        $path = $request->file('photo')
            ->store('profile-photos', 'public');

        $user->update([
            'photo_url' => \Storage::url($path)
        ]);

        ActivityLog::log('User', 'Update', "User {$user->name} uploaded profile photo", $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile photo uploaded',
            'data' => [
                'photo_url' => $user->photo_url
            ]
        ]);
    }

    /**
     * Get all users for admin dashboard.
     * Supports search, role filter, status filter, and pagination.
     */
    public function getAllUsers(Request $request)
    {
        $query = \App\Models\User::select('id', 'name', 'email', 'role', 'status', 'points_balance', 'created_at');

        // Search filter (name or email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Order by created_at descending
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get global user statistics for dashboard.
     */
    public function getGlobalStats()
    {
        $total = \App\Models\User::count();

        // Active users
        $active = \App\Models\User::where('status', 'active')->count();

        // Count tenants
        $tenants = \App\Models\User::whereIn('role', ['tenan', 'tenant'])->count();

        // New users today
        $newToday = \App\Models\User::whereDate('created_at', today())->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'active' => $active,
                'tenants' => $tenants,
                'new_today' => $newToday
            ]
        ]);
    }

    /**
     * Toggle user status (Operator, Teknisi, Admin, Super Admin).
     * Operator and Teknisi require Super Admin password verification.
     */
    public function toggleStatus(Request $request, $id)
    {
        $currentUser = $request->user();

        // Check if user has permission
        if (!in_array($currentUser->role, ['super_admin', 'admin', 'operator', 'teknisi'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to change user status'
            ], 403);
        }

        // Operator and Teknisi require Super Admin password
        if (in_array($currentUser->role, ['operator', 'teknisi'])) {
            $request->validate([
                'password' => 'required|string',
            ]);

            // Get any Super Admin to verify password
            $superAdmin = \App\Models\User::where('role', 'super_admin')->first();
            if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Super Admin password'
                ], 401);
            }
        }

        $user = \App\Models\User::findOrFail($id);

        // Prevent changing own status
        if ($user->id === $currentUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot change your own status'
            ], 400);
        }

        // Toggle status
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLog::log('User', 'StatusChange', "{$currentUser->name} changed {$user->name}'s status to {$newStatus}", $currentUser->id);

        return response()->json([
            'status' => 'success',
            'message' => "User status changed to {$newStatus}",
            'data' => ['new_status' => $newStatus]
        ]);
    }

    /**
     * Toggle multiple users status (Operator, Teknisi, Admin, Super Admin).
     * Operator and Teknisi require Super Admin password verification.
     */
    public function toggleMultipleStatus(Request $request)
    {
        $currentUser = $request->user();

        // Check if user has permission
        if (!in_array($currentUser->role, ['super_admin', 'admin', 'operator', 'teknisi'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to change user status'
            ], 403);
        }

        // Validate request
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'new_status' => 'required|string|in:active,inactive',
        ]);

        // Operator and Teknisi require Super Admin password
        if (in_array($currentUser->role, ['operator', 'teknisi'])) {
            $request->validate([
                'password' => 'required|string',
            ]);

            // Get any Super Admin to verify password
            $superAdmin = \App\Models\User::where('role', 'super_admin')->first();
            if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Super Admin password'
                ], 401);
            }
        }

        $userIds = $request->user_ids;
        $newStatus = $request->new_status;
        $updatedCount = 0;
        $skipped = [];

        foreach ($userIds as $userId) {
            $user = \App\Models\User::find($userId);

            if (!$user) {
                continue;
            }

            // Prevent changing own status
            if ($user->id === $currentUser->id) {
                $skipped[] = ['id' => $userId, 'reason' => 'Cannot change own status'];
                continue;
            }

            $user->update(['status' => $newStatus]);
            $updatedCount++;

            ActivityLog::log('User', 'BulkStatusChange', "{$currentUser->name} changed {$user->name}'s status to {$newStatus}", $currentUser->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => "{$updatedCount} user(s) status changed to {$newStatus}",
            'updated_count' => $updatedCount,
            'skipped' => $skipped
        ]);
    }

    /**
     * Get user statistics for dashboard.
     */
    public function getUserStats($id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);

            // Calculate stats
            $totalTransactions = \DB::table('transactions')
                ->where('user_id', $id)
                ->where('status', 'completed')
                ->count();

            $totalPoints = \DB::table('transactions')
                ->where('user_id', $id)
                ->where('status', 'completed')
                ->sum('total_points') ?? 0;

            // Calculate total redeemed (join user_vouchers with vouchers to get points_required)
            $totalRedeemed = \DB::table('user_vouchers')
                ->join('vouchers', 'user_vouchers.voucher_id', '=', 'vouchers.id')
                ->where('user_vouchers.user_id', $id)
                ->sum('vouchers.points_required') ?? 0;

            // Points history (last 7 days)
            $pointsHistory = \DB::table('transactions')
                ->where('user_id', $id)
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays(7))
                ->select(
                    \DB::raw('DATE(created_at) as date'),
                    \DB::raw('SUM(total_points) as points')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'status' => 'success',
                'user' => $user,
                'stats' => [
                    'total_transactions' => $totalTransactions,
                    'total_earned' => $totalPoints,
                    'total_redeemed' => $totalRedeemed,
                    'current_balance' => $user->points_balance ?? 0,
                    'points_history' => $pointsHistory
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load user stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new user (Admin only).
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:user,admin,super_admin,operator,teknisi,tenan',
            'status' => 'nullable|string|in:active,inactive',
            'points_balance' => 'nullable|integer|min:0',
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
            'points_balance' => $request->points_balance ?? 0,
        ]);

        ActivityLog::log('User', 'Create', "Admin {$request->user()->name} created user: {$user->name} ({$user->email})", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update user (Admin only).
     */
    public function updateUserAdmin(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email,' . $id,
            'role' => 'required|string|in:user,admin,super_admin,operator,teknisi,tenan',
            'status' => 'nullable|string|in:active,inactive',
            'points_balance' => 'nullable|integer|min:0',
            'password' => 'nullable|string|min:8',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status ?? $user->status,
            'points_balance' => $request->points_balance ?? $user->points_balance,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        ActivityLog::log('User', 'Update', "Admin {$request->user()->name} updated user: {$user->name} ({$user->email})", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete user permanently (Admin only).
     * Requires password verification for security.
     */
    public function deleteUser(Request $request, $id)
    {
        // Check if user has delete permission (super_admin or admin only)
        $currentUser = $request->user();
        if (!in_array($currentUser->role, ['super_admin', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete users'
            ], 403);
        }

        // Validate password
        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, $currentUser->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid password. Please enter your correct password.'
            ], 401);
        }

        $user = \App\Models\User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === $currentUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        // Prevent deleting super_admin by non-super_admin
        if ($user->role === 'super_admin' && $currentUser->role !== 'super_admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only Super Admin can delete other Super Admin accounts'
            ], 403);
        }

        $userName = $user->name;
        $userEmail = $user->email;

        // Hard delete
        $user->delete();

        ActivityLog::log('User', 'Delete', "Admin {$currentUser->name} deleted user: {$userName} ({$userEmail})", $currentUser->id);

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Verify admin password (for secure operations).
     */
    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $currentUser = $request->user();

        if (!Hash::check($request->password, $currentUser->password)) {
            return response()->json([
                'status' => 'error',
                'valid' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'valid' => true,
            'message' => 'Password verified'
        ]);
    }

    /**
     * Delete multiple users (Admin only).
     * Requires password verification for security.
     */
    public function deleteMultipleUsers(Request $request)
    {
        // Check if user has delete permission (super_admin or admin only)
        $currentUser = $request->user();
        if (!in_array($currentUser->role, ['super_admin', 'admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete users'
            ], 403);
        }

        // Validate request
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, $currentUser->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid password. Please enter your correct password.'
            ], 401);
        }

        $userIds = $request->user_ids;
        $deletedCount = 0;
        $skipped = [];

        foreach ($userIds as $userId) {
            $user = \App\Models\User::find($userId);

            if (!$user) {
                continue;
            }

            // Prevent self-deletion
            if ($user->id === $currentUser->id) {
                $skipped[] = ['id' => $userId, 'reason' => 'Cannot delete own account'];
                continue;
            }

            // Prevent deleting super_admin by non-super_admin
            if ($user->role === 'super_admin' && $currentUser->role !== 'super_admin') {
                $skipped[] = ['id' => $userId, 'name' => $user->name, 'reason' => 'Insufficient permission'];
                continue;
            }

            $userName = $user->name;
            $userEmail = $user->email;

            $user->delete();
            $deletedCount++;

            ActivityLog::log('User', 'BulkDelete', "Admin {$currentUser->name} deleted user: {$userName} ({$userEmail})", $currentUser->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => "{$deletedCount} user(s) deleted successfully",
            'deleted_count' => $deletedCount,
            'skipped' => $skipped
        ]);
    }
}
