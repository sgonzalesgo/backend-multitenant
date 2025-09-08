<?php

namespace App\Traits;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

trait ApiRespondTrait
{
    // ------- ÉXITO -------
    protected function ok(mixed $data = null): JsonResponse
    { return ApiResponse::success($data, trans('api.success'), 200); }

    protected function createdResponse(mixed $data = null): JsonResponse
    { return ApiResponse::store($data, trans('api.created'), 201); }

    protected function updatedResponse(mixed $data = null): JsonResponse
    { return ApiResponse::updated($data, trans('api.updated'), 200); }

    protected function deletedResponse(mixed $data = null): JsonResponse
    { return ApiResponse::deleted($data, trans('api.deleted'), 200); }

    protected function noContent(): JsonResponse
    { return ApiResponse::noContent(trans('api.no_content'), 204); }

    // ------- ERRORES -------
    protected function badRequest(mixed $details = null): JsonResponse
    {
        return $this->error(trans('api.bad_request'), 400, $details);
    }

    protected function unauthorized(): JsonResponse
    {
        return $this->error(trans('api.unauthenticated'), 401);
    }

    protected function forbidden(): JsonResponse
    {
        return $this->error(trans('api.forbidden'), 403);
    }

    protected function notFound(): JsonResponse
    {
        return $this->error(trans('api.not_found'), 404);
    }

    protected function conflict(mixed $details = null): JsonResponse
    {
        return $this->error(trans('api.conflict'), 409, $details);
    }

    protected function validationError(array $fields): JsonResponse
    {
        return $this->error(trans('api.validation_error'), 422, ['fields' => $fields]);
    }

    protected function tooManyRequests(): JsonResponse
    {
        return $this->error(trans('api.too_many_requests'), 429);
    }

    protected function serverError(mixed $details = null): JsonResponse
    {
        return $this->error(trans('api.server_error'), 500, $details);
    }

    protected function error(string $message, int $http = 400, mixed $errorDetails = null, mixed $data = null): JsonResponse
    {
        return ApiResponse::error($message, $http, $errorDetails, $data);
    }
}
