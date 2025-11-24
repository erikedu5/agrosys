<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
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
        if ($e instanceof QueryException) {
            return $this->renderSafeError($request, 500);
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
            return $this->renderInertiaError($request, $status);
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

    private function renderSafeError($request, int $status)
    {
        if ($request->header('X-Inertia')) {
            return $this->renderInertiaError($request, $status);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No pudimos procesar la operacion. Intenta nuevamente o contacta al administrador.',
            ], $status);
        }

        $viewStatus = in_array($status, [404, 419, 429, 500, 503]) ? $status : 500;

        return response()->view("errors.{$viewStatus}", [], $viewStatus);
    }

    private function renderInertiaError($request, int $status)
    {
        $normalized = in_array($status, [404, 419, 429, 500, 503]) ? $status : 500;

        return Inertia::render('Error', [
            'status' => $normalized,
        ])->toResponse($request)->setStatusCode($normalized);
    }
}
