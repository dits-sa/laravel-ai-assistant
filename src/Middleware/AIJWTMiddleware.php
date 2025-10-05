<?php

namespace LaravelAIAssistant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use LaravelAIAssistant\Controllers\AIAuthController;

class AIJWTMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        try {
            // Try multiple token sources
            $token = $request->bearerToken() ?? $request->header('X-AI-Token') ?? $request->get('token');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token required',
                    'message' => 'AI token is required for this operation'
                ], 401);
            }

            // Validate token using the same logic as AIAuthController
            $authController = new AIAuthController();
            $validationResult = $authController->validateToken($request);
            
            if ($validationResult->getData()->success) {
                // Token is valid, continue with the request
                return $next($request);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid token',
                    'message' => 'AI token is invalid or expired'
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed',
                'message' => 'Failed to validate AI token',
                'details' => $e->getMessage()
            ], 401);
        }
    }
}
