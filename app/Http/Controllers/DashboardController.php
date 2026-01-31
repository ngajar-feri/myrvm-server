<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RvmMachine;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Simple Role-Based Redirect / View Rendering
        // In a real app, you might use Policies or dedicated Controllers per role
        
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            $machines = RvmMachine::with('edgeDevice')->get();
            return view('dashboard.admin', compact('machines'));
        }
        
        if ($user->role === 'operator') {
            return view('dashboard.operator');
        }
        
        if ($user->role === 'tenan') {
            return view('dashboard.tenant');
        }
        
        // Default for 'user'
        return view('dashboard.user');
    }
}
