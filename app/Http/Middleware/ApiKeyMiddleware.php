<?php

namespace App\Http\Middleware;

use App\Common\Helpers\DateHelper;
use App\Common\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header("x-api-key");

        if ($apiKey) {
            if ($apiKey !== "cDZsb3JpcW93ZWt3bGdlN2tmc2xib2tsejJscmgwOTM") {
                // echo "apiKey: ^". $apiKey . "^\n";
                // echo "API_KEY: ^".  env("API_KEY") . "^\n";

                return ResponseHelper::unauthorized("Invalid API_KEY");
            }
        } else {
            return ResponseHelper::unauthorized("API_KEY not found");
        }
        return $next($request);
    }
}
