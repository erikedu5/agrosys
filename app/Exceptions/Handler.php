<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use BadMethodCallException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof BadMethodCallException) {
            return $this->renderSafeError($request, 404);
        }

        if ($e instanceof QueryException) {
            Log::error('Database error.', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            $message = $this->isDatabaseConnectionError($e)
                ? 'No se pudo conectar con la base de datos. Verifica que el servicio este arriba.'
                : 'Ocurrio un error al consultar la base de datos.';

            return $this->renderSafeError($request, 500, $message);
        }

        if ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        if ($request->expectsJson() && !($e instanceof ValidationException)) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

            if ($status >= 500) {
                return response()->json([
                    'message' => 'Ocurrio un error inesperado. Intenta de nuevo mas tarde o contacta al administrador.',
                ], $status);
            }
        }

        if ($request->header('X-Inertia')) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;
            $message = config('app.debug') ? $this->debugMessage($e, $status) : null;
            return $this->renderInertiaError($request, $status, $message);
        }

        if (!config('app.debug')) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

            if (in_array($status, [404, 419, 429, 500, 503])) {
                return response()->view("errors.{$status}", [], $status);
            }

            return response()->view('errors.500', [], 500);
        }

        return parent::render($request, $e);
    }

    private function renderSafeError($request, int $status, ?string $message = null)
    {
        if ($request->header('X-Inertia')) {
            return $this->renderInertiaError($request, $status, $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message ?: 'No pudimos procesar la operacion. Intenta nuevamente o contacta al administrador.',
            ], $status);
        }

        $viewStatus = in_array($status, [404, 419, 429, 500, 503]) ? $status : 500;

        return response()->view("errors.{$viewStatus}", [], $viewStatus);
    }

    private function renderInertiaError($request, int $status, ?string $message = null)
    {
        $normalized = in_array($status, [404, 419, 429, 500, 503]) ? $status : 500;

        return Inertia::render('Error', [
            'status' => $normalized,
            'url' => $request->fullUrl(),
            'message' => $message,
        ])->toResponse($request)->setStatusCode($normalized);
    }

    private function isDatabaseConnectionError(QueryException $e): bool
    {
        $message = $e->getMessage();
        $code = (string) $e->getCode();

        return in_array($code, ['2002', '2006', '1045', '1049'], true)
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'SQLSTATE[HY000] [2006]')
            || str_contains($message, 'SQLSTATE[HY000] [1045]')
            || str_contains($message, 'SQLSTATE[HY000] [1049]');
    }

    private function debugMessage(Throwable $e, int $status): ?string
    {
        if ($status < 500) {
            return null;
        }

        $location = $e->getFile() . ':' . $e->getLine();

        return get_class($e) . ' - ' . $e->getMessage() . ' (' . $location . ')';
    }
}
