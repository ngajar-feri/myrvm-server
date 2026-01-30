<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\RvmMachine;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Start a new transaction session.
     */
    public function start(Request $request)
    {
        $request->validate([
            'rvm_id' => 'required|exists:rvm_machines,id',
        ]);

        // Check if user has pending transaction
        $existing = Transaction::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'message' => 'Resuming existing session',
                'data' => $existing
            ]);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'rvm_machine_id' => $request->rvm_id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        ActivityLog::log('Transaction', 'Create', "Transaction #{$transaction->id} started by {$request->user()->name}", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Session started',
            'data' => $transaction
        ], 201);
    }

    /**
     * Deposit item to current session.
     */
    public function depositItem(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'waste_type' => 'required|string',
            'weight' => 'required|numeric|min:0.001',
            'points' => 'required|integer|min:1',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);

        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Transaction already completed'], 400);
        }

        $item = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'waste_type' => $request->waste_type,
            'weight' => $request->weight,
            'points' => $request->points,
        ]);

        // Update totals
        $transaction->increment('total_points', $request->points);
        $transaction->increment('total_weight', $request->weight);
        $transaction->increment('total_items');

        return response()->json([
            'status' => 'success',
            'message' => 'Item deposited',
            'data' => $item
        ]);
    }

    /**
     * Commit/Finish transaction session.
     */
    public function commit(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);

        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Transaction already completed'], 400);
        }

        DB::transaction(function () use ($transaction, $request) {
            $transaction->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Add points to user balance
            $request->user()->increment('points_balance', $transaction->total_points);
        });

        ActivityLog::log('Transaction', 'Complete', "Transaction #{$transaction->id} completed, {$transaction->total_points} points earned", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction completed',
            'data' => $transaction
        ]);
    }

    /**
     * Create QR session for mobile app.
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'rvm_id' => 'nullable|exists:rvm_machines,id',
        ]);

        // Check if user has active session
        $existing = DB::table('user_sessions')
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            // Generate QR from existing session
            $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->generate($existing->session_code));

            return response()->json([
                'status' => 'success',
                'message' => 'Resuming existing session',
                'data' => [
                    'session_id' => $existing->id,
                    'session_code' => $existing->session_code,
                    'qr_code_data' => $qrCode,
                    'expires_at' => $existing->expires_at,
                    'expires_in_seconds' => max(0, now()->diffInSeconds($existing->expires_at, false))
                ]
            ]);
        }

        // Create new session
        $sessionCode = \Illuminate\Support\Str::uuid()->toString();
        $expiresAt = now()->addMinutes(5);

        $sessionId = DB::table('user_sessions')->insertGetId([
            'user_id' => $request->user()->id,
            'rvm_machine_id' => $request->rvm_id,
            'session_code' => $sessionCode,
            'status' => 'pending',
            'qr_generated_at' => now(),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Generate QR Code
        $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->generate($sessionCode));

        return response()->json([
            'status' => 'success',
            'data' => [
                'session_id' => $sessionId,
                'session_code' => $sessionCode,
                'qr_code_data' => $qrCode,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_seconds' => 300
            ]
        ], 201);
    }

    /**
     * Cancel transaction.
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);

        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending transactions can be cancelled'
            ], 400);
        }

        $previousStatus = $transaction->status;
        $itemsCount = $transaction->total_items;

        $transaction->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        ActivityLog::log('Transaction', 'Cancel', "Transaction #{$transaction->id} cancelled", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction cancelled',
            'data' => [
                'transaction_id' => $transaction->id,
                'previous_status' => $previousStatus,
                'new_status' => 'cancelled',
                'items_count' => $itemsCount
            ]
        ]);
    }

    /**
     * Get transaction history with pagination.
     */
    public function history(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:pending,completed,cancelled',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = Transaction::where('user_id', $request->user()->id)
            ->with('rvmMachine:id,name,location');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date) {
            $query->whereDate('started_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('started_at', '<=', $request->to_date);
        }

        $perPage = $request->per_page ?? 20;
        $transactions = $query->orderBy('started_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
                'data' => $transactions->items()
            ]
        ]);
    }

    /**
     * Show transaction detail.
     */
    public function show($id, Request $request)
    {
        $transaction = Transaction::with(['rvmMachine:id,name,location', 'items'])
            ->findOrFail($id);

        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $transaction->id,
                'user' => [
                    'id' => $transaction->user_id,
                    'name' => $request->user()->name
                ],
                'rvm_machine' => $transaction->rvmMachine ? [
                    'id' => $transaction->rvmMachine->id,
                    'name' => $transaction->rvmMachine->name,
                    'location' => $transaction->rvmMachine->location
                ] : null,
                'status' => $transaction->status,
                'items' => $transaction->items,
                'totals' => [
                    'items' => $transaction->total_items,
                    'weight' => $transaction->total_weight,
                    'points' => $transaction->total_points,
                    'value' => $transaction->total_points * 10 // Rp 10 per point
                ],
                'timestamps' => [
                    'started_at' => $transaction->started_at,
                    'completed_at' => $transaction->completed_at
                ]
            ]
        ]);
    }

    /**
     * Get active transaction session.
     */
    public function getActiveSession(Request $request)
    {
        $activeTransaction = Transaction::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->with(['rvmMachine:id,name,location', 'items'])
            ->first();

        if (!$activeTransaction) {
            return response()->json([
                'status' => 'success',
                'data' => null,
                'message' => 'No active transaction'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $activeTransaction->id,
                'rvm_machine' => $activeTransaction->rvmMachine,
                'status' => $activeTransaction->status,
                'items_count' => $activeTransaction->total_items,
                'total_points' => $activeTransaction->total_points,
                'started_at' => $activeTransaction->started_at
            ]
        ]);
    }
}
