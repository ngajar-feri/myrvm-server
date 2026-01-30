<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Display assignment list page
     */
    public function index()
    {
        return view('dashboard.assignments.index');
    }

    /**
     * Return content-only view for SPA navigation
     */
    public function indexContent()
    {
        return view('dashboard.assignments.index-content');
    }
}
