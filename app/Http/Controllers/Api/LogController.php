<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

use App\Exports\ActivityLogExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class LogController extends Controller
{
    /**
     * Allowed roles for accessing logs.
     */
    private $allowedRoles = ['super_admin', 'admin', 'operator', 'teknisi'];

    /**
     * Get activity logs from database.
     * 
     * @OA\Get(
     *      path="/api/v1/logs",
     *      operationId="getActivityLogs",
     *      tags={"Logs"},
     *      summary="Read activity logs (Roles: super_admin, admin, operator, teknisi)",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Items per page",
     *          required=false,
     *          @OA\Schema(type="integer", default=20)
     *      ),
     *      @OA\Parameter(
     *          name="module",
     *          in="query",
     *          description="Filter by module (Auth, Device, Machine, System)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="action",
     *          in="query",
     *          description="Filter by action (Login, Update, Error, Warning)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="date_from",
     *          in="query",
     *          description="Filter from date (YYYY-MM-DD)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="date_to",
     *          in="query",
     *          description="Filter to date (YYYY-MM-DD)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Activity logs list",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden - Insufficient role"
     *      )
     * )
     */
    public function index(Request $request)
    {
        // Check role-based access
        $user = $request->user();
        if (!in_array($user->role, $this->allowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Insufficient permissions.'
            ], 403);
        }

        $perPage = $request->input('per_page', 20);

        $query = ActivityLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('module')) {
            $query->module($request->module);
        }

        if ($request->filled('action')) {
            $query->action($request->action);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateBetween($request->date_from, $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $logs->items(),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
        ]);
    }

    /**
     * Get log statistics.
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, $this->allowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied.'
            ], 403);
        }

        $today = now()->toDateString();

        return response()->json([
            'status' => 'success',
            'stats' => [
                'total' => ActivityLog::count(),
                'today' => ActivityLog::whereDate('created_at', $today)->count(),
                'errors' => ActivityLog::where('action', 'Error')->count(),
                'warnings' => ActivityLog::where('action', 'Warning')->count(),
                'modules' => ActivityLog::select('module')
                    ->distinct()
                    ->pluck('module'),
                'actions' => ActivityLog::select('action')
                    ->distinct()
                    ->pluck('action'),
            ]
        ]);
    }
    /**
     * Export activity logs to Excel or PDF.
     */
    public function export(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, $this->allowedRoles)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $query = ActivityLog::with('user:id,name,email')->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('module'))
            $query->module($request->module);
        if ($request->filled('action'))
            $query->action($request->action);
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateBetween($request->date_from, $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Limit to 1000 records for performance if not specified
        $limit = $request->input('per_page', 1000);
        $logs = $query->limit($limit)->get();

        if ($request->input('format') === 'excel') {
            return Excel::download(new ActivityLogExport($logs), 'activity_logs_' . date('Y-m-d_H-i') . '.xlsx');
        }

        if ($request->input('format') === 'pdf') {
            // Ensure memory limit is sufficient for PDF generation
            ini_set('memory_limit', '256M');
            ini_set('max_execution_time', 300);

            $pdf = Pdf::loadView('dashboard.logs.pdf', ['logs' => $logs]);

            // Set Paper & Options
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

            return $pdf->download('activity_logs_' . date('Y-m-d_H-i') . '.pdf');
        }

        return response()->json(['message' => 'Invalid format'], 400);
    }
}
