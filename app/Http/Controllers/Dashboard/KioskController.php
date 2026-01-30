<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RvmMachine;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * KioskController
 * 
 * Handles the RVM-UI Kiosk interface rendering.
 * This is the main entry point for the touchscreen display on RVM machines.
 * 
 * @package App\Http\Controllers\Dashboard
 */
class KioskController extends Controller
{
    /**
     * Display the kiosk interface for a specific RVM machine.
     * 
     * Middleware 'signed' validates the URL signature before entering.
     * If signature is invalid/tampered, Laravel returns 403 Forbidden.
     *
     * @param Request $request
     * @param string $uuid Machine UUID (36-char format)
     * @return View|Response
     */
    public function index(Request $request, string $uuid): View|Response
    {
        // 1. Log incoming request for debugging
        \Illuminate\Support\Facades\Log::info("Kiosk Lookup: checking UUID={$uuid}");

        // 2. Find machine by UUID
        $machine = RvmMachine::where('uuid', $uuid)->first();

        // 3. Logic Check (UUID not found in database)
        if (!$machine) {
            \Illuminate\Support\Facades\Log::error("Kiosk Lookup: Machine NOT FOUND for UUID {$uuid}");
            return $this->renderErrorPage(
                'Mesin Tidak Ditemukan',
                'UUID mesin tidak valid atau tidak terdaftar.',
                404
            );
        }

        // 4. Status Check - Log for debugging
        \Illuminate\Support\Facades\Log::info("Kiosk Lookup: Success for Machine UUID {$machine->uuid}. Status: {$machine->status}");

        // 5. Get configuration
        $config = $this->getMachineConfig($machine);

        return view('dashboard.kiosk.index', [
            'machine' => $machine,
            'config' => $config,
        ]);
    }

    /**
     * Get machine configuration for kiosk initialization.
     *
     * @param RvmMachine $machine
     * @return array<string, mixed>
     */
    private function getMachineConfig(RvmMachine $machine): array
    {
        // Determine theme based on time or saved preference
        $currentHour = (int) now()->setTimezone($machine->timezone ?? 'Asia/Jakarta')->format('H');
        $isNightTime = $currentHour >= 18 || $currentHour < 6;

        return [
            'machine_uuid' => $machine->uuid,
            'machine_name' => $machine->name,
            'location' => $machine->location ?? 'Unknown',
            'timezone' => $machine->timezone ?? 'Asia/Jakarta',
            'theme_mode' => $machine->theme_mode ?? 'auto', // auto, light, dark
            'suggested_theme' => $isNightTime ? 'dark' : 'light',
            'qr_refresh_interval' => 300, // 5 minutes in seconds
            'websocket_channel' => "rvm.{$machine->serial_number}",
            'api_base_url' => config('app.url') . '/api/v1/kiosk',
        ];
    }

    /**
     * Render a styled error page for kiosk display.
     *
     * @param string $title
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    private function renderErrorPage(string $title, string $message, int $statusCode): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - MyRVM Kiosk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
            color: #E0E0E0;
        }
        .container {
            text-align: center;
            padding: 60px 40px;
            background: rgba(255,255,255,0.05);
            border-radius: 24px;
            backdrop-filter: blur(10px);
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .icon { 
            font-size: 80px; 
            margin-bottom: 24px;
            filter: grayscale(0.3);
        }
        .error-code { 
            font-size: 72px; 
            font-weight: 700; 
            color: #4CAF50;
            opacity: 0.9;
        }
        .error-title { 
            font-size: 28px; 
            margin: 20px 0 16px;
            font-weight: 600; 
        }
        .error-message { 
            font-size: 16px; 
            opacity: 0.8; 
            line-height: 1.7;
            color: #9E9E9E;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🌿</div>
        <div class="error-code">{$statusCode}</div>
        <div class="error-title">{$title}</div>
        <div class="error-message">{$message}</div>
    </div>
</body>
</html>
HTML;

        return response($html, $statusCode);
    }
}
