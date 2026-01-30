<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Simple Role-Based Redirect / View Rendering
        // In a real app, you might use Policies or dedicated Controllers per role
        
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return view('dashboard.admin');
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
