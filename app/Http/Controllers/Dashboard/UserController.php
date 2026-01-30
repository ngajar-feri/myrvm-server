<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display user management page (full page load).
     */
    public function index()
    {
        return view('dashboard.users.index');
    }

    /**
     * Return content only for SPA navigation.
     */
    public function indexContent()
    {
        return view('dashboard.users.index-content');
    }

    /**
     * Get users list via AJAX (for DataTable).
     */
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        $role = $request->get('role');
        $status = $request->get('status');

        $query = User::query();

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role) {
            $query->where('role', $role);
        }

        // Status filter (assuming there's a status column)
        if ($status) {
            $query->where('status', $status);
        }

        $users = $query->latest()->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get user statistics.
     */
    public function getUserStats($id)
    {
        $user = User::findOrFail($id);

        // Calculate stats
        $totalTransactions = DB::table('transactions')
            ->where('user_id', $id)
            ->where('status', 'completed')
            ->count();

        $totalPoints = DB::table('transactions')
            ->where('user_id', $id)
            ->where('status', 'completed')
            ->sum('total_points');

        $totalRedeemed = DB::table('user_vouchers')
            ->where('user_id', $id)
            ->sum('points_used') ?? 0;

        // Points history (last 7 days)
        $pointsHistory = DB::table('transactions')
            ->where('user_id', $id)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_points) as points')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'user' => $user,
            'stats' => [
                'total_transactions' => $totalTransactions,
                'total_earned' => $totalPoints,
                'total_redeemed' => $totalRedeemed,
                'current_balance' => $user->points_balance,
                'points_history' => $pointsHistory
            ]
        ]);
    }
}
