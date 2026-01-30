<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RvmMachine;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateKioskMachine Middleware
 * 
 * Validates that API requests come from a registered and active RVM machine.
 * Machine UUID is passed via X-Machine-UUID header.
 * 
 * @package App\Http\Middleware
 */
class ValidateKioskMachine
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $machineUuid = $request->header('X-Machine-UUID');

        if (!$machineUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Header X-Machine-UUID diperlukan.',
                'error_code' => 'MISSING_MACHINE_UUID',
            ], 400);
        }

        // Validate machine exists and is active
        $machine = RvmMachine::where('serial_number', $machineUuid)
            ->where('status', 'active')
            ->first();

        if (!$machine) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin tidak ditemukan atau tidak aktif.',
                'error_code' => 'INVALID_MACHINE',
            ], 404);
        }

        // Inject machine into request for controllers
        $request->merge(['validated_machine' => $machine]);
        $request->attributes->set('machine', $machine);

        return $next($request);
    }
}
