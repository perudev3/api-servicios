<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $module)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 403);
        }

        if (!$user->canAccessModule($module)) {
            return response()->json([
                'success' => false,
                'message' => "No tienes acceso al módulo: {$module}.",
            ], 403);
        }

        return $next($request);
    }
}
