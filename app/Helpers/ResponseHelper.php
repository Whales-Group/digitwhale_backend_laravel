<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
class ResponseHelper
{
    /**
     * Build a standardized response.
     *
     * @param bool $status
     * @param string $message
     * @param array $data
     * @param int $statusCode
     * @param mixed|null $error
     * @return JsonResponse
     */
    private static function buildResponse(
        bool $status,
        string $message,
        mixed $data,
        int $statusCode,
        mixed $error = null
    ): JsonResponse {
        return response()->json(
            [
                "status" => $status,
                "statusCode" => $statusCode,
                "message" => $message,
                "data" => $data,
                "error" => $error,
            ],
            $statusCode,
            [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "Enviroment" => env("APP_ENV"),
                "Organization" => "DigitWhale",
            ]
        );
    }

    /**
     * Success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param string|null $error
     * @return JsonResponse
     */
    public static function success(
        mixed $data = [],
        string $message = "Successful",
        int $statusCode = 200,
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(true, $message, $data, $statusCode, $error);
    }

    /**
     * Error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $data
     * @param string|null $error
     * @return JsonResponse
     */
    public static function error(
        string $message = "An error occurred",
        int $statusCode = 400,
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, $statusCode, $error);
    }

    /**
     * Created response.
     *
     * @param mixed $data
     * @param string $message
     * @param string|null $error
     * @return JsonResponse
     */
    public static function created(
        mixed $data = [],
        string $message = "Resource created successfully",
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(true, $message, $data, 201, $error);
    }

    /**
     * Updated response.
     *
     * @param mixed $data
     * @param string $message
     * @param string|null $error
     * @return JsonResponse
     */
    public static function updated(
        mixed $data = [],
        string $message = "Resource updated successfully",
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(true, $message, $data, 200, $error);
    }

    /**
     * Internal Server Error response.
     *
     * @param string $message
     * @param mixed $data
     * @param string|null $error
     * @return JsonResponse
     */
    public static function internalServerError(
        string $message = "Internal Server Error",
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, 500, $error);
    }

    /**
     * Unauthorized response.
     *
     * @param string $message
     * @param mixed $data
     * @param string|null $error
     * @return JsonResponse
     */
    public static function unauthorized(
        string $message = "Unauthorized",
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, 401, $error);
    }

    /**
     * Unauthenticated response.
     *
     * @param string $message
     * @param mixed $data
     * @param string|null $error
     * @return JsonResponse
     */
    public static function unauthenticated(
        string $message = "Unauthenticated",
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, 403, $error);
    }

    /**
     * Forbidden response.
     *
     * @param string $message
     * @param mixed $data
     * @param string|null $error
     * @return JsonResponse
     */
    public static function forbidden(
        string $message = "Forbidden",
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, 403, $error);
    }

    /**
     * Not Found response.
     *
     * @param string $message
     * @param mixed $data
     * @param string|null $error
     * @return JsonResponse
     */
    public static function notFound(
        string $message = "Resource not found",
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, 404, $error);
    }

    /**
     * Unprocessable Entity response.
     *
     * @param string $message
     * @param mixed $data
     * @param mixed|null $error
     * @return JsonResponse
     */
    public static function unprocessableEntity(
        string $message = "Unprocessable entity",
        mixed $data = [],
        mixed $error = null
    ): JsonResponse {
        return self::buildResponse(false, $message, $data, 422, $error);
    }

    /**
     * Method to implode nested arrays.
     *
     * @param mixed $mixed
     * @param string $separator
     * @return mixed
     */
    public static function implodeNestedArrays(
        mixed $array,
        string $separator = ", "
    ): array {
        return array_map(function ($value) use ($separator) {
            return is_array($value) ? implode($separator, $value) : $value;
        }, $array);
    }
}
