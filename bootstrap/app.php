<?php

use App\Exceptions\AppException;
use App\Exceptions\CodedException;
use App\Helpers\ResponseHelper;
use App\Http\Middleware\HandleCors;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'VerifyApiKey' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'SetStructure' => \App\Http\Middleware\StructuralMiddleware::class,
            'BearerTokenEnforcer'=>\App\Http\Middleware\BTMiddleware::class,
            'steril' => \App\Http\Middleware\EncryptResponseDecryptPayload::class,
        ]);
        $middleware->append(HandleCors::class);
    })
    ->withExceptions(function ($exceptions) {
        // Define custom exception handlers
        // $exceptions->render(function (RouteNotFoundException $exception, $request) {
        //     return ResponseHelper::unprocessableEntity(
        //         message: 'The requested route was not found.',
        //     );
        // });
    
        $exceptions->render(function (InvalidParameterException $exception, $request) {
            return ResponseHelper::unprocessableEntity(
                message: 'Invalid parameters provided.',
            );
        });

        $exceptions->render(function (AppException $exception, $request) {
            return ResponseHelper::error(
                message: $exception->getMessage(),
            );
        });
        
        $exceptions->render(function (CodedException $exception, $request) {
            return ResponseHelper::error(
                message: $exception->getMessage(),
            );
        });
        
        $exceptions->render(function (AuthenticationException $exception, $request) {
            return ResponseHelper::error(
                message: 'Please provide a valid bearer token.',
                error: 'Unauthorized',
            );
        });

        // Fallback for all other exceptions
        $exceptions->render(function (\Throwable $exception, $request) {

            // for auth exceptions
            if ($exception->getMessage() == "Route [login] not defined.") {
                return ResponseHelper::unauthenticated(
                    message: 'Unauthenticated.',
                    error: "Invalid token, Token not found or expired token."
                );
            }

            return ResponseHelper::internalServerError(
                message: 'An unexpected error occurred.',
                error: config('app.debug') ? $exception->getMessage() : null
            );
        });
    })
    ->create();