<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    /**
     * Display maintenance tickets page
     */
    public function index()
    {
        return view('dashboard.tickets.index');
    }

    /**
     * Return content-only view for SPA navigation
     */
    public function indexContent()
    {
        return view('dashboard.tickets.index-content');
    }
}
