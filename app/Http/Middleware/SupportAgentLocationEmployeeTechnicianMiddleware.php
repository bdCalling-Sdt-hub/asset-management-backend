<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SupportAgentLocationEmployeeTechnicianMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! in_array($user->role, ['super_admin','organization','third_party','location_employee','support_agent','technician',])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}
