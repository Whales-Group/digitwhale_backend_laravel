<?php

namespace App\Http\Middleware;

use App\Enums\TokenAbility;
use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Response;

class BTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return ResponseHelper::unauthorized("User not authenticated");
        }

        $token = $request->user()->tokens()->where('token', str_replace('Bearer ', '', $request->bearerToken()))->first();
        
        if (!$token || !$token->canAccess(TokenAbility::ACCESS_API->value)) {
            return ResponseHelper::unauthorized("Invalid token or insufficient access");
        }

        return $next($request);
    }
}