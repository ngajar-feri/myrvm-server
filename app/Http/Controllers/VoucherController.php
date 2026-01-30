<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class VoucherController extends Controller
{
    /**
     * Store a newly created voucher in storage.
     */
    public function store(Request $request)
    {
        // Ensure only 'tenan' can create vouchers
        if (Auth::user()->role !== 'tenan') {
            abort(403, 'Hanya Tenant yang dapat membuat voucher.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:0',
            'valid_until' => 'required|date|after:today',
            'code' => 'nullable|string|unique:vouchers,code',
            'status' => 'required|in:active,inactive',
        ]);

        $voucher = new Voucher($validated);
        $voucher->tenant_id = Auth::id(); // Force ownership
        $voucher->save();

        return redirect()->route('dashboard')->with('success', 'Voucher berhasil dibuat.');
    }

    /**
     * Update the specified voucher in storage.
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        // Authorization Check
        if ($voucher->tenant_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:0',
            'valid_until' => 'required|date|after:today',
            'status' => 'required|in:active,inactive',
        ]);

        $voucher->update($validated);

        return redirect()->route('dashboard')->with('success', 'Voucher berhasil diperbarui.');
    }

    /**
     * Remove the specified voucher from storage.
     */
    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);

        if ($voucher->tenant_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
        }

        $voucher->delete();

        return redirect()->route('dashboard')->with('success', 'Voucher berhasil dihapus.');
    }

    /**
     * API Endpoint for Mobile Apps to list active vouchers.
     * 
     * @OA\Get(
     *      path="/api/v1/vouchers",
     *      operationId="getVouchersList",
     *      tags={"Vouchers"},
     *      summary="Get list of active vouchers",
     *      description="Returns list of vouchers that are active and valid",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Server Error"
     *      )
     * )
     */
    public function apiIndex()
    {
        $vouchers = Voucher::with('tenant:id,name')
            ->where('status', 'active')
            ->whereDate('valid_until', '>=', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }
}
