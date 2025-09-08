<?php

use App\Http\Middleware\EnsureEmailVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\{
    HttpException,
    NotFoundHttpException,
    MethodNotAllowedHttpException
};
use Symfony\Component\HttpFoundation\Response;
use App\Http\Middleware\AttachBearerTokenFromCookie;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeamFromTenant;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'               => RoleMiddleware::class,
            'permission'         => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'setLocale' => SetLocale::class,
            'bearer_cookie' => AttachBearerTokenFromCookie::class,
            'verified.email' => EnsureEmailVerified::class,
        ]);
        $middleware->group('tenant', [
            NeedsTenant::class,
            SetPermissionsTeamFromTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Helper para JSON uniforme
        $json = function (int $status, string $message, mixed $data = null, mixed $error = null) {
            return response()->json([
                'code'    => $status,
                'message' => $message,
                'data'    => $data,
                'error'   => $error,
            ], $status);
        };

        // 422 – Validación
        $exceptions->render(function (ValidationException $e, $request) use ($json) {
            return $json(Response::HTTP_UNPROCESSABLE_ENTITY, __('errors.validation_failed'), null, $e->errors());
        });

        // 401 – No autenticado
        $exceptions->render(function (AuthenticationException $e, $request) use ($json) {
            return $json(Response::HTTP_UNAUTHORIZED, __('errors.unauthenticated'), null, 'Unauthenticated');
        });

        // 403 – Sin autorización
        $exceptions->render(function (AuthorizationException $e, $request) use ($json) {
            return $json(Response::HTTP_FORBIDDEN, __('errors.forbidden'), null, class_basename($e));
        });

        // 404 – Modelo / Ruta no encontrada
        $exceptions->render(function (ModelNotFoundException $e, $request) use ($json) {
            return $json(Response::HTTP_NOT_FOUND, __('errors.model_not_found'), null, class_basename($e));
        });
        $exceptions->render(function (NotFoundHttpException $e, $request) use ($json) {
            return $json(Response::HTTP_NOT_FOUND, __('errors.route_not_found'), null, class_basename($e));
        });

        // 405 – Método no permitido
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) use ($json) {
            return $json(Response::HTTP_METHOD_NOT_ALLOWED, __('errors.method_not_allowed'), null, class_basename($e));
        });

        // 429 – Rate limit
        $exceptions->render(function (ThrottleRequestsException $e, $request) use ($json) {
            return $json(Response::HTTP_TOO_MANY_REQUESTS, __('errors.too_many_requests'), null, class_basename($e));
        });

        // Otras HttpException con status específico
        $exceptions->render(function (HttpException $e, $request) use ($json) {
            $status  = $e->getStatusCode();
            $message = match ($status) {
                400 => __('errors.bad_request'),
                401 => __('errors.unauthenticated'),
                403 => __('errors.forbidden'),
                404 => __('errors.route_not_found'),
                405 => __('errors.method_not_allowed'),
                422 => __('errors.validation_failed'),
                429 => __('errors.too_many_requests'),
                default => __('errors.http_error'),
            };
            return $json($status, $e->getMessage() ?: $message, null, class_basename($e));
        });

        // Fallback 500 – cualquier otra excepción
        $exceptions->render(function (Throwable $e, $request) use ($json) {
            $error = config('app.debug')
                ? ['type' => class_basename($e), 'message' => $e->getMessage(), 'trace' => collect($e->getTrace())->take(3)->all()]
                : 'Server Error';
            return $json(Response::HTTP_INTERNAL_SERVER_ERROR, __('errors.server_error'), null, $error);
        });
    })->create();
