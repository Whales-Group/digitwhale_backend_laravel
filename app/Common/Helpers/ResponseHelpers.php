<?php

namespace App\Common\Helpers;

use Illuminate\Http\JsonResponse;

abstract class ResponseHelpers
{
    public static function success(
        mixed $data = [],
        string $message = "Successful",
        int $statusCode = 200
    ): JsonResponse {
        return self::buildResponse(true, $message, $data, $statusCode);
    }

    // BZX-SERVER-UPGRADE

    public static function error(
        string $message = "An error occurred",
        int $statusCode = 400,
        mixed $data = []
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, $statusCode);
    }

    public static function created(
        string $message = "Resource created",
        int $statusCode = 201
    ): JsonResponse {
        return self::buildResponse(true, $message, [], $statusCode);
    }

    public static function updated(
        string $message = "Resource updated",
        int $statusCode = 200
    ): JsonResponse {
        return self::buildResponse(true, $message, [], $statusCode);
    }

    public static function internalServerError(
        string $message = "Internal Server Error",
        int $statusCode = 500
    ): JsonResponse {
        return self::buildResponse(false, $message, [], $statusCode);
    }

    public static function unauthorized(
        string $message = "Unauthorized",
        int $statusCode = 401
    ): JsonResponse {
        return self::buildResponse(false, $message, [], $statusCode);
    }
    public static function unauthenticated(
        string $message = "Unauthenticated",
        int $statusCode = 401
    ): JsonResponse {
        return self::buildResponse(false, $message, [], $statusCode);
    }

    public static function forbidden(
        string $message = "Forbidden",
        int $statusCode = 403
    ): JsonResponse {
        return self::buildResponse(false, $message, [], $statusCode);
    }

    public static function notFound(
        string $message = "Resource not found",
        int $statusCode = 404
    ): JsonResponse {
        return self::buildResponse(false, $message, [], $statusCode);
    }

    public static function unprocessableEntity(
        string $message = "Unprocessable Entity",
        int $statusCode = 422
    ): JsonResponse {
        return self::buildResponse(false, $message, [], $statusCode);
    }

    private static function buildResponse(
        bool $status,
        string $message,
        mixed $data = [],
        int $statusCode = 200
    ): JsonResponse {
        return response()->json(
            [
                "status" => $status,
                "statusCode" => $statusCode,
                "message" => $message,
                "data" => $data,
            ],
            $statusCode
        );
    }

    public static function implodeNestedArray(
        mixed $data,
        mixed $keys,
        string $separator = " "
    ): string {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $implodedValue = implode($separator, $data[$key]);
                if (!empty($implodedValue)) {
                    // Return the first error message if found
                    return $implodedValue;
                }
            }
        }

        // Return empty string if no errors found
        return "";
    }
}
