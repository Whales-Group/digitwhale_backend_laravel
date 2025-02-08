<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AppLog;

class StructuralMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log the incoming request
        AppLog::debug("Request data: ", $request->all());

        // Pass the request to the next middleware and get the response
        $response = $next($request);

        // Log the response data (if needed)
        AppLog::debug("Response status: ", [
            "status" => $response->getStatusCode(),
        ]);

        // Modify the response headers
        $response->headers->set("Accept", "application/json");
        $response->headers->set("Content-Type", "application/json");

        return $response;
    }
}
