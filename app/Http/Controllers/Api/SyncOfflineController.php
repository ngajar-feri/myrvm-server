<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\RvmMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SyncOfflineController
 * 
 * Handles bulk synchronization of offline transactions from Edge devices.
 * Transactions are stored locally on Edge during network outages and
 * synced to server when connection is restored.
 */
class SyncOfflineController extends Controller
{
    /**
     * Sync offline transactions from Edge device.
     * 
     * POST /api/v1/edge/sync-offline
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncOffline(Request $request)
    {
        $request->validate([
            'transactions' => 'required|array|min:1',
            'transactions.*.session_id' => 'required|string',
            'transactions.*.user_id' => 'required|integer',
            'transactions.*.timestamp' => 'required|string',
            'transactions.*.items' => 'required|array',
            'transactions.*.items.*.type' => 'required|string',
            'transactions.*.items.*.weight' => 'numeric',
            'transactions.*.items.*.points' => 'integer',
        ]);
        
        $transactions = $request->input('transactions');
        $syncedCount = 0;
        $errors = [];
        
        // Get RVM machine from authenticated edge device
        $rvmMachine = $this->getAuthenticatedRvmMachine($request);
        
        DB::beginTransaction();
        
        try {
            foreach ($transactions as $offlineTx) {
                // Check if already synced (idempotency)
                $existingSession = Transaction::where('session_id', $offlineTx['session_id'])->first();
                if ($existingSession) {
                    Log::info("Skipping duplicate session: {$offlineTx['session_id']}");
                    $syncedCount++; // Count as success (already synced)
                    continue;
                }
                
                // Verify user exists
                $user = User::find($offlineTx['user_id']);
                if (!$user) {
                    $errors[] = "User not found: {$offlineTx['user_id']}";
                    continue;
                }
                
                // Calculate totals from items
                $totalPoints = 0;
                $totalWeight = 0;
                $totalItems = count($offlineTx['items']);
                
                foreach ($offlineTx['items'] as $item) {
                    $totalPoints += $item['points'] ?? 0;
                    $totalWeight += $item['weight'] ?? 0;
                }
                
                // Create transaction
                $transaction = Transaction::create([
                    'user_id' => $offlineTx['user_id'],
                    'rvm_machine_id' => $rvmMachine?->id,
                    'session_id' => $offlineTx['session_id'],
                    'total_points' => $totalPoints,
                    'total_weight' => $totalWeight,
                    'total_items' => $totalItems,
                    'status' => 'completed',
                    'started_at' => $offlineTx['timestamp'],
                    'completed_at' => now(),
                ]);
                
                // Create transaction items
                foreach ($offlineTx['items'] as $item) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'waste_type' => $item['type'],
                        'weight' => $item['weight'] ?? 0,
                        'points' => $item['points'] ?? 0,
                    ]);
                }
                
                // Update user points
                $user->increment('points_balance', $totalPoints);
                
                $syncedCount++;
                Log::info("Synced offline transaction: {$offlineTx['session_id']}");
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => "Synced {$syncedCount} offline transactions",
                'synced_count' => $syncedCount,
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Offline sync failed: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed: ' . $e->getMessage(),
                'synced_count' => 0,
            ], 500);
        }
    }
    
    /**
     * Get the System Donation user ID for offline mode.
     * 
     * GET /api/v1/edge/system-donation-user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemDonationUser()
    {
        $user = User::where('name', 'System Donation')->first();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'System Donation user not found. Please run seeder.',
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'system_donation_user_id' => $user->id,
            'user_name' => $user->name,
        ]);
    }
    
    /**
     * Get authenticated RVM machine from request.
     */
    private function getAuthenticatedRvmMachine(Request $request): ?RvmMachine
    {
        // Try to get from bearer token or request header
        $machineId = $request->header('X-RVM-Machine-ID');
        
        if ($machineId) {
            return RvmMachine::find($machineId);
        }
        
        return null;
    }
}
