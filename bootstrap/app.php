<?php

use App\Common\Helpers\ResponseHelper;
use App\Http\Middleware\HandleCors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Exceptions\Handler;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'VerifyApiKey' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'SetStructure' => \App\Http\Middleware\StructuralMiddleware::class,
        ]);
        $middleware->append(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (
            RouteNotFoundException $exception, $request) {
            return ResponseHelper::unprocessableEntity($exception->getMessage());
        });
    })
    ->create();
