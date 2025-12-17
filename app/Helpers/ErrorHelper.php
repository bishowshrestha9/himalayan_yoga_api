<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class ErrorHelper
{
    /**
     * Handle exception and return safe error response
     * Logs the full error details but only returns generic message to user
     * 
     * @param \Throwable $e The exception
     * @param string $userMessage The message to show to the user
     * @param int $statusCode HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function handleException(\Throwable $e, string $userMessage = 'An error occurred', int $statusCode = 500)
    {
        // Log the full error details for debugging
        Log::error($userMessage, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Only return safe message to user (no technical details)
        return response()->json([
            'status' => false,
            'message' => $userMessage,
        ], $statusCode);
    }
}
