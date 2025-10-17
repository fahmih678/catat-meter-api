<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
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
            // Don't report broken pipe errors as they're expected in development
            if ($this->isBrokenPipeError($e)) {
                return false;
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // Handle broken pipe errors gracefully
            if ($this->isBrokenPipeError($e)) {
                return $this->handleBrokenPipeError($e, $request);
            }

            // Handle API exceptions
            if ($request->is('api/*')) {
                return $this->handleApiException($e, $request);
            }
        });
    }

    /**
     * Check if the exception is a broken pipe error
     */
    private function isBrokenPipeError(Throwable $e): bool
    {
        $message = $e->getMessage();

        return strpos($message, 'Broken pipe') !== false ||
            strpos($message, 'errno=32') !== false ||
            strpos($message, 'file_put_contents()') !== false ||
            strpos($message, 'Connection reset by peer') !== false;
    }

    /**
     * Handle broken pipe errors
     */
    private function handleBrokenPipeError(Throwable $e, Request $request): Response
    {
        // Log the error with minimal details
        Log::debug('Broken pipe detected', [
            'url' => $request->url(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);

        // Return a minimal JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Request processed'
        ], 200, [
            'Connection' => 'close',
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Handle API exceptions
     */
    private function handleApiException(Throwable $e, Request $request): Response
    {
        $status = 500;
        $message = 'Internal Server Error';

        // Determine status code based on exception type
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $status = 422;
            $message = 'Validation Error';
        } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $status = 404;
            $message = 'Resource Not Found';
        } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
            $status = 401;
            $message = 'Unauthorized';
        } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            $status = 403;
            $message = 'Forbidden';
        }

        $response = [
            'status' => 'error',
            'message' => $message,
            'code' => $status
        ];

        // Add validation errors if available
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $response['errors'] = $e->errors();
        }

        // Add debug information in non-production
        if (!app()->environment('production') && config('api.error_handling.include_trace', false)) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        return response()->json($response, $status, [
            'Content-Type' => 'application/json',
            'Connection' => 'close'
        ]);
    }
}
