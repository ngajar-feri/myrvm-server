<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogsController extends Controller
{
    /**
     * Display the logs management page.
     */
    public function index(Request $request)
    {
        // Check role access (super_admin, admin, operator, teknisi)
        $allowedRoles = ['super_admin', 'admin', 'operator', 'teknisi'];
        if (!in_array(auth()->user()->role, $allowedRoles)) {
            abort(403, 'Access denied');
        }

        // Return content only for SPA navigation
        if ($request->ajax() || $request->is('*/content')) {
            return view('dashboard.logs.index-content');
        }

        return view('dashboard.logs.index');
    }

    /**
     * Return only the content for SPA navigation.
     */
    public function content()
    {
        return view('dashboard.logs.index-content');
    }
}
