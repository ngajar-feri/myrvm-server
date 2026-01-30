<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\UserVoucher;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RedemptionController extends Controller
{
    /**
     * Redeem voucher with points.
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
        ]);

        $voucher = Voucher::findOrFail($request->voucher_id);
        $user = $request->user();

        if ($user->points_balance < $voucher->points_required) {
            return response()->json([
                'status' => 'error',
                'message' => 'Poin tidak mencukupi'
            ], 400);
        }

        $userVoucher = null;

        DB::transaction(function () use ($user, $voucher, &$userVoucher) {
            // Deduct points
            $user->decrement('points_balance', $voucher->points_required);

            // Create User Voucher
            $userVoucher = UserVoucher::create([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
                'unique_code' => strtoupper(Str::random(10)),
                'status' => 'active',
                'expires_at' => now()->addDays(30), // Default 30 days validity
            ]);
        });

        ActivityLog::log('Redemption', 'Redeem', "User {$user->name} redeemed voucher '{$voucher->title}' for {$voucher->points_required} points", $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil ditukar',
            'data' => $userVoucher
        ]);
    }

    /**
     * Validate voucher (Tenant Side).
     */
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userVoucher = UserVoucher::where('unique_code', $request->code)
            ->with(['voucher', 'user'])
            ->first();

        if (!$userVoucher) {
            return response()->json(['message' => 'Voucher tidak valid'], 404);
        }

        if ($userVoucher->status !== 'active') {
            return response()->json(['message' => 'Voucher sudah digunakan atau kadaluarsa'], 400);
        }

        if ($userVoucher->expires_at && now()->greaterThan($userVoucher->expires_at)) {
            return response()->json(['message' => 'Voucher kadaluarsa'], 400);
        }

        // Check Tenant Ownership
        // Only owner of the voucher template can validate it
        if ($userVoucher->voucher->tenant_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized: Voucher ini bukan milik tenant Anda'], 403);
        }

        // Mark as used
        $userVoucher->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        ActivityLog::log('Redemption', 'Validate', "Tenant {$request->user()->name} validated voucher code {$request->code}", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher valid dan berhasil digunakan',
            'data' => $userVoucher
        ]);
    }

    /**
     * Get user's vouchers.
     */
    public function getUserVouchers(Request $request)
    {
        $vouchers = UserVoucher::where('user_id', $request->user()->id)
            ->with('voucher:id,name,description,discount_type,discount_value')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $vouchers->map(function ($userVoucher) {
                return [
                    'id' => $userVoucher->id,
                    'unique_code' => $userVoucher->unique_code,
                    'status' => $userVoucher->status,
                    'voucher' => $userVoucher->voucher,
                    'expires_at' => $userVoucher->expires_at,
                    'used_at' => $userVoucher->used_at,
                    'created_at' => $userVoucher->created_at
                ];
            })
        ]);
    }

    /**
     * Get voucher detail by code.
     */
    public function getVoucherDetail($code)
    {
        $userVoucher = UserVoucher::where('unique_code', $code)
            ->with(['voucher', 'user:id,name,email'])
            ->first();

        if (!$userVoucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $userVoucher
        ]);
    }
}
