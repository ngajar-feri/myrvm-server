<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwaggerAuthMiddleware
{
    /**
     * Roles allowed to access API documentation.
     */
    private $allowedRoles = ['super_admin', 'admin', 'operator', 'teknisi'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via web session
        if (!auth('web')->check()) {
            // Return 403 error page (not redirect) for direct access
            // This prevents iframe from loading nested dashboard/login page
            return $this->accessDeniedResponse('Authentication required. Please login through the dashboard to access API documentation.');
        }

        // Check if user has allowed role
        $user = auth('web')->user();
        if (!in_array($user->role, $this->allowedRoles)) {
            return $this->accessDeniedResponse('Access denied. Your role does not have permission to view API documentation.');
        }

        return $next($request);
    }

    /**
     * Return a styled 403 access denied page.
     */
    private function accessDeniedResponse(string $message): Response
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            max-width: 500px;
        }
        .error-code { font-size: 80px; font-weight: bold; opacity: 0.8; }
        .error-title { font-size: 24px; margin: 20px 0 10px; }
        .error-message { font-size: 14px; opacity: 0.9; line-height: 1.6; }
        .icon { font-size: 60px; margin-bottom: 20px; }
        .btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <div class="error-code">403</div>
        <div class="error-title">Access Denied</div>
        <div class="error-message">' . htmlspecialchars($message) . '</div>
        <a href="' . url('/login') . '" class="btn">Go to Login</a>
    </div>
</body>
</html>';

        return response($html, 403);
    }
}
