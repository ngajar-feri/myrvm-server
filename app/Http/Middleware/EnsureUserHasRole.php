<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Role hierarchy (higher index = higher access)
     */
    protected $roleHierarchy = [
        'user' => 1,
        'tenan' => 2,
        'tenant' => 2,
        'teknisi' => 3,
        'operator' => 3,
        'admin' => 4,
        'super_admin' => 5,
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            abort(403, 'Unauthorized access: Not authenticated.');
        }

        $userRole = $request->user()->role;
        $userLevel = $this->roleHierarchy[$userRole] ?? 0;

        // Super admin and admin always have access
        if ($userLevel >= 4) {
            return $next($request);
        }

        // Check if user's role is in the allowed roles list
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // Check role hierarchy - user can access if their level is >= lowest required level
        $minRequiredLevel = PHP_INT_MAX;
        foreach ($roles as $role) {
            $roleLevel = $this->roleHierarchy[$role] ?? 0;
            if ($roleLevel < $minRequiredLevel) {
                $minRequiredLevel = $roleLevel;
            }
        }

        if ($userLevel >= $minRequiredLevel) {
            return $next($request);
        }

        abort(403, 'Unauthorized access: Insufficient role privileges.');
    }
}
