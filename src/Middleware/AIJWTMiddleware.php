<?php

namespace LaravelAIAssistant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

            // Simple JWT validation for testing
            try {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $token)[1])), true);
                
                if (!$payload || !isset($payload['user_id'])) {
                    throw new \Exception('Invalid token payload');
                }
                
                // Check if token is expired
                if (isset($payload['exp']) && $payload['exp'] < time()) {
                    throw new \Exception('Token expired');
                }
                
                // Token is valid, continue with the request
                return $next($request);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid token',
                    'message' => 'AI token is invalid or expired',
                    'details' => $e->getMessage()
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
