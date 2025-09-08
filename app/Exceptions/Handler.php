<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): \Illuminate\Http\Response|JsonResponse|RedirectResponse|Response
    {
        // 1) Validación (422) – estructura con errores por campo
        if ($e instanceof ValidationException) {
            return $this->json(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('error.validation_failed'),
                null,
                $e->errors() // array field => [messages]
            );
        }

        // 2) No autenticado (401)
        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        // 3) Sin autorización (403)
        if ($e instanceof AuthorizationException) {
            return $this->json(
                Response::HTTP_FORBIDDEN,
                __('error.forbidden'),
                null,
                $this->errorPayload($e)
            );
        }

        // 4) Modelo no encontrado (404)
        if ($e instanceof ModelNotFoundException) {
            return $this->json(
                Response::HTTP_NOT_FOUND,
                __('error.model_not_found'),
                null,
                $this->errorPayload($e)
            );
        }

        // 5) Ruta no encontrada (404)
        if ($e instanceof NotFoundHttpException) {
            return $this->json(
                Response::HTTP_NOT_FOUND,
                __('error.route_not_found'),
                null,
                $this->errorPayload($e)
            );
        }

        // 6) Método HTTP no permitido (405)
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->json(
                Response::HTTP_METHOD_NOT_ALLOWED,
                __('error.method_not_allowed'),
                null,
                $this->errorPayload($e)
            );
        }

        // 7) Límite de peticiones (429)
        if ($e instanceof ThrottleRequestsException) {
            return $this->json(
                Response::HTTP_TOO_MANY_REQUESTS,
                __('error.too_many_requests'),
                null,
                $this->errorPayload($e)
            );
        }

        // 8) Excepciones HTTP con código específico
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            // Mensaje por defecto según status si el exception->getMessage() viene vacío
            $message = match ($status) {
                400 => __('error.bad_request'),
                401 => __('error.unauthenticated'),
                403 => __('error.forbidden'),
                404 => __('error.route_not_found'),
                405 => __('error.method_not_allowed'),
                422 => __('error.validation_failed'),
                429 => __('error.too_many_requests'),
                default => __('error.http_error'),
            };

            return $this->json(
                $status,
                $e->getMessage() ?: $message,
                null,
                $this->errorPayload($e)
            );
        }

        // 9) Query/DB (500 – puedes ajustar a 400 si prefieres no revelar detalles)
        if ($e instanceof QueryException) {
            return $this->json(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                __('error.server_error'),
                null,
                app()->hasDebugModeEnabled() ? $this->errorPayload($e) : 'Server Error'
            );
        }

        // 10) Fallback (500)
        return $this->json(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            __('error.server_error'),
            null,
            app()->hasDebugModeEnabled() ? $this->errorPayload($e) : 'Server Error'
        );
    }

    /**
     * Custom unauthenticated response (401).
     */
    protected function unauthenticated($request, AuthenticationException $exception): \Illuminate\Http\Response|JsonResponse|RedirectResponse
    {
        return $this->json(
            Response::HTTP_UNAUTHORIZED,
            __('error.unauthenticated'),
            null,
            'Unauthenticated'
        );
    }

    /**
     * Helper para la respuesta estándar.
     */
    protected function json(int $status, string $message, mixed $data = null, mixed $error = null): JsonResponse
    {
        return response()->json([
            'code'    => $status,
            'message' => $message,
            'data'    => $data,
            'error'   => $error,
        ], $status);
    }

    /**
     * Helper para construir un payload de error “seguro”.
     */
    protected function errorPayload(Throwable $e): array|string
    {
        // En producción puedes devolver solo el tipo; en debug, incluye trace/message
        if (config('app.debug')) {
            return [
                'type'    => class_basename($e),
                'message' => $e->getMessage(),
                'trace'   => collect($e->getTrace())->take(3)->all(), // limitar trace
            ];
        }

        return class_basename($e);
    }
}
