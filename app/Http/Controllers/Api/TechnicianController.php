<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicianAssignment;
use App\Models\RvmMachine;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TechnicianController extends Controller
{
    /**
     * List assignments for authenticated technician.
     */
    public function assignments(Request $request)
    {
        $assignments = TechnicianAssignment::where('technician_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->with('rvmMachine')
            ->get();

        ActivityLog::log('Maintenance', 'Read', "User {$request->user()->name} viewed their assignments", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'data' => $assignments
        ]);
    }

    /**
     * Generate PIN for machine access.
     */
    public function generatePin(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:technician_assignments,id',
        ]);

        $assignment = TechnicianAssignment::findOrFail($request->assignment_id);

        if ($assignment->technician_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $assignment->update([
            'access_pin' => $pin,
            'pin_expires_at' => now()->addMinutes(30),
            'status' => 'in_progress'
        ]);

        ActivityLog::log('Maintenance', 'Create', "PIN generated for machine assignment #{$assignment->id}", $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN generated',
            'data' => [
                'pin' => $pin,
                'expires_at' => $assignment->pin_expires_at
            ]
        ]);
    }

    /**
     * Validate PIN (Called by RVM Machine).
     */
    public function validatePin(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|exists:rvm_machines,serial_number',
            'pin' => 'required|string|size:6',
        ]);

        $machine = RvmMachine::where('serial_number', $request->serial_number)->firstOrFail();

        $assignment = TechnicianAssignment::where('rvm_machine_id', $machine->id)
            ->where('access_pin', $request->pin)
            ->where('pin_expires_at', '>', now())
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'Invalid or expired PIN'], 401);
        }

        ActivityLog::log('Maintenance', 'Access', "Machine {$machine->name} accessed via PIN by technician: {$assignment->technician->name}", $assignment->technician_id);

        return response()->json([
            'status' => 'success',
            'message' => 'Access Granted',
            'data' => [
                'technician' => $assignment->technician->name,
                'assignment_id' => $assignment->id
            ]
        ]);
    }
}
