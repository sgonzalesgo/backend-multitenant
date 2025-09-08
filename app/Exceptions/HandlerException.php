<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class HandlerException extends ExceptionHandler
{
    protected $dontReport = [
        // agrega aquí excepciones que no quieras reportar a logs/bug trackers
    ];

    public function register(): void
    {
        // reportables si necesitas
    }

    public function render($request, Throwable $e)
    {
        // En APIs o cuando el cliente espera JSON, devolvemos el envelope
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApi($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function renderApi($request, Throwable $e)
    {
        // 422 VALIDATION
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                trans('api.validation_error'),
                422,
                ['fields' => $e->errors()]
            );
        }

        // 401 AUTHENTICATION
        if ($e instanceof AuthenticationException) {
            return ApiResponse::error(
                trans('api.unauthenticated'),
                401
            );
        }

        // 403 AUTHORIZATION
        if ($e instanceof AuthorizationException) {
            return ApiResponse::error(
                trans('api.forbidden'),
                403
            );
        }

        // 404 NOT FOUND (ruta o modelo)
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return ApiResponse::error(
                trans('api.not_found'),
                404
            );
        }

        // 429 RATE LIMIT
        if ($e instanceof ThrottleRequestsException) {
            return ApiResponse::error(
                trans('api.too_many_requests'),
                429
            );
        }

        // HttpException genérico (4xx/5xx)
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: trans('api.http_error');
            return ApiResponse::error($message, $status);
        }

        // 4xx/5xx de base de datos
        if ($e instanceof QueryException) {
            $sqlState   = $e->errorInfo[0] ?? null; // PG: '23505'
            $driverCode = $e->errorInfo[1] ?? null; // MySQL: 1062

            $isUniqueViolation = ($sqlState === '23505') || ($driverCode === 1062);

            if ($isUniqueViolation) {
                return ApiResponse::error(
                    trans('api.unique_violation'),
                    409,
                    $this->errorPayload($e)
                );
            }

            return ApiResponse::error(
                trans('api.query_error'),
                500,
                $this->errorPayload($e)
            );
        }

        // 500 FALLBACK
        return ApiResponse::error(
            trans('api.server_error'),
            500,
            $this->errorPayload($e)
        );
    }

    /**
     * Detalles técnicos sólo en local/testing; en prod se ocultan.
     */
    protected function errorPayload(Throwable $e): ?array
    {
        if (app()->environment('local', 'testing')) {
            return [
                'exception' => class_basename($e),
                'message'   => $e->getMessage(),
                'trace'     => collect($e->getTrace())->take(5)->toArray(),
            ];
        }
        return null;
    }
}
