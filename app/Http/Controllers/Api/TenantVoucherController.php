<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class TenantVoucherController extends Controller
{
    /**
     * List all vouchers owned by tenant.
     * 
     * @OA\Get(
     *      path="/api/v1/tenant/vouchers",
     *      operationId="getTenantVouchers",
     *      tags={"Tenant"},
     *      summary="List all vouchers owned by authenticated tenant",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="List of vouchers",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $vouchers = Voucher::where('tenant_id', $request->user()->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }

    /**
     * Create a new voucher.
     * 
     * @OA\Post(
     *      path="/api/v1/tenant/vouchers",
     *      operationId="createTenantVoucher",
     *      tags={"Tenant"},
     *      summary="Create new voucher",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"title","points_required","valid_until","status"},
     *              @OA\Property(property="title", type="string", example="Voucher Diskon 10%"),
     *              @OA\Property(property="description", type="string", example="Potongan harga untuk pembelian minimal 50rb"),
     *              @OA\Property(property="points_required", type="integer", example=100),
     *              @OA\Property(property="valid_until", type="string", format="date", example="2026-12-31"),
     *              @OA\Property(property="code", type="string", example="PROMO10"),
     *              @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Voucher created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Voucher berhasil dibuat"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:0',
            'valid_until' => 'required|date|after:today',
            'code' => 'nullable|string|unique:vouchers,code',
            'status' => 'required|in:active,inactive',
        ]);

        $voucher = Voucher::create([
            'tenant_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'points_required' => $request->points_required,
            'valid_until' => $request->valid_until,
            'code' => $request->code,
            'status' => $request->status,
        ]);

        ActivityLog::log('Voucher', 'Create', "Tenant {$request->user()->name} created voucher '{$voucher->title}'", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil dibuat',
            'data' => $voucher
        ], 201);
    }

    /**
     * Update a voucher.
     * 
     * @OA\Put(
     *      path="/api/v1/tenant/vouchers/{id}",
     *      operationId="updateTenantVoucher",
     *      tags={"Tenant"},
     *      summary="Update existing voucher",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"title","points_required","valid_until","status"},
     *              @OA\Property(property="title", type="string", example="Voucher Diskon 10%"),
     *              @OA\Property(property="description", type="string", example="Potongan harga untuk pembelian minimal 50rb"),
     *              @OA\Property(property="points_required", type="integer", example=100),
     *              @OA\Property(property="valid_until", type="string", format="date", example="2026-12-31"),
     *              @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Voucher updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Voucher berhasil diperbarui"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(response=403, description="Forbidden (Not owner)")
     * )
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        if ($voucher->tenant_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:0',
            'valid_until' => 'required|date|after:today',
            'status' => 'required|in:active,inactive',
        ]);

        $voucher->update($request->only([
            'title',
            'description',
            'points_required',
            'valid_until',
            'status'
        ]));

        ActivityLog::log('Voucher', 'Update', "Tenant {$request->user()->name} updated voucher '{$voucher->title}'", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil diperbarui',
            'data' => $voucher
        ]);
    }

    /**
     * Delete a voucher.
     * 
     * @OA\Delete(
     *      path="/api/v1/tenant/vouchers/{id}",
     *      operationId="deleteTenantVoucher",
     *      tags={"Tenant"},
     *      summary="Delete voucher",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Voucher deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Voucher berhasil dihapus")
     *          )
     *      ),
     *      @OA\Response(response=403, description="Forbidden (Not owner)")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        if ($voucher->tenant_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $voucherTitle = $voucher->title;
        $voucher->delete();

        ActivityLog::log('Voucher', 'Delete', "Tenant {$request->user()->name} deleted voucher '{$voucherTitle}'", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Voucher berhasil dihapus',
        ]);
    }
}
