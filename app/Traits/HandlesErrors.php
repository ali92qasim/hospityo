<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait HandlesErrors
{
    /**
     * Handle success response
     */
    protected function successResponse(string $message, $data = null, string $redirectRoute = null): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ]);
        }

        $redirect = $redirectRoute ? redirect()->route($redirectRoute) : redirect()->back();
        return $redirect->with('success', $message);
    }

    /**
     * Handle error response
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], $code);
        }

        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Handle exception with logging
     */
    protected function handleException(\Throwable $e, string $context = 'Operation'): RedirectResponse|JsonResponse
    {
        // Log the exception
        Log::error("{$context} failed", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Determine user-friendly message
        $message = config('app.debug') 
            ? $e->getMessage() 
            : "{$context} failed. Please try again or contact support.";

        return $this->errorResponse($message, 500);
    }

    /**
     * Handle validation errors
     */
    protected function validationError(array $errors): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        return redirect()->back()
            ->withErrors($errors)
            ->withInput();
    }

    /**
     * Handle not found error
     */
    protected function notFoundResponse(string $resource = 'Resource'): RedirectResponse|JsonResponse
    {
        $message = "{$resource} not found.";

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 404);
        }

        return redirect()->back()->with('error', $message);
    }

    /**
     * Handle unauthorized access
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized access'): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
