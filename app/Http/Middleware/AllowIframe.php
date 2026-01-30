<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowIframe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Remove headers that block iframes
        $response->headers->remove('X-Frame-Options');
        
        // Add headers to allow iframes from self
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        
        return $response;
    }
}
