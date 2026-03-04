<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
        // Handle Model Not Found Exception
        if ($e instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($request, $e);
        }

        // Handle 404 Not Found
        if ($e instanceof NotFoundHttpException) {
            return $this->handleNotFoundHttpException($request, $e);
        }

        // Handle Database Query Exception
        if ($e instanceof QueryException) {
            return $this->handleQueryException($request, $e);
        }

        // Handle HTTP Exceptions (403, 500, etc.)
        if ($e instanceof HttpException) {
            return $this->handleHttpException($request, $e);
        }

        // Handle Authentication Exception
        if ($e instanceof AuthenticationException) {
            return $this->handleAuthenticationException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle Model Not Found Exception
     */
    protected function handleModelNotFoundException($request, ModelNotFoundException $e)
    {
        $model = strtolower(class_basename($e->getModel()));
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Resource not found',
                'message' => "The requested {$model} could not be found."
            ], 404);
        }

        return redirect()->back()
            ->with('error', "The requested {$model} could not be found.");
    }

    /**
     * Handle Not Found HTTP Exception
     */
    protected function handleNotFoundHttpException($request, NotFoundHttpException $e)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'The requested resource was not found.'
            ], 404);
        }

        return response()->view('errors.404', [], 404);
    }

    /**
     * Handle Database Query Exception
     */
    protected function handleQueryException($request, QueryException $e)
    {
        // Log the full error for debugging
        \Log::error('Database Query Exception', [
            'message' => $e->getMessage(),
            'sql' => $e->getSql() ?? 'N/A',
            'bindings' => $e->getBindings() ?? [],
        ]);

        $message = 'A database error occurred. Please try again.';

        // Check for specific database errors
        if ($e->getCode() == 23000) {
            // Integrity constraint violation
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $message = 'This record already exists in the system.';
            } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
                $message = 'Cannot delete this record as it is being used by other records.';
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Database Error',
                'message' => $message
            ], 500);
        }

        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Handle HTTP Exception
     */
    protected function handleHttpException($request, HttpException $e)
    {
        $statusCode = $e->getStatusCode();
        $message = $e->getMessage() ?: 'An error occurred';

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'HTTP Error',
                'message' => $message,
                'status_code' => $statusCode
            ], $statusCode);
        }

        // Check if custom error view exists
        if (view()->exists("errors.{$statusCode}")) {
            return response()->view("errors.{$statusCode}", [
                'exception' => $e
            ], $statusCode);
        }

        return redirect()->back()
            ->with('error', $message);
    }

    /**
     * Handle Authentication Exception
     */
    protected function handleAuthenticationException($request, AuthenticationException $e)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource.'
            ], 401);
        }

        return redirect()->guest(route('login'))
            ->with('error', 'Please login to continue.');
    }

    /**
     * Convert a validation exception into a JSON response.
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'error' => 'Validation Error',
            'message' => 'The given data was invalid.',
            'errors' => $exception->errors(),
        ], $exception->status);
    }
}
