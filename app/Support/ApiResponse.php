<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $http = 200): JsonResponse
    {
        return response()->json([
            'code'    => $http,   // el http code numérico
            'message' => $message,
            'data'    => $data,
            'error'   => null,
        ], $http);
    }

    public static function index(mixed $data = null, string $message = 'List', int $http = 200): JsonResponse
    {
        return self::success($data, $message, $http);
    }

    public static function store(mixed $data = null, string $message = 'Created', int $http = 201): JsonResponse
    {
        return self::success($data, $message, $http);
    }

    public static function updated(mixed $data = null, string $message = 'Updated', int $http = 200): JsonResponse
    {
        return self::success($data, $message, $http);
    }

    public static function deleted(mixed $data = null, string $message = 'Deleted', int $http = 200): JsonResponse
    {
        return self::success($data, $message, $http);
    }

    public static function noContent(string $message = 'No Content', int $http = 204): JsonResponse
    {
        // Mantenemos el envelope, aunque sea un 204
        return response()->json([
            'code'    => $http,
            'message' => $message,
            'data'    => null,
            'error'   => null,
        ], $http);
    }

    public static function error(string $message, int $http, mixed $errorDetails = null, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code'    => $http,
            'message' => $message,
            'data'    => $data,
            'error'   => $errorDetails, // puede ser string|array
        ], $http);
    }
}
